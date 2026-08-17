import { X } from "lucide-react"

export function Modal({ title, open, onClose, children, wide = false }) {
  if (!open) return null
  return <div className="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-black/50 p-0 sm:grid sm:place-items-center sm:p-4" role="dialog" aria-modal="true" aria-label={title} onMouseDown={(event) => event.target === event.currentTarget && onClose()}>
    <div className={`max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 shadow-xl sm:rounded-2xl sm:p-6 ${wide ? "sm:max-w-2xl" : "sm:max-w-lg"}`}>
      <div className="mb-4 flex items-center justify-between sm:mb-5"><h2 className="text-lg font-bold sm:text-xl">{title}</h2><button type="button" className="grid size-10 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100" onClick={onClose}><X size={20}/></button></div>
      {children}
    </div>
  </div>
}
