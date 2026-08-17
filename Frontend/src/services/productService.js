import { apiClient } from "@/api/client"

export const productService = {
    list: () => apiClient("/products")
}
