import { Slot } from "@radix-ui/react-slot"
import { cva } from "class-variance-authority"
import { cn } from "@/utils/cn"

const buttonVariants = cva(
  "inline-flex min-h-10 touch-manipulation items-center justify-center gap-2 rounded-lg text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-indigo-600 text-white shadow-sm hover:bg-indigo-700",
        outline: "border border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50",
        ghost: "text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950",
      },
      size: { default: "h-10 px-4 py-2", sm: "h-9 px-3", icon: "size-10" },
    },
    defaultVariants: { variant: "default", size: "default" },
  },
)

export function Button({ className, variant, size, asChild, ...props }) {
  const Comp = asChild ? Slot : "button"
  return <Comp className={cn(buttonVariants({ variant, size }), className)} {...props} />
}
