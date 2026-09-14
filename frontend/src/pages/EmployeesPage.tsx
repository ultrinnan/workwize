import { useCallback, useEffect, useState } from 'react'
import { api, ApiError } from '../api/client'
import type { Employee } from '../api/types'
import Pagination from '../components/Pagination'

export default function EmployeesPage() {
  const [employees, setEmployees] = useState<Employee[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.listEmployees({ page, search })
      setEmployees(response.data)
      setLastPage(response.meta.last_page)
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Unable to load employees.')
    } finally {
      setLoading(false)
    }
  }, [page, search])

  useEffect(() => {
    void load()
  }, [load])

  async function handleDelete(employee: Employee) {
    const confirmed = window.confirm(
      `Delete "${employee.name ?? employee.email}"? It is removed from the local database only.`,
    )

    if (!confirmed) {
      return
    }

    try {
      await api.deleteEmployee(employee.id)
      await load()
    } catch (caught) {
      setError(
        caught instanceof ApiError ? caught.message : 'Unable to delete employee.',
      )
    }
  }

  return (
    <section className="page">
      <header className="page__header">
        <h1>Employees</h1>
      </header>

      <input
        className="search"
        type="search"
        placeholder="Search by name, email or position"
        value={search}
        onChange={(event) => {
          setSearch(event.target.value)
          setPage(1)
        }}
      />

      {error && <p className="alert alert--error">{error}</p>}
      {loading && <p className="muted">Loading…</p>}

      {!loading && employees.length === 0 && <p className="muted">No employees yet.</p>}

      <ul className="asset-list">
        {employees.map((employee) => (
          <li key={employee.id} className="asset-row">
            <div className="asset-row__main">
              <span className="asset-row__name">{employee.name ?? 'Unknown'}</span>
              <span className="asset-row__meta">{employee.email}</span>
              <span className="asset-row__serial">
                {employee.position ?? '—'} · {employee.assets_count ?? 0} asset
                {(employee.assets_count ?? 0) === 1 ? '' : 's'}
              </span>
            </div>
            <button
              type="button"
              className="button button--danger"
              onClick={() => void handleDelete(employee)}
            >
              Delete
            </button>
          </li>
        ))}
      </ul>

      <Pagination current={page} last={lastPage} onChange={setPage} />
    </section>
  )
}
