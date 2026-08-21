import { useEffect, useState } from "react"
import { Navigate, useLocation, useNavigate } from "react-router-dom"
import { Store } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { useAppDispatch, useAppSelector } from "@/hooks/redux"
import { clearAuthError, login } from "@/redux/slices/authSlice"
import { apiUrl } from "@/api/client"

export function LoginPage() {
  const keycloakEnabled = import.meta.env.VITE_KEYCLOAK_ENABLED === "true"
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const location = useLocation()
  const { token, user, loading, error } = useAppSelector((state) => state.auth)
  const [email, setEmail] = useState("admin@minipos.test")
  const [password, setPassword] = useState("password")
  const from = location.state?.from?.pathname ?? "/"

  useEffect(() => () => { dispatch(clearAuthError()) }, [dispatch])

  if (token && user) return <Navigate to="/" replace />

  async function handleSubmit(event) {
    event.preventDefault()
    let result = await dispatch(login({ email, password }))
    if (login.rejected.match(result) && result.payload?.code === "SESSION_CONFLICT") {
      const shouldContinue = window.confirm("This user is currently being used on another device. Do you want to continue and disconnect the other session?")
      if (!shouldContinue) return
      result = await dispatch(login({ email, password, force_session: true }))
    }
    if (login.fulfilled.match(result)) navigate(from, { replace: true })
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
      {keycloakEnabled && <><div className="my-5 flex items-center gap-3 text-xs text-zinc-400"><span className="h-px flex-1 bg-zinc-200"/>or<span className="h-px flex-1 bg-zinc-200"/></div><Button type="button" variant="outline" className="w-full" onClick={() => { window.location.href = apiUrl('/auth/keycloak/redirect') }}>Sign in with Keycloak SSO</Button></>}
      <p className="mt-5 text-center text-xs text-zinc-400">Demo: admin@minipos.test / password</p>
    </Card>
  </main>
}
