import { apiClient, queryString } from "@/api/client"

function resource(path) {
  return {
    list: (params) => apiClient(`${path}${queryString(params)}`),
    get: (id) => apiClient(`${path}/${id}`),
    create: (data) => apiClient(path, { method: "POST", body: JSON.stringify(data) }),
    update: (id, data) => apiClient(`${path}/${id}`, { method: "PUT", body: JSON.stringify(data) }),
    remove: (id) => apiClient(`${path}/${id}`, { method: "DELETE" }),
  }
}

export const categoryService = resource("/categories")
export const productService = {
  ...resource("/products"),
  create: (data) => apiClient("/products", { method: "POST", body: data }),
  update: (id, data) => {
    data.append("_method", "PUT")
    return apiClient(`/products/${id}`, { method: "POST", body: data })
  },
}
export const customerService = resource("/customers")
export const userService = resource("/users")
export const orderService = {
  list: (params) => apiClient(`/orders${queryString(params)}`),
  get: (id) => apiClient(`/orders/${id}`),
  create: (data) => apiClient("/orders", { method: "POST", body: JSON.stringify(data) }),
  cancel: (id) => apiClient(`/orders/${id}/cancel`, { method: "PATCH" }),
}
export const paywayPaymentService = {
  get: (orderId) => apiClient(`/orders/${orderId}/payway-payment`),
  simulate: (orderId) => apiClient(`/orders/${orderId}/payway-payment/simulate`, { method: "POST" }),
}
export const dashboardService = { get: () => apiClient("/dashboard") }
export const reportService = {
  summary: (params) => apiClient(`/reports/summary${queryString(params)}`),
  products: (params) => apiClient(`/reports/products${queryString(params)}`),
  customers: (params) => apiClient(`/reports/customers${queryString(params)}`),
}
