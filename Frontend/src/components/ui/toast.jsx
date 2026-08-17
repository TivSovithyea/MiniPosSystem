import { useCallback, useMemo, useState } from "react"
import { AlertCircle, CheckCircle2, Info, X } from "lucide-react"
import { ToastContext } from "@/hooks/useToast"

const styles = {
  success: { icon: CheckCircle2, className: "border-emerald-200 bg-emerald-50 text-emerald-900" },
  error: { icon: AlertCircle, className: "border-red-200 bg-red-50 text-red-900" },
  info: { icon: Info, className: "border-indigo-200 bg-indigo-50 text-indigo-900" },
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const dismiss = useCallback((id) => setToasts((current) => current.filter((toast) => toast.id !== id)), [])
  const show = useCallback((message, type = "info", title) => {
    const id = crypto.randomUUID()
    setToasts((current) => [...current.slice(-3), { id, message, type, title }])
    window.setTimeout(() => dismiss(id), 4000)
    return id
  }, [dismiss])
  const value = useMemo(() => ({ show, success: (message, title) => show(message, "success", title), error: (message, title) => show(message, "error", title), info: (message, title) => show(message, "info", title) }), [show])
  return <ToastContext.Provider value={value}>{children}<div className="fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3" aria-live="polite">{toasts.map((toast) => { const style = styles[toast.type] ?? styles.info; const Icon = style.icon; return <div key={toast.id} className={`flex items-start gap-3 rounded-xl border p-4 shadow-lg backdrop-blur ${style.className}`}><Icon className="mt-0.5 shrink-0" size={19}/><div className="min-w-0 flex-1">{toast.title && <p className="text-sm font-bold">{toast.title}</p>}<p className="text-sm leading-5">{toast.message}</p></div><button onClick={() => dismiss(toast.id)} className="rounded p-0.5 opacity-60 hover:opacity-100" aria-label="Dismiss notification"><X size={16}/></button></div> })}</div></ToastContext.Provider>
}
