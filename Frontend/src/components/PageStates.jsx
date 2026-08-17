export function LoadingState() {
  return <div className="grid min-h-52 place-items-center text-sm text-zinc-500">Loading…</div>
}

export function ErrorMessage({ message }) {
  return message ? <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{message}</p> : null
}

export function EmptyState({ children = "No records found." }) {
  return <div className="py-14 text-center text-sm text-zinc-500">{children}</div>
}
