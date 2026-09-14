import type { Asset, Employee, Paginated, SyncRun } from './types'

const API_BASE = (import.meta.env.VITE_API_URL as string | undefined) ?? '/api'

export class ApiError extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}${path}`, {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(init.headers ?? {}),
    },
  })

  if (!response.ok) {
    let message = `Request failed (${response.status})`
    try {
      const body = (await response.json()) as { message?: string }
      if (body.message) {
        message = body.message
      }
    } catch {
      // Non-JSON error body, keep the default message.
    }
    throw new ApiError(message, response.status)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export interface ListParams {
  search?: string
  page?: number
  sort?: string
  direction?: 'asc' | 'desc'
  perPage?: number
}

function toQuery(params: ListParams): string {
  const query = new URLSearchParams()
  if (params.search) query.set('search', params.search)
  if (params.page) query.set('page', String(params.page))
  if (params.sort) query.set('sort', params.sort)
  if (params.direction) query.set('direction', params.direction)
  if (params.perPage) query.set('per_page', String(params.perPage))

  const value = query.toString()

  return value ? `?${value}` : ''
}

export const api = {
  listAssets: (params: ListParams = {}) =>
    request<Paginated<Asset>>(`/assets${toQuery(params)}`),

  getAsset: (id: number) => request<{ data: Asset }>(`/assets/${id}`),

  deleteAsset: (id: number) => request<void>(`/assets/${id}`, { method: 'DELETE' }),

  listEmployees: (params: ListParams = {}) =>
    request<Paginated<Employee>>(`/employees${toQuery(params)}`),

  deleteEmployee: (id: number) =>
    request<void>(`/employees/${id}`, { method: 'DELETE' }),

  sync: () => request<{ data: SyncRun }>('/sync', { method: 'POST' }),

  listSyncRuns: () => request<{ data: SyncRun[] }>('/sync-runs'),
}
