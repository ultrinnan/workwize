import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api, ApiError } from '../api/client'
import type { Asset } from '../api/types'

const ATTRIBUTE_LABELS: Record<string, string> = {
  make: 'Brand',
  model: 'Model',
  model_identifier: 'Model Identifier',
  platform: 'Platform',
  processor: 'Processor',
  core_count: 'CPU Cores',
  ram_gb: 'RAM (GB)',
  storage_gb: 'Storage (GB)',
  battery_percent: 'Battery (%)',
}

const ATTRIBUTE_ORDER = Object.keys(ATTRIBUTE_LABELS)

function formatValue(value: string | number | null): string {
  if (value === null || value === '') {
    return '—'
  }

  return String(value)
}

export default function AssetDetailPage() {
  const { assetId } = useParams()
  const [asset, setAsset] = useState<Asset | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!assetId) {
      return
    }

    let active = true

    setLoading(true)
    setError(null)

    api
      .getAsset(Number(assetId))
      .then((response) => {
        if (active) {
          setAsset(response.data)
        }
      })
      .catch((caught) => {
        if (active) {
          setError(caught instanceof ApiError ? caught.message : 'Unable to load asset.')
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false)
        }
      })

    return () => {
      active = false
    }
  }, [assetId])

  const attributeKeys = asset
    ? [
        ...ATTRIBUTE_ORDER.filter((key) => key in asset.attributes),
        ...Object.keys(asset.attributes).filter((key) => !ATTRIBUTE_ORDER.includes(key)),
      ]
    : []

  return (
    <section className="page">
      <header className="page__header">
        <h1>Asset Details</h1>
        <Link className="button" to="/assets">
          Back to assets
        </Link>
      </header>

      {loading && <p className="muted">Loading…</p>}
      {error && <p className="alert alert--error">{error}</p>}

      {asset && (
        <>
          <div className="card asset-name">
            <span className="asset-name__label">Asset Name</span>
            <strong className="asset-name__value">{asset.device_name}</strong>
            <span className="asset-name__meta">
              Serial: {asset.serial_code} · Provider: {asset.provider}
            </span>
          </div>

          <div className="card">
            <h2>Assigned To</h2>
            {asset.employee ? (
              <p>
                {asset.employee.name ?? 'Unknown'}{' '}
                <span className="muted">({asset.employee.email})</span>
              </p>
            ) : (
              <p className="muted">Unassigned</p>
            )}
          </div>

          <div className="card">
            <h2>Asset Attributes</h2>
            <dl className="attributes">
              {attributeKeys.map((key) => (
                <div className="attributes__row" key={key}>
                  <dt>{ATTRIBUTE_LABELS[key] ?? key}</dt>
                  <dd>{formatValue(asset.attributes[key])}</dd>
                </div>
              ))}
            </dl>
            {asset.missing_at && (
              <p className="alert alert--warning">
                This device was not present in the latest Jamf sync.
              </p>
            )}
          </div>
        </>
      )}
    </section>
  )
}
