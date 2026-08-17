import { useState } from "react"
import { BarChart3, Boxes, FolderTree, LayoutDashboard, LogOut, Menu, ReceiptText, ShoppingCart, Store, Users, X } from "lucide-react"
import { NavLink, Outlet } from "react-router-dom"
import { cn } from "@/utils/cn"
import { useAppDispatch, useAppSelector } from "@/hooks/redux"
import { logout } from "@/redux/slices/authSlice"

const links = [
  { to: "/", label: "Dashboard", icon: LayoutDashboard }, { to: "/pos", label: "Point of sale", icon: ShoppingCart },
  { to: "/products", label: "Products", icon: Boxes }, { to: "/categories", label: "Categories", icon: FolderTree },
  { to: "/customers", label: "Customers", icon: Users }, { to: "/orders", label: "Orders", icon: ReceiptText }, { to: "/reports", label: "Reports", icon: BarChart3 },
]

function SidebarContent({ onNavigate, onSignOut }) {
  return <><div className="flex h-16 shrink-0 items-center gap-3 px-5 lg:h-20 lg:px-6"><span className="grid size-10 place-items-center rounded-xl bg-indigo-500 shadow-lg shadow-indigo-950/30"><Store size={21}/></span><div><p className="font-bold">MiniPOS</p><p className="text-xs text-zinc-400">Store management</p></div></div>
    <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-3">{links.map(({ to, label, icon: Icon }) => <NavLink key={to} to={to} end={to === "/"} onClick={onNavigate} className={({ isActive }) => cn("flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-400 transition", isActive ? "bg-white/10 text-white" : "hover:bg-white/5 hover:text-white")}><Icon size={19}/>{label}</NavLink>)}</nav>
    <button onClick={onSignOut} className="m-3 flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-zinc-400 hover:bg-white/5 hover:text-white"><LogOut size={19}/>Sign out</button></>
}

export function AppLayout() {
  const [menuOpen, setMenuOpen] = useState(false)
  const dispatch = useAppDispatch()
  const user = useAppSelector((state) => state.auth.user)
  const signOut = () => { setMenuOpen(false); void dispatch(logout()) }
  return <div className="app-shell min-h-screen bg-zinc-100 text-zinc-950">
    <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-zinc-950 text-white lg:flex"><SidebarContent onSignOut={signOut}/></aside>
    {menuOpen && <div className="fixed inset-0 z-50 lg:hidden"><button className="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm" onClick={() => setMenuOpen(false)} aria-label="Close menu overlay"/><aside className="relative flex h-full w-[min(82vw,320px)] flex-col bg-zinc-950 text-white shadow-2xl"><button className="absolute right-3 top-3 grid size-10 place-items-center rounded-xl text-zinc-400 hover:bg-white/10 hover:text-white" onClick={() => setMenuOpen(false)} aria-label="Close menu"><X size={21}/></button><SidebarContent onNavigate={() => setMenuOpen(false)} onSignOut={signOut}/></aside></div>}
    <div className="lg:pl-64"><header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-zinc-200 bg-white/95 px-3 backdrop-blur sm:px-5 lg:h-20 lg:px-8"><button className="grid size-11 place-items-center rounded-xl hover:bg-zinc-100 lg:hidden" onClick={() => setMenuOpen(true)} aria-label="Open menu" aria-expanded={menuOpen}><Menu/></button><div className="ml-auto flex min-w-0 items-center gap-2 sm:gap-3"><div className="min-w-0 text-right"><p className="max-w-36 truncate text-sm font-semibold sm:max-w-none">{user?.name}</p><p className="hidden text-xs text-zinc-500 sm:block">Administrator</p></div><div className="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-100 font-bold text-indigo-700 sm:size-10">{user?.name?.charAt(0).toUpperCase()}</div></div></header><main className="p-3 pb-8 sm:p-5 lg:p-8"><Outlet/></main></div>
  </div>
}
