import { useEffect } from "react"
import { Navigate, Outlet, useLocation } from "react-router-dom"
import { useAppDispatch, useAppSelector } from "@/hooks/redux"
import { loadUser, sessionExpired } from "@/redux/slices/authSlice"

const SESSION_CHECK_INTERVAL = Math.max(Number(import.meta.env.VITE_SESSION_CHECK_INTERVAL_MS) || 3000, 1000)

export function ProtectedRoute() {
  const dispatch = useAppDispatch()
  const { token, user, loading } = useAppSelector((state) => state.auth)
  const location = useLocation()
  const hasUser = Boolean(user)

  useEffect(() => {
    if (token && !hasUser) void dispatch(loadUser())
  }, [dispatch, token, hasUser])

  useEffect(() => {
    if (!token || !hasUser) return
    const verifySession = () => { void dispatch(loadUser()) }
    const checkVisibleSession = () => { if (document.visibilityState === "visible") verifySession() }
    const interval = window.setInterval(verifySession, SESSION_CHECK_INTERVAL)
    window.addEventListener("focus", verifySession)
    document.addEventListener("visibilitychange", checkVisibleSession)
    return () => {
      window.clearInterval(interval)
      window.removeEventListener("focus", verifySession)
      document.removeEventListener("visibilitychange", checkVisibleSession)
    }
  }, [dispatch, token, hasUser])

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
