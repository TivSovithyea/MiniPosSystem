import { cn } from "@/utils/cn"

export function Card({ className, ...props }) {
  return <div className={cn("rounded-2xl border border-zinc-200 bg-white shadow-sm", className)} {...props} />
}
export function CardHeader({ className, ...props }) {
  return <div className={cn("flex flex-col gap-1.5 p-5", className)} {...props} />
}
export function CardTitle({ className, ...props }) {
  return <h3 className={cn("font-semibold tracking-tight", className)} {...props} />
}
export function CardContent({ className, ...props }) {
  return <div className={cn("p-5 pt-0", className)} {...props} />
}
