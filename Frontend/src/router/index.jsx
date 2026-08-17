import { createBrowserRouter } from "react-router-dom"
import { AppLayout } from "@/components/layout/AppLayout"
import { ProtectedRoute } from "@/components/auth/ProtectedRoute"
import { DashboardPage } from "@/pages/DashboardPage"
import { NotFoundPage } from "@/pages/NotFoundPage"
import { PlaceholderPage } from "@/pages/PlaceholderPage"
import { PosPage } from "@/pages/PosPage"
import { LoginPage } from "@/pages/LoginPage"
import { CategoriesPage } from "@/pages/CategoriesPage"
import { CustomersPage } from "@/pages/CustomersPage"
import { OrdersPage } from "@/pages/OrdersPage"
import { ProductsPage } from "@/pages/ProductsPage"
import { ReportsPage } from "@/pages/ReportsPage"
import { InvoicePage } from "@/pages/InvoicePage"

export const router = createBrowserRouter([
  { path: "/login", element: <LoginPage /> },
  { element: <ProtectedRoute />, children: [
    { path: "/", element: <AppLayout />, errorElement: <NotFoundPage />, children: [
      { index: true, element: <DashboardPage /> },
      { path: "pos", element: <PosPage /> },
      { path: "products", element: <ProductsPage /> },
      { path: "categories", element: <CategoriesPage /> },
      { path: "customers", element: <CustomersPage /> },
      { path: "orders", element: <OrdersPage /> },
      { path: "orders/:orderId/invoice", element: <InvoicePage /> },
      { path: "reports", element: <ReportsPage /> },
      { path: "settings", element: <PlaceholderPage title="Settings" /> },
    ] },
  ] },
])
