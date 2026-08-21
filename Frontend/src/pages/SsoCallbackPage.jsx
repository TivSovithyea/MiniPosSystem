import { useEffect, useRef, useState } from "react"
import { AlertTriangle } from "lucide-react"
import { apiClient } from "@/api/client"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"

export function SsoCallbackPage() {
  const params = new URLSearchParams(window.location.hash.slice(1))
  const token = params.get("token")
  const challenge = params.get("session_conflict")
  const handledToken = useRef(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState("")

  useEffect(() => {
    if (!token || handledToken.current) return
    handledToken.current = true
    localStorage.setItem("minipos_token", token)
    window.location.replace("/")
  }, [token])

  async function continueSession() {
    if (busy || !challenge) return
    setBusy(true)
    setError("")
    try {
      const response = await apiClient("/auth/session/continue", {
        method: "POST",
        body: JSON.stringify({ challenge }),
      })
      localStorage.setItem("minipos_token", response.token)
      window.location.replace("/")
    } catch (requestError) {
      setError(requestError.message)
      setBusy(false)
    }
  }

  if (token) return <main className="grid min-h-screen place-items-center bg-zinc-100 text-sm text-zinc-500">Completing SSO sign-in…</main>

  if (!challenge) {
    return <main className="grid min-h-screen place-items-center bg-zinc-100 p-5"><Card className="w-full max-w-md p-7 text-center"><p className="text-red-700">The SSO response is invalid or expired.</p><Button className="mt-5" onClick={() => window.location.replace("/login")}>Return to login</Button></Card></main>
  }

  return <main className="grid min-h-screen place-items-center bg-zinc-100 p-5">
    <Card className="w-full max-w-md p-7 text-center">
      <AlertTriangle className="mx-auto text-amber-500" size={48}/>
      <h1 className="mt-4 text-xl font-bold">User already signed in</h1>
      <p className="mt-2 text-sm text-zinc-500">This user is currently being used on another device. Do you want to continue and disconnect the other session?</p>
      {error && <p role="alert" className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error} Please return to login and try again.</p>}
      <div className="mt-6 grid grid-cols-2 gap-3">
        <Button type="button" variant="outline" disabled={busy} onClick={() => window.location.replace("/login")}>No</Button>
        <Button type="button" disabled={busy} onClick={continueSession}>{busy ? "Continuing…" : "Yes, continue"}</Button>
      </div>
    </Card>
  </main>
}
