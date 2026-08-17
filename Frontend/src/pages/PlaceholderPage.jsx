import { Card } from "@/components/ui/card"

export function PlaceholderPage({ title }) {
  return <div className="max-w-5xl"><h1 className="text-3xl font-bold">{title}</h1><p className="mt-2 text-zinc-500">This module is ready for your business requirements.</p><Card className="mt-6 grid min-h-72 place-items-center p-8 text-center text-zinc-400">Build your {title.toLowerCase()} workflow here.</Card></div>
}
