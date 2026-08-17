import { useEffect, useState } from "react"
import { ArrowUpRight, Banknote, Package, Receipt, Users } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { dashboardService } from "@/services/apiServices"
import { ErrorMessage, LoadingState } from "@/components/PageStates"

export function DashboardPage() {
  const [data, setData] = useState(null), [error, setError] = useState("")
  useEffect(() => { dashboardService.get().then(setData).catch((e) => setError(e.message)) }, [])
  if (!data && !error) return <LoadingState/>
  const metrics = data ? [
    ["Today's sales", `$${Number(data.today.sales).toFixed(2)}`, Banknote, "bg-emerald-100 text-emerald-700"], ["Today's orders", data.today.orders, Receipt, "bg-blue-100 text-blue-700"],
    ["Customers", data.customers, Users, "bg-violet-100 text-violet-700"], ["Low stock", data.low_stock, Package, "bg-amber-100 text-amber-700"],
  ] : []
  return <div className="mx-auto max-w-7xl space-y-7"><div><p className="text-sm font-medium text-emerald-700">{new Intl.DateTimeFormat(undefined, { dateStyle: "full" }).format(new Date())}</p><h1 className="mt-1 text-3xl font-bold">Store overview</h1><p className="mt-2 text-zinc-500">Live activity from your MiniPOS API.</p></div><ErrorMessage message={error}/><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{metrics.map(([label, value, Icon, color]) => <Card key={label}><CardContent className="p-5"><div className="flex justify-between"><div><p className="text-sm text-zinc-500">{label}</p><p className="mt-2 text-2xl font-bold">{value}</p></div><span className={`grid size-10 place-items-center rounded-xl ${color}`}><Icon size={20}/></span></div></CardContent></Card>)}</div>
    {data && <div className="grid gap-5 xl:grid-cols-2"><Card><CardHeader><CardTitle>Recent orders</CardTitle></CardHeader><CardContent className="space-y-3">{data.recent_orders.length ? data.recent_orders.map((o) => <div key={o.id} className="flex justify-between rounded-xl bg-zinc-50 p-3"><div><p className="text-sm font-semibold">{o.order_number}</p><p className="text-xs text-zinc-500">{o.customer?.name ?? "Walk-in"}</p></div><div className="text-right"><p className="font-bold">${Number(o.total).toFixed(2)}</p><p className="text-xs capitalize text-zinc-500">{o.status}</p></div></div>) : <p className="text-sm text-zinc-500">No orders yet.</p>}</CardContent></Card><Card><CardHeader><CardTitle>Popular products</CardTitle></CardHeader><CardContent className="space-y-3">{data.popular_products.length ? data.popular_products.map((i) => <div key={i.product_id} className="flex justify-between rounded-xl bg-zinc-50 p-3"><p className="text-sm font-semibold">{i.product_name}</p><p className="flex items-center gap-1 text-sm text-emerald-700"><ArrowUpRight size={15}/>{i.quantity_sold} sold</p></div>) : <p className="text-sm text-zinc-500">Sales will appear after checkout.</p>}</CardContent></Card></div>}
  </div>
}
