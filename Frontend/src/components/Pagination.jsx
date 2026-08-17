import { ChevronLeft, ChevronRight } from "lucide-react"
import { Button } from "@/components/ui/button"

export function Pagination({ meta, onPageChange }) {
  if (!meta || meta.last_page <= 1) return null
  return <div className="flex flex-col gap-3 border-t border-zinc-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
    <p className="text-center text-xs text-zinc-500 sm:text-left sm:text-sm">Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}</p>
    <div className="flex items-center justify-center gap-1 sm:gap-2">
      <Button variant="outline" size="sm" className="px-2 sm:px-3" disabled={meta.current_page <= 1} onClick={() => onPageChange(meta.current_page - 1)}><ChevronLeft size={16}/><span className="hidden min-[400px]:inline">Previous</span></Button>
      <span className="px-1 text-xs sm:px-2 sm:text-sm">{meta.current_page} / {meta.last_page}</span>
      <Button variant="outline" size="sm" className="px-2 sm:px-3" disabled={meta.current_page >= meta.last_page} onClick={() => onPageChange(meta.current_page + 1)}><span className="hidden min-[400px]:inline">Next</span><ChevronRight size={16}/></Button>
    </div>
  </div>
}
