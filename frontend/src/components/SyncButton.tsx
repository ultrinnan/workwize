import { useState } from 'react'
import { api, ApiError } from '../api/client'
import type { SyncRun } from '../api/types'

interface SyncButtonProps {
  onSynced: (run: SyncRun) => void
}

export default function SyncButton({ onSynced }: SyncButtonProps) {
  const [syncing, setSyncing] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  async function handleClick() {
    setSyncing(true)
    setError(null)
    setMessage(null)

    try {
      const { data } = await api.sync()
      setMessage(
        `Synced ${data.total_devices} devices — ${data.created_assets} created, ` +
          `${data.updated_assets} updated, ${data.restored_assets} restored, ` +
          `${data.skipped_unassigned} unassigned skipped.`,
      )
      onSynced(data)
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Sync failed.')
    } finally {
      setSyncing(false)
    }
  }

  return (
    <div className="sync">
      <button
        type="button"
        className="button button--primary"
        onClick={() => void handleClick()}
        disabled={syncing}
      >
        {syncing ? 'Syncing…' : 'Sync Now'}
      </button>
      {message && <p className="sync__message">{message}</p>}
      {error && <p className="alert alert--error">{error}</p>}
    </div>
  )
}
