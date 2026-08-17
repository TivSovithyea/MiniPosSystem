import { Link } from "react-router-dom"
import { Button } from "@/components/ui/button"

export function NotFoundPage() { return <div className="grid min-h-[70vh] place-items-center text-center"><div><p className="text-7xl font-black text-emerald-600">404</p><h1 className="mt-4 text-2xl font-bold">Page not found</h1><p className="mt-2 text-zinc-500">The page you requested doesn’t exist.</p><Button asChild className="mt-6"><Link to="/">Back to dashboard</Link></Button></div></div> }
