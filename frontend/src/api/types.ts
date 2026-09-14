export interface Employee {
  id: number
  email: string
  name: string | null
  phone: string | null
  position: string | null
  assets_count?: number
  assets?: Asset[]
  created_at: string | null
  updated_at: string | null
}

export type AssetAttributes = Record<string, string | number | null>

export interface Asset {
  id: number
  serial_code: string
  device_name: string
  provider: string
  external_id: string | null
  attributes: AssetAttributes
  last_seen_at: string | null
  missing_at: string | null
  employee?: Employee | null
  created_at: string | null
  updated_at: string | null
}

export interface SyncRun {
  id: number
  provider: string
  status: string
  total_devices: number
  created_assets: number
  updated_assets: number
  restored_assets: number
  unassigned_assets: number
  missing_assets: number
  skipped_unassigned: number
  skipped_missing_serial: number
  started_at: string | null
  finished_at: string | null
  error_message: string | null
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}
