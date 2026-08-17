import { useEffect, useState } from "react"
import { ArrowLeft, Printer, Store } from "lucide-react"
import { Link, useParams } from "react-router-dom"
import { orderService } from "@/services/apiServices"
import { Button } from "@/components/ui/button"
import { ErrorMessage, LoadingState } from "@/components/PageStates"

const money = (value) => `$${Number(value ?? 0).toFixed(2)}`

export function InvoicePage() {
  const { orderId } = useParams()
  const [order, setOrder] = useState(null), [error, setError] = useState("")
  useEffect(() => { orderService.get(orderId).then(setOrder).catch((e) => setError(e.message)) }, [orderId])
  if (!order && !error) return <LoadingState/>
  if (error) return <div className="mx-auto max-w-2xl"><ErrorMessage message={error}/></div>

  const itemCount = order.items.reduce((total, item) => total + item.quantity, 0)
  return <div className="receipt-page mx-auto max-w-[80mm]">
    <div className="no-print mb-4 flex flex-col gap-2 min-[400px]:flex-row min-[400px]:justify-between"><Button variant="outline" asChild><Link to="/orders"><ArrowLeft size={17}/>Orders</Link></Button><Button onClick={() => window.print()}><Printer size={17}/>Print receipt</Button></div>
    <article className="receipt-sheet bg-white px-4 py-6 font-mono text-[11px] leading-[1.45] text-black shadow-sm">
      <header className="text-center"><span className="mx-auto grid size-10 place-items-center rounded-full border-2 border-black"><Store size={20}/></span><h1 className="mt-2 text-lg font-black tracking-widest">MINIPOS MART</h1><p>Your neighborhood mini mart</p><p>Tel: 012 345 678</p></header>

      <div className="my-4 border-y border-dashed border-black py-3"><ReceiptLine label="Receipt" value={order.order_number}/><ReceiptLine label="Date" value={new Date(order.ordered_at).toLocaleDateString()}/><ReceiptLine label="Time" value={new Date(order.ordered_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}/><ReceiptLine label="Customer" value={order.customer?.name ?? "Walk-in"}/><ReceiptLine label="Payment" value={order.payment_method.toUpperCase()}/></div>

      <section><div className="grid grid-cols-[1fr_52px] border-b border-dashed border-black pb-1 font-bold"><span>ITEM</span><span className="text-right">AMOUNT</span></div>{order.items.map((item) => <div key={item.id} className="border-b border-dotted border-zinc-400 py-2"><div className="grid grid-cols-[1fr_52px] gap-2"><span className="break-words font-bold">{item.product_name}</span><span className="text-right font-bold">{money(item.line_total)}</span></div><p>{item.quantity} x {money(item.unit_price)}</p></div>)}</section>

      <section className="space-y-1 border-b border-dashed border-black py-3"><ReceiptLine label={`Subtotal (${itemCount} items)`} value={money(order.subtotal)}/>{Number(order.discount) > 0 && <ReceiptLine label="Discount" value={`-${money(order.discount)}`}/>}<ReceiptLine label="Tax" value={money(order.tax)}/><div className="mt-2 flex items-end justify-between border-t border-black pt-2 text-base font-black"><span>TOTAL</span><span>{money(order.total)}</span></div></section>

      <section className="space-y-1 border-b border-dashed border-black py-3"><ReceiptLine label="Paid via" value={order.payment_method.toUpperCase()}/><ReceiptLine label="Payment" value={order.payment_status.toUpperCase()}/>{order.status === "cancelled" && <p className="mt-2 border-2 border-black p-1 text-center text-sm font-black">CANCELLED / REFUNDED</p>}</section>

      {order.notes && <section className="border-b border-dashed border-black py-3"><p className="font-bold">NOTE</p><p>{order.notes}</p></section>}
      <footer className="pt-4 text-center"><p className="font-bold">THANK YOU FOR SHOPPING!</p><p>Please keep this receipt.</p><p className="mt-3 tracking-[0.18em]">* {order.order_number} *</p><p className="mt-3 text-[9px]">Powered by MiniPOS</p></footer>
    </article>
  </div>
}

function ReceiptLine({ label, value }) {
  return <div className="flex items-start justify-between gap-3"><span className="shrink-0">{label}</span><span className="break-all text-right font-semibold">{value}</span></div>
}
