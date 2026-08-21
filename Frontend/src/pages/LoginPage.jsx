import { useEffect, useState } from "react"
import { Navigate, useLocation, useNavigate } from "react-router-dom"
import { ShieldAlert, Store } from "lucide-react"
import { Modal } from "@/components/Modal"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { useAppDispatch, useAppSelector } from "@/hooks/redux"
import { clearAuthError, login } from "@/redux/slices/authSlice"

export function LoginPage() {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const location = useLocation()
  const { token, user, loading, error } = useAppSelector((state) => state.auth)
  const [email, setEmail] = useState("admin@minipos.test")
  const [password, setPassword] = useState("password")
  const [showSessionConflict, setShowSessionConflict] = useState(false)
  const from = location.state?.from?.pathname ?? "/"

  useEffect(() => () => { dispatch(clearAuthError()) }, [dispatch])

  if (token && user) return <Navigate to="/" replace />

  async function handleSubmit(event) {
    event.preventDefault()
    const result = await dispatch(login({ email, password }))
    if (login.rejected.match(result) && result.payload?.code === "SESSION_CONFLICT") {
      dispatch(clearAuthError())
      setShowSessionConflict(true)
      return
    }
    if (login.fulfilled.match(result)) navigate(from, { replace: true })
  }

  async function replaceExistingSession() {
    const result = await dispatch(login({ email, password, force_session: true }))
    if (login.fulfilled.match(result)) {
      setShowSessionConflict(false)
      navigate(from, { replace: true })
    }
  }

  return <main className="grid min-h-screen place-items-center bg-zinc-100 p-5">
    <Card className="w-full max-w-md p-7">
      <div className="mb-7 flex items-center gap-3"><span className="grid size-11 place-items-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-200"><Store size={22}/></span><div><h1 className="text-xl font-bold">Sign in to MiniPOS</h1><p className="text-sm text-zinc-500">Manage your store securely</p></div></div>
      <form className="space-y-4" onSubmit={handleSubmit}>
        <label className="block text-sm font-medium">Email<Input className="mt-1.5" type="email" value={email} onChange={(event) => setEmail(event.target.value)} required autoComplete="email" /></label>
        <label className="block text-sm font-medium">Password<Input className="mt-1.5" type="password" value={password} onChange={(event) => setPassword(event.target.value)} required autoComplete="current-password" /></label>
        {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
        <Button className="w-full" disabled={loading}>{loading ? "Signing in…" : "Sign in"}</Button>
      </form>
      {/* Keycloak SSO is temporarily hidden while deploying the normal login version first. */}
      <p className="mt-5 text-center text-xs text-zinc-400">Demo: admin@minipos.test / password</p>
    </Card>
    <Modal title="Another session is active" open={showSessionConflict} onClose={() => setShowSessionConflict(false)}>
      <div className="flex gap-4">
        <span className="grid size-11 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700"><ShieldAlert size={22}/></span>
        <div>
          <p className="font-medium text-zinc-900">This account is signed in on another device.</p>
          <p className="mt-1 text-sm leading-6 text-zinc-500">Continuing will sign out the other device and move the active session here.</p>
        </div>
      </div>
      {error && <p role="alert" className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button type="button" variant="outline" disabled={loading} onClick={() => setShowSessionConflict(false)}>Stay signed out</Button>
        <Button type="button" disabled={loading} onClick={replaceExistingSession}>{loading ? "Switching session…" : "Continue on this device"}</Button>
      </div>
    </Modal>
  </main>
}
