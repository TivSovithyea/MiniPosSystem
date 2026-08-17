import { useCallback, useEffect, useState } from "react"
import { Pencil, Plus, Search, Trash2 } from "lucide-react"
import { categoryService } from "@/services/apiServices"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Modal } from "@/components/Modal"
import { EmptyState, ErrorMessage, LoadingState } from "@/components/PageStates"
import { Pagination } from "@/components/Pagination"
import { useToast } from "@/hooks/useToast"

const blank = { name: "", slug: "", description: "", is_active: true }

export function CategoriesPage() {
  const toast = useToast()
  const [result, setResult] = useState(null), [page, setPage] = useState(1), [search, setSearch] = useState("")
  const [loading, setLoading] = useState(true), [error, setError] = useState(""), [editing, setEditing] = useState(null), [form, setForm] = useState(blank), [saving, setSaving] = useState(false)
  const load = useCallback(async () => { setLoading(true); setError(""); try { setResult(await categoryService.list({ page, search, per_page: 10 })) } catch (e) { setError(e.message) } finally { setLoading(false) } }, [page, search])
  useEffect(() => { const id = setTimeout(load, 250); return () => clearTimeout(id) }, [load])
  function openForm(category = null) { setEditing(category); setForm(category ? { name: category.name, slug: category.slug, description: category.description ?? "", is_active: category.is_active } : { ...blank }); setError("") }
  async function submit(event) { event.preventDefault(); setSaving(true); setError(""); try { const action = editing ? "updated" : "created"; editing ? await categoryService.update(editing.id, form) : await categoryService.create(form); toast.success(`Category ${action} successfully.`); setEditing(null); setForm(blank); await load() } catch (e) { setError(e.message); toast.error(e.message, "Could not save category") } finally { setSaving(false) } }
  async function remove(category) { if (!window.confirm(`Delete ${category.name}?`)) return; setError(""); try { await categoryService.remove(category.id); toast.success(`${category.name} was deleted.`); await load() } catch (e) { setError(e.message); toast.error(e.message, "Could not delete category") } }
  const modalOpen = editing !== null || form !== blank
  return <div className="mx-auto max-w-6xl space-y-5"><div className="flex flex-wrap items-end justify-between gap-3"><div><h1 className="text-3xl font-bold">Categories</h1><p className="mt-1 text-zinc-500">Organize products into manageable groups.</p></div><Button onClick={() => openForm()}><Plus size={17}/>Add category</Button></div>
    <Card className="p-5"><div className="relative mb-5 max-w-md"><Search className="absolute left-3 top-2.5 text-zinc-400" size={19}/><Input className="pl-10" placeholder="Search categories…" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1) }}/></div><ErrorMessage message={error}/>{loading ? <LoadingState/> : !result?.data.length ? <EmptyState/> : <><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b text-zinc-500"><tr><th className="pb-3">Name</th><th className="pb-3">Slug</th><th className="pb-3">Products</th><th className="pb-3">Status</th><th className="pb-3 text-right">Actions</th></tr></thead><tbody>{result.data.map((item) => <tr key={item.id} className="border-b border-zinc-100"><td className="py-4 font-semibold">{item.name}</td><td className="py-4 text-zinc-500">{item.slug}</td><td className="py-4">{item.products_count}</td><td className="py-4"><span className={`rounded-full px-2 py-1 text-xs ${item.is_active ? "bg-emerald-100 text-emerald-700" : "bg-zinc-100 text-zinc-600"}`}>{item.is_active ? "Active" : "Inactive"}</span></td><td className="py-4"><div className="flex justify-end gap-1"><Button variant="ghost" size="icon" onClick={() => openForm(item)} aria-label="Edit"><Pencil size={16}/></Button><Button variant="ghost" size="icon" onClick={() => remove(item)} aria-label="Delete"><Trash2 size={16}/></Button></div></td></tr>)}</tbody></table></div><Pagination meta={result} onPageChange={setPage}/></>}</Card>
    <Modal title={editing ? "Edit category" : "Add category"} open={modalOpen} onClose={() => { setEditing(null); setForm(blank); setError("") }}><form className="space-y-4" onSubmit={submit}><ErrorMessage message={error}/><label className="block text-sm font-medium">Name<Input className="mt-1" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value, slug: editing ? form.slug : e.target.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "") })} required/></label><label className="block text-sm font-medium">Slug<Input className="mt-1" value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} required/></label><label className="block text-sm font-medium">Description<textarea className="mt-1 min-h-24 w-full rounded-lg border border-zinc-200 p-3 text-sm outline-none focus:border-emerald-500" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })}/></label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })}/>Active</label><div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={() => { setEditing(null); setForm(blank) }}>Cancel</Button><Button disabled={saving}>{saving ? "Saving…" : "Save"}</Button></div></form></Modal>
  </div>
}
