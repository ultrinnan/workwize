# Execution Plan — MDM Device Sync (Laravel + React)

## 1. Requirements Analysis

### Functional (from `Readme.md`)
- Laravel backend reads `files/api-mock-response.json` directly and syncs **assigned devices only** into MySQL.
- React frontend must **not** read the JSON directly; it talks to the Laravel HTTP API.
- UI shows synced **assets** and **employees**; supports **delete only** (no create/edit).
- Deleting an asset or employee removes it from the **local DB only**.
- Manual **Sync Now** trigger.
- MDM Sync Behaviour Rules:
  - Asset uniqueness by **serial code**.
  - Employee uniqueness by **email**.
  - Unassigned devices (no employee email) must **not** be imported.
  - Device details visible in the assets list after sync.
  - If an employee does not exist, create it.
  - Jamf is the source of truth for device assignments.
  - Re-running sync must **recreate** deleted assets/employees.
  - Changes to assignment or attributes in the mock response must be reflected after sync (e.g. RAM 8GB -> 16GB).

### Non-functional
- Laravel + React + MySQL.
- Dockerized: `docker-compose.yml` starts backend, frontend, MySQL.
- Runnable with a single command (`docker compose up`).
- README with start instructions and browser access.
- Design for extensibility: make adding future MDM providers easy.
- Improve upon `files/RefJamfSyncService.php`.

### Reference: `files/RefJamfSyncService.php`
Good baseline (transaction, field fallbacks, email/serial validation) but weak on:
- No provider abstraction — `jamf` and the Jamf payload shape are hardcoded inside the service.
- No sync run history/reporting; no change detection (everything counts as "updated").
- No handling for devices that disappear from Jamf.
- Employee fields kept stale (`?? $employee->name` preserves old value).
- No `last_seen_at`, `position`, or external id tracking.

### Mock data observations (`files/api-mock-response.json`)
- 7 devices, all currently assigned; one employee (Alex Smith) owns 2 devices => employee -> assets is 1-to-many.
- Fields available but unused by reference: `position`, `udid`, `lastContactTime`.
- No `storage.disks` in this mock => storage must degrade gracefully to `null`.
- Uses `totalRamMegabytes` (16384 / 8192 / 32768) => convert MB -> GB.

### UI wireframe (`files/ui_design_wireframe.png`)
- **Screen 1 — Asset Management**: top nav (`Home`, `Assets`, `Employees`), heading + **Sync Now** button (top-right), list of asset rows, each showing `Asset N` and `Assigned To: EmployeeN`.
- **Screen 2 — Asset Details**: heading, an **Asset Name** box, and an **Asset Attributes** box (hardware specs, brand name, etc.).
- Implies routes: `Home`, `Assets`, `Employees`, plus an Asset Details view/drawer opened from an asset row.

## 2. Repository Layout

```
mdm-case-study/
├── docker-compose.yml
├── Readme.md
├── PLAN.md
├── backend/                 # Laravel 11 app
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Services/Mdm/
│   │   └── Data/            # DTOs
│   ├── database/migrations/
│   ├── database/seeders/
│   └── tests/
├── frontend/                # React + Vite + TypeScript
│   └── src/
│       ├── api/
│       ├── pages/
│       └── components/
└── files/                   # provided references (mock JSON, wireframe, ref service)
```

## 3. Database Schema

### `employees`
| column | notes |
|---|---|
| id | PK |
| email | unique, required |
| name | nullable |
| phone | nullable |
| position | nullable (from mock `userAndLocation.position`) |
| timestamps | created_at / updated_at |

Hard delete (not soft delete) — re-sync recreates; avoids unique-email vs soft-delete conflict.

### `assets`
| column | notes |
|---|---|
| id | PK |
| serial_code | unique, required (uniqueness key) |
| employee_id | FK -> employees, RESTRICT on delete |
| device_name | from `general.name` etc. |
| provider | e.g. `jamf` |
| external_id | Jamf `udid` (for future deep-linking) |
| attributes | JSON: model, make, ram_gb, storage_gb, processor, core_count, battery_percent |
| last_seen_at | from `general.lastContactTime` |
| missing_at | set when the device is absent from the latest Jamf payload |
| timestamps | created_at / updated_at |

### `sync_runs`
| column | notes |
|---|---|
| id | PK |
| provider | e.g. `jamf` |
| status | `success` / `failed` |
| created_assets / updated_assets / restored_assets | counters |
| skipped_unassigned / skipped_missing_serial | counters |
| started_at / finished_at | timestamps |
| error_message | nullable |

## 4. Sync Architecture

- `App\Contracts\MdmProvider` — `fetchDevices(): array<MdmDevice>`.
- `App\Data\MdmDevice` — normalized DTO (serial, email, name, phone, position, deviceName, externalId, attributes[], lastSeenAt).
- `App\Services\Mdm\Jamf\JamfProvider` — implements the contract, reads the mock JSON from a configurable path (`config('mdm.jamf.mock_path')`).
- `App\Services\Mdm\Jamf\JamfDeviceMapper` — maps raw Jamf payload -> `MdmDevice` using the reference's resilient fallback chains; unit-tested.
- `App\Services\Mdm\DeviceSyncService` — provider-agnostic:
  - Runs inside a DB transaction.
  - Skips devices with missing serial (`skipped_missing_serial`) or missing/invalid email (`skipped_unassigned`).
  - Upserts employee by email; updates `name`/`phone`/`position` only when the new value is non-null (no stale fallback).
  - Upserts asset by `serial_code`; sets `employee_id`, `provider`, `attributes`, `last_seen_at`.
  - **Change detection**: only count as `updated` when values actually changed; count recreations as `restored`.
  - Recreates previously deleted rows (works naturally with `firstOrNew` + hard delete).
  - **Unassignment**: if a device is present in the payload but now has no valid email, its existing local asset is unassigned (and removed from the assigned list) — an assignment change to "unassigned" must be reflected.
  - Persists a `sync_runs` report.
- Extension point: a new provider only needs to implement `MdmProvider` and be bound in the container — no change to `DeviceSyncService`.
- Documented assumption: assets present locally but absent from the payload get `missing_at` flagged (deactivated in Jamf) rather than hard-deleted.

## 5. HTTP API (Laravel)

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/sync` | Trigger sync, return run summary |
| GET | `/api/assets` | Paginated assets, search/sort, employee eager-loaded |
| GET | `/api/assets/{asset}` | Asset detail (name + attributes) for the Asset Details screen |
| DELETE | `/api/assets/{asset}` | Local delete |
| GET | `/api/employees` | Paginated employees with assigned-asset count |
| GET | `/api/employees/{employee}` | Employee detail (optional) |
| DELETE | `/api/employees/{employee}` | Local delete; 409 if the employee still owns assets |
| GET | `/api/sync-runs` | Recent sync history for UI feedback |

Assumption to document: deleting an employee who still owns assets returns `409 Conflict` to prevent orphaning; user must delete/reassign assets first (no manual reassign in scope).

## 6. Frontend (React + Vite + TypeScript)

- Routing: `Home`, `Assets` (Screen 1), `Employees`; Asset Details as a route/drawer (Screen 2).
- **Assets page**: heading + **Sync Now** button (top-right); list/table rows showing asset name and `Assigned To: <employee>`; per-row delete with confirm dialog; click row -> Asset Details.
- **Asset Details**: name box + attributes box (model, make, RAM, storage, processor, core count, battery, serial, provider).
- **Employees page**: list with name, email, phone, position, assigned asset count; per-row delete with confirm dialog (surfaces 409 when assets still assigned).
- `Sync Now` calls `POST /api/sync`, shows a summary (created/updated/skipped/restored), and refreshes the list.
- Typed API client, loading/error/empty states.
- Style with a lightweight setup (plain CSS / CSS modules) matching the wireframe's simple card/row layout.

## 7. Tests

- Feature tests per MDM rule:
  - Asset uniqueness by serial; employee uniqueness by email.
  - Unassigned device skipped; missing serial skipped.
  - Deleted asset/employee recreated on re-sync.
  - Attribute change (RAM 8 -> 16) reflected.
  - Reassignment (device moves to another employee) reflected.
  - Unassignment (device loses its email) removes it from the assigned list.
  - Deleting employee with assets -> 409.
  - Local delete only (no call back to Jamf).
- Unit tests for `JamfDeviceMapper` edge cases (missing keys, email casing/trim, RAM MB->GB, missing disks).
- Frontend: basic component test for list + delete flow (if time allows).

## 8. Docker

- `docker-compose.yml` services: `mysql:8`, `backend` (php-fpm + nginx, auto `migrate` on start via entrypoint), `frontend` (nginx serving built Vite app; proxy `/api` to backend in dev).
- Healthchecks + `depends_on` ordering so `docker compose up` works in one command.
- `.env` defaults suitable for local dev; no production hardening required.

## 9. Deliverables & Order

0. **Repository & access** (per README Goals): create a new repository from the template, grant access to the required GitHub reviewers (`@gumacs92`, `@omarsh99`), and push the project there.
1. Scaffold monorepo + Docker skeleton.
2. Migrations + models.
3. MDM abstraction (`MdmProvider`, DTO, Jamf provider + mapper).
4. `DeviceSyncService` with rules + change detection + `sync_runs`.
5. API controllers/routes/resources.
6. React pages/components + API client.
7. Feature/unit tests.
8. README: start instructions, API overview, architecture, **assumptions & known limitations**.

## 10. Assumptions & Known Limitations (to document in README)

- Employee delete is blocked while assets are assigned (409).
- Devices absent from the Jamf payload are flagged as missing, not hard-deleted (Jamf is source of truth but data is retained for audit).
- A device that is present but becomes unassigned is removed from the assigned list (deleted locally), consistent with "only assigned devices are imported".
- Storage is summed across disks; `null` when no disk data is provided.
- Mock JSON is read from disk instead of a live Jamf API (per assignment).
- Auth/authorization is out of scope.
- Hard deletes for local-only removals; no soft-delete/audit trail on assets/employees.
