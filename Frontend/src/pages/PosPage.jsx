import { useCallback, useEffect, useRef, useState } from "react"
import { CheckCircle2, Clock3, Minus, Package, Plus, Printer, Search, ShoppingBag, Trash2 } from "lucide-react"
import { QRCodeSVG } from "qrcode.react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { customerService, orderService, paywayPaymentService, productService } from "@/services/apiServices"
import { EmptyState, ErrorMessage, LoadingState } from "@/components/PageStates"
import { Pagination } from "@/components/Pagination"
import { useToast } from "@/hooks/useToast"
import { Modal } from "@/components/Modal"

function ProductVisual({ product }) {
  if (product.image) {
    return <img className="h-full w-full object-cover" src={product.image} alt={product.name}/>
  }
  return <Package className="text-zinc-300" size={56}/>
}

export function PosPage() {
  const toast = useToast()
  const [result, setResult] = useState(null), [customers, setCustomers] = useState([]), [cart, setCart] = useState([])
  const [page, setPage] = useState(1), [search, setSearch] = useState(""), [loading, setLoading] = useState(true)
  const [customerId, setCustomerId] = useState(""), [payment, setPayment] = useState("cash"), [error, setError] = useState(""), [success, setSuccess] = useState(""), [busy, setBusy] = useState(false)
  const [qrCheckout, setQrCheckout] = useState(null)
  const previewRef = useRef(null)
  const load = useCallback(async () => { setLoading(true); try { setResult(await productService.list({ page, search, active_only: 1, per_page: 9 })) } catch (e) { setError(e.message) } finally { setLoading(false) } }, [page, search])
  useEffect(() => { customerService.list({ per_page: 100 }).then((response) => setCustomers(response.data)).catch((e) => setError(e.message)) }, [])
  useEffect(() => { const id = setTimeout(load, 250); return () => clearTimeout(id) }, [load])
  const qrOrderId = qrCheckout?.order.id
  const qrOrderNumber = qrCheckout?.order.order_number
  const qrStatus = qrCheckout?.payment.status
  useEffect(() => {
    if (!qrOrderId || qrStatus !== "pending") return
    let stopped = false
    const check = async () => {
      try {
        const next = await paywayPaymentService.get(qrOrderId)
        if (stopped) return
        setQrCheckout((current) => current ? { ...current, payment: next } : null)
        if (next.status === "paid") {
          setCart([]); setBusy(false); setSuccess(`Order ${qrOrderNumber} completed.`)
          toast.success(`Order ${qrOrderNumber} completed.`, "ABA PayWay payment received")
          if (previewRef.current) previewRef.current.location.href = `/orders/${qrOrderId}/invoice`
          await load()
        } else if (["expired", "cancelled"].includes(next.status)) {
          setBusy(false); previewRef.current?.close(); previewRef.current = null
        }
      } catch (e) {
        if (!stopped) setError(e.message)
      }
    }
    const id = window.setInterval(check, 5000)
    check()
    return () => { stopped = true; window.clearInterval(id) }
  }, [qrOrderId, qrOrderNumber, qrStatus, load, toast])

  function add(product) {
    if (!product.stock) return
    setCart((items) => { const found = items.find((item) => item.id === product.id); return found ? items.map((item) => item.id === product.id ? { ...item, quantity: Math.min(item.quantity + 1, product.stock) } : item) : [...items, { ...product, quantity: 1 }] })
  }
  function adjust(id, amount) { setCart((items) => items.map((item) => item.id === id ? { ...item, quantity: Math.max(1, Math.min(item.quantity + amount, item.stock)) } : item)) }
  const subtotal = cart.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0)

  async function checkout(shouldPrint = false) {
    const preview = shouldPrint ? window.open("", "_blank") : null
    if (shouldPrint && !preview) { toast.error("Please allow pop-ups to open the receipt preview.", "Preview blocked"); return }
    if (preview) { preview.document.title = "Preparing receipt..."; preview.document.body.innerHTML = "<p style='font:16px sans-serif;padding:24px'>Preparing receipt...</p>" }
    setBusy(true); setError(""); setSuccess("")
    try {
      const order = await orderService.create({ customer_id: customerId || null, payment_method: payment, items: cart.map((item) => ({ product_id: item.id, quantity: item.quantity })) })
      if (payment === "qr") {
        previewRef.current = preview
        setQrCheckout({ order, payment: order.payway_payment })
        return
      }
      setCart([]); setSuccess(`Order ${order.order_number} completed.`); toast.success(`Order ${order.order_number} completed.`, "Payment successful")
      if (preview) preview.location.href = `/orders/${order.id}/invoice`
      await load()
    } catch (e) {
      preview?.close(); setBusy(false); setError(e.message); toast.error(e.message, "Checkout failed")
    } finally { if (payment !== "qr") setBusy(false) }
  }

  async function closeQrCheckout() {
    const checkout = qrCheckout
    setQrCheckout(null); setBusy(false); previewRef.current?.close(); previewRef.current = null
    if (checkout?.payment.status === "pending") {
      try { await orderService.cancel(checkout.order.id); await load() } catch (e) { setError(e.message) }
    }
  }

  async function simulatePaywayPayment() {
    if (!qrCheckout) return
    try {
      const next = await paywayPaymentService.simulate(qrCheckout.order.id)
      setQrCheckout((current) => current ? { ...current, payment: next } : null)
      setCart([]); setBusy(false); setSuccess(`Order ${qrCheckout.order.order_number} completed in sandbox simulation.`)
      toast.success("Local sandbox payment simulated.", "Test payment completed")
      await load()
    } catch (e) {
      setError(e.message); toast.error(e.message, "Simulation failed")
    }
  }

  return <div className="grid max-w-7xl gap-5 xl:grid-cols-[1fr_380px]">
    <section><div className="mb-5"><h1 className="text-3xl font-bold">Point of sale</h1><p className="text-zinc-500">Select products to create an order.</p></div><ErrorMessage message={error}/>{success && <p className="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{success}</p>}<div className="relative my-5"><Search className="absolute left-3 top-2.5 text-zinc-400" size={20}/><Input className="pl-10" placeholder="Search products..." value={search} onChange={(event) => { setSearch(event.target.value); setPage(1) }}/></div>
      {loading ? <LoadingState/> : !result?.data.length ? <EmptyState>No products available.</EmptyState> : <><div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{result.data.map((product) => <Card key={product.id} className={`p-4 transition ${product.stock ? "cursor-pointer hover:border-indigo-300 hover:shadow-md" : "opacity-50"}`} onClick={() => add(product)}><div className="grid aspect-[4/3] place-items-center overflow-hidden rounded-xl bg-zinc-100"><ProductVisual product={product}/></div><p className="mt-4 font-semibold">{product.name}</p><div className="mt-1 flex justify-between"><span className="text-sm text-zinc-500">{product.stock} in stock</span><span className="font-bold text-indigo-700">${Number(product.price).toFixed(2)}</span></div></Card>)}</div><Pagination meta={result} onPageChange={setPage}/></>}
    </section>
    <Card className="h-fit p-5 xl:sticky xl:top-5"><div className="flex justify-between"><div className="flex items-center gap-2"><ShoppingBag size={20}/><h2 className="font-bold">Current order</h2></div><Button variant="ghost" size="sm" onClick={() => setCart([])}>Clear</Button></div>
      <div className="my-5 space-y-3">{cart.length ? cart.map((item) => <div key={item.id} className="rounded-xl bg-zinc-50 p-3"><div className="flex items-center gap-2"><div className="flex-1"><p className="text-sm font-semibold">{item.name}</p><p className="text-xs text-zinc-500">${Number(item.price).toFixed(2)} each</p></div><p className="text-sm font-bold">${(Number(item.price) * item.quantity).toFixed(2)}</p><button onClick={() => setCart(cart.filter((entry) => entry.id !== item.id))}><Trash2 size={16}/></button></div><div className="mt-2 flex items-center gap-2"><button className="rounded border p-1" onClick={() => adjust(item.id, -1)}><Minus size={13}/></button><span>{item.quantity}</span><button className="rounded border p-1" onClick={() => adjust(item.id, 1)}><Plus size={13}/></button></div></div>) : <p className="py-10 text-center text-sm text-zinc-500">Your cart is empty.</p>}</div>
      <div className="space-y-3 border-t border-dashed pt-4"><label className="block text-xs text-zinc-500">Customer<select className="mt-1 h-10 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900" value={customerId} onChange={(event) => setCustomerId(event.target.value)}><option value="">Walk-in customer</option>{customers.map((customer) => <option key={customer.id} value={customer.id}>{customer.name}</option>)}</select></label><label className="block text-xs text-zinc-500">Payment<select className="mt-1 h-10 w-full rounded-lg border bg-white px-3 text-sm text-zinc-900" value={payment} onChange={(event) => setPayment(event.target.value)}><option value="cash">Cash</option><option value="card">Card</option><option value="qr">QR payment</option></select></label><div className="flex justify-between text-sm text-zinc-500"><span>Subtotal</span><span>${subtotal.toFixed(2)}</span></div><div className="flex justify-between text-sm text-zinc-500"><span>Tax (10%)</span><span>${(subtotal * .1).toFixed(2)}</span></div><div className="flex justify-between text-lg font-bold"><span>Total</span><span>${(subtotal * 1.1).toFixed(2)}</span></div></div>
      <div className="mt-5 grid gap-2"><Button className="w-full" variant="outline" disabled={!cart.length || busy} onClick={() => checkout(false)}>{busy ? "Processing..." : `Charge $${(subtotal * 1.1).toFixed(2)}`}</Button><Button className="w-full" disabled={!cart.length || busy} onClick={() => checkout(true)}><Printer size={17}/>{busy ? "Processing..." : "Charge & Print"}</Button></div>
    </Card>
    <Modal title="Pay with ABA PayWay" open={Boolean(qrCheckout)} onClose={closeQrCheckout}>
      {qrCheckout && <div className="space-y-4 text-center">
        {qrCheckout.payment.status === "paid" ? <div className="py-8"><CheckCircle2 className="mx-auto text-emerald-600" size={64}/><h3 className="mt-3 text-xl font-bold">Payment received</h3><p className="text-sm text-zinc-500">{qrCheckout.order.order_number}</p></div> : <>
          <div className="mx-auto flex h-[360px] w-[290px] max-w-full shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white p-2">{qrCheckout.payment.qr_image ? <img style={{ display: "block", width: 274, height: 344, maxWidth: "100%", objectFit: "contain" }} src={qrCheckout.payment.qr_image} alt="ABA PayWay payment QR code"/> : <QRCodeSVG value={qrCheckout.payment.qr_payload ?? qrCheckout.payment.qr} size={250} level="L" includeMargin/>}</div>
          <p className="text-sm text-zinc-500">Scan with ABA Mobile or any KHQR-enabled banking app</p>
          {/* {qrCheckout.payment.environment === "sandbox" && <p className="rounded-lg bg-blue-50 p-3 text-sm font-medium text-blue-800">Sandbox QR — the live ABA Mobile app cannot pay this code. Use PayWay sandbox testing, or switch to production credentials after ABA approval.</p>} */}
          {qrCheckout.payment.can_simulate && <Button type="button" className="w-full bg-blue-600 hover:bg-blue-700" onClick={simulatePaywayPayment}>Simulate successful payment</Button>}
          {qrCheckout.payment.deeplink && <a className="block rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700" href={qrCheckout.payment.deeplink}>Open ABA Mobile</a>}
          <div className={`flex items-center justify-center gap-2 rounded-lg p-3 text-sm ${qrCheckout.payment.status === "expired" ? "bg-red-50 text-red-700" : "bg-amber-50 text-amber-700"}`}><Clock3 size={17}/>{qrCheckout.payment.status === "expired" ? "QR expired. Close and create a new payment." : "Waiting for payment confirmation…"}</div>
          <p className="break-all text-xs text-zinc-400">Reference: {qrCheckout.payment.reference}</p>
        </>}
        <Button variant="outline" className="w-full" onClick={closeQrCheckout}>{qrCheckout.payment.status === "paid" ? "Done" : "Cancel payment"}</Button>
      </div>}
    </Modal>
  </div>
}
