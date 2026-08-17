import { useEffect } from "react"
import { Navigate, Outlet, useLocation } from "react-router-dom"
import { useAppDispatch, useAppSelector } from "@/hooks/redux"
import { loadUser, sessionExpired } from "@/redux/slices/authSlice"

export function ProtectedRoute() {
  const dispatch = useAppDispatch()
  const { token, user, loading } = useAppSelector((state) => state.auth)
  const location = useLocation()

  useEffect(() => {
    if (token && !user) void dispatch(loadUser())
  }, [dispatch, token, user])

  useEffect(() => {
    const handleUnauthorized = () => dispatch(sessionExpired())
    window.addEventListener("minipos:unauthorized", handleUnauthorized)
    return () => window.removeEventListener("minipos:unauthorized", handleUnauthorized)
  }, [dispatch])

  if (token && !user && loading) return <div className="grid min-h-screen place-items-center bg-zinc-100 text-sm text-zinc-500">Loading your session…</div>
  if (!token) return <Navigate to="/login" replace state={{ from: location }} />
  if (!user) return null

  return <Outlet />
}
