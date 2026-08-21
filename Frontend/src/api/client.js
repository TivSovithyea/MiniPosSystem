const API_URL = import.meta.env.VITE_API_URL ?? "https://minipossystem.onrender.com/api"
export const apiUrl = (path = "") => `${API_URL}${path}`

export class ApiError extends Error {
  constructor(message, status, errors = {}, code = null) {
    super(message)
    this.name = "ApiError"
    this.status = status
    this.errors = errors
    this.code = code
  }
}

export function queryString(params = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== "" && value !== undefined && value !== null) query.set(key, value)
  })
  const result = query.toString()
  return result ? `?${result}` : ""
}

export async function apiClient(path, options = {}) {
  const token = localStorage.getItem("minipos_token")
  const isFormData = options.body instanceof FormData
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      "Accept": "application/json",
      ...(!isFormData ? { "Content-Type": "application/json" } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })
  if (!response.ok) {
    const body = await response.json().catch(() => null)
    if (response.status === 401 && token) {
      localStorage.removeItem("minipos_token")
      window.dispatchEvent(new Event("minipos:unauthorized"))
    }
    const validationMessage = body?.errors ? Object.values(body.errors).flat()[0] : null
    throw new ApiError(validationMessage ?? body?.message ?? `Request failed: ${response.status}`, response.status, body?.errors, body?.code)
  }
  if (response.status === 204) return undefined
  return response.json()
}
