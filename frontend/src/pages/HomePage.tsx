import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api/client'
import type { SyncRun } from '../api/types'

export default function HomePage() {
  const [lastRun, setLastRun] = useState<SyncRun | null>(null)

  useEffect(() => {
    let active = true

    api
      .listSyncRuns()
      .then(({ data }) => {
        if (active) {
          setLastRun(data[0] ?? null)
        }
      })
      .catch(() => {
        // Home page tolerates a missing sync history.
      })

    return () => {
      active = false
    }
  }, [])

  return (
    <section className="page">
      <header className="page__header">
        <h1>WorkWize MDM Device Sync</h1>
      </header>

      <div className="card">
        <p>
          Import assigned devices from Jamf into WorkWize. Only devices that
          have an assigned employee are imported; employees are matched by
          email and assets by serial number.
        </p>
        <p className="muted">
          Use <strong>Assets → Sync Now</strong> to pull the latest devices.
        </p>
        <div className="actions">
          <Link className="button button--primary" to="/assets">
            Go to Assets
          </Link>
          <Link className="button" to="/employees">
            Go to Employees
          </Link>
        </div>
      </div>

      {lastRun && (
        <div className="card">
          <h2>Last sync</h2>
          <p className="muted">
            {lastRun.provider} · {lastRun.status} ·{' '}
            {lastRun.finished_at
              ? new Date(lastRun.finished_at).toLocaleString()
              : 'in progress'}
          </p>
          <ul className="stats">
            <li>
              <span>{lastRun.total_devices}</span> devices
            </li>
            <li>
              <span>{lastRun.created_assets}</span> created
            </li>
            <li>
              <span>{lastRun.updated_assets}</span> updated
            </li>
            <li>
              <span>{lastRun.skipped_unassigned}</span> unassigned skipped
            </li>
          </ul>
        </div>
      )}
    </section>
  )
}
