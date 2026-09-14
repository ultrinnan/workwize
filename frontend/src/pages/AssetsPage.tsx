import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api, ApiError } from '../api/client'
import type { Asset, SyncRun } from '../api/types'
import Pagination from '../components/Pagination'
import SyncButton from '../components/SyncButton'

export default function AssetsPage() {
  const [assets, setAssets] = useState<Asset[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const response = await api.listAssets({ page, search })
      setAssets(response.data)
      setLastPage(response.meta.last_page)
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Unable to load assets.')
    } finally {
      setLoading(false)
    }
  }, [page, search])

  useEffect(() => {
    void load()
  }, [load])

  async function handleDelete(asset: Asset) {
    const confirmed = window.confirm(
      `Delete "${asset.device_name}"? It is removed from the local database only.`,
    )

    if (!confirmed) {
      return
    }

    try {
      await api.deleteAsset(asset.id)
      await load()
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Unable to delete asset.')
    }
  }

  function handleSynced(_run: SyncRun) {
    setPage(1)
    void load()
  }

  return (
    <section className="page">
      <header className="page__header">
        <h1>Asset Management</h1>
        <SyncButton onSynced={handleSynced} />
      </header>

      <input
        className="search"
        type="search"
        placeholder="Search by device, serial, model or employee"
        value={search}
        onChange={(event) => {
          setSearch(event.target.value)
          setPage(1)
        }}
      />

      {error && <p className="alert alert--error">{error}</p>}
      {loading && <p className="muted">Loading…</p>}

      {!loading && assets.length === 0 && (
        <p className="muted">
          No assets yet. Use <strong>Sync Now</strong> to import devices from Jamf.
        </p>
      )}

      <ul className="asset-list">
        {assets.map((asset) => (
          <li key={asset.id} className="asset-row">
            <Link className="asset-row__main" to={`/assets/${asset.id}`}>
              <span className="asset-row__name">{asset.device_name}</span>
              <span className="asset-row__meta">
                {asset.employee
                  ? `Assigned To: ${asset.employee.name ?? asset.employee.email}`
                  : 'Unassigned'}
              </span>
              <span className="asset-row__serial">{asset.serial_code}</span>
            </Link>
            <button
              type="button"
              className="button button--danger"
              onClick={() => void handleDelete(asset)}
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
