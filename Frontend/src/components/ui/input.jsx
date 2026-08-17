import { cn } from "@/utils/cn"

export function Input({ className, ...props }) {
  return <input className={cn("h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100", className)} {...props} />
}
