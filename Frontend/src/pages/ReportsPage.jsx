import { useCallback, useEffect, useState } from "react"
import { Banknote, Boxes, Receipt, ShoppingBasket } from "lucide-react"
import { orderService, reportService } from "@/services/apiServices"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { EmptyState, ErrorMessage, LoadingState } from "@/components/PageStates"
import { Pagination } from "@/components/Pagination"

const tabs = [["summary", "Order summary"], ["orders", "Orders"], ["products", "Product orders"], ["customers", "Customer orders"]]
const money = (value) => `$${Number(value ?? 0).toFixed(2)}`

export function ReportsPage() {
  const [tab, setTab] = useState("summary"), [from, setFrom] = useState(""), [to, setTo] = useState(""), [search, setSearch] = useState(""), [page, setPage] = useState(1)
  const [data, setData] = useState(null), [loading, setLoading] = useState(true), [error, setError] = useState("")
  const load = useCallback(async () => { setLoading(true); setError(""); try { const params = { date_from: from, date_to: to, search, page, per_page: 10 }; setData(tab === "summary" ? await reportService.summary(params) : tab === "orders" ? await orderService.list(params) : await reportService[tab](params)) } catch (e) { setError(e.message) } finally { setLoading(false) } }, [tab, from, to, search, page])
  useEffect(() => { const id = setTimeout(load, 200); return () => clearTimeout(id) }, [load])
  function switchTab(value) { setTab(value); setPage(1); setSearch(""); setData(null) }
  return <div className="mx-auto max-w-7xl space-y-5"><div><h1 className="text-3xl font-bold">Reports</h1><p className="mt-1 text-zinc-500">Analyze orders, products, and customer sales.</p></div>
    <div className="flex flex-wrap gap-2">{tabs.map(([value, label]) => <Button key={value} variant={tab === value ? "default" : "outline"} onClick={() => switchTab(value)}>{label}</Button>)}</div>
    <Card className="p-5"><div className="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><label className="text-xs font-medium text-zinc-500">From<Input className="mt-1" type="date" value={from} onChange={(e) => { setFrom(e.target.value); setPage(1) }}/></label><label className="text-xs font-medium text-zinc-500">To<Input className="mt-1" type="date" value={to} min={from} onChange={(e) => { setTo(e.target.value); setPage(1) }}/></label>{(tab === "products" || tab === "customers") && <label className="text-xs font-medium text-zinc-500 sm:col-span-2">Search<Input className="mt-1" placeholder={`Search ${tab}…`} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1) }}/></label>}</div><ErrorMessage message={error}/>{loading || !data ? <LoadingState/> : <ReportContent tab={tab} data={data} onPageChange={setPage}/>}</Card>
  </div>
}

function ReportContent({ tab, data, onPageChange }) {
  if (tab === "summary") return <Summary data={data}/>
  if (!data?.data?.length) return <EmptyState>No report data for this period.</EmptyState>
  if (tab === "orders") return <><ReportTable headers={["Order", "Customer", "Date", "Payment", "Status", "Total"]}>{data.data.map((o) => <tr key={o.id} className="border-b border-zinc-100"><Cell strong>{o.order_number}</Cell><Cell>{o.customer?.name ?? "Walk-in"}</Cell><Cell>{new Date(o.ordered_at).toLocaleDateString()}</Cell><Cell>{o.payment_method.toUpperCase()}</Cell><Cell capitalize>{o.status}</Cell><Cell strong>{money(o.total)}</Cell></tr>)}</ReportTable><Pagination meta={data} onPageChange={onPageChange}/></>
  if (tab === "products") return <><ReportTable headers={["Product", "SKU", "Category", "Orders", "Quantity sold", "Revenue"]}>{data.data.map((p) => <tr key={p.id} className="border-b border-zinc-100"><Cell strong>{p.name}</Cell><Cell>{p.sku}</Cell><Cell>{p.category_name}</Cell><Cell>{p.orders_count}</Cell><Cell>{p.quantity_sold}</Cell><Cell strong>{money(p.revenue)}</Cell></tr>)}</ReportTable><Pagination meta={data} onPageChange={onPageChange}/></>
  return <><ReportTable headers={["Customer", "Contact", "Orders", "Average order", "Last order", "Revenue"]}>{data.data.map((c) => <tr key={c.id ?? "walk-in"} className="border-b border-zinc-100"><Cell strong>{c.name}</Cell><Cell>{c.email || c.phone || "—"}</Cell><Cell>{c.orders_count}</Cell><Cell>{money(c.average_order)}</Cell><Cell>{new Date(c.last_order_at).toLocaleDateString()}</Cell><Cell strong>{money(c.revenue)}</Cell></tr>)}</ReportTable><Pagination meta={data} onPageChange={onPageChange}/></>
}

function Summary({ data }) {
  const cards = [["Revenue", money(data.totals.revenue), Banknote], ["Orders", data.totals.orders_count, Receipt], ["Items sold", data.totals.items_sold, ShoppingBasket], ["Average order", money(data.totals.average_order), Boxes]]
  return <div className="space-y-6"><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map(([label, value, Icon]) => <Card key={label}><CardContent className="flex items-center justify-between p-4"><div><p className="text-sm text-zinc-500">{label}</p><p className="mt-1 text-2xl font-bold">{value}</p></div><Icon className="text-emerald-600"/></CardContent></Card>)}</div><div className="grid gap-5 lg:grid-cols-2"><div><h3 className="mb-3 font-bold">Payment methods</h3>{data.payment_methods.length ? <ReportTable headers={["Method", "Orders", "Revenue"]}>{data.payment_methods.map((p) => <tr key={p.payment_method} className="border-b"><Cell>{p.payment_method.toUpperCase()}</Cell><Cell>{p.orders_count}</Cell><Cell strong>{money(p.revenue)}</Cell></tr>)}</ReportTable> : <EmptyState/>}</div><div><h3 className="mb-3 font-bold">Daily sales</h3>{data.daily_sales.length ? <ReportTable headers={["Date", "Orders", "Revenue"]}>{data.daily_sales.map((d) => <tr key={d.date} className="border-b"><Cell>{d.date}</Cell><Cell>{d.orders_count}</Cell><Cell strong>{money(d.revenue)}</Cell></tr>)}</ReportTable> : <EmptyState/>}</div></div></div>
}
function ReportTable({ headers, children }) { return <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b text-zinc-500"><tr>{headers.map((h) => <th className="whitespace-nowrap pb-3 pr-5" key={h}>{h}</th>)}</tr></thead><tbody>{children}</tbody></table></div> }
function Cell({ children, strong, capitalize }) { return <td className={`whitespace-nowrap py-3 pr-5 ${strong ? "font-semibold" : ""} ${capitalize ? "capitalize" : ""}`}>{children}</td> }
