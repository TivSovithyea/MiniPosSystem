import { useCallback, useEffect, useState } from "react"
import { KeyRound, Pencil, Plus, Search, Trash2, UserCog } from "lucide-react"
import { userService } from "@/services/apiServices"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Modal } from "@/components/Modal"
import { EmptyState, ErrorMessage, LoadingState } from "@/components/PageStates"
import { Pagination } from "@/components/Pagination"
import { useToast } from "@/hooks/useToast"
import { useAppSelector } from "@/hooks/redux"

const emptyUser = () => ({ name: "", email: "", password: "" })

export function UsersPage() {
  const toast = useToast()
  const currentUser = useAppSelector((state) => state.auth.user)
  const [result, setResult] = useState(null)
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState("")
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState("")
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyUser)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(null)
  const [deleteBusy, setDeleteBusy] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    setError("")
    try { setResult(await userService.list({ page, search, per_page: 10 })) }
    catch (err) { setError(err.message) }
    finally { setLoading(false) }
  }, [page, search])

  useEffect(() => { const id = setTimeout(load, 250); return () => clearTimeout(id) }, [load])

  function openForm(user = null) {
    setEditing(user)
    setForm(user ? { name: user.name, email: user.email, password: "" } : emptyUser())
    setError("")
    setFormOpen(true)
  }

  async function submit(event) {
    event.preventDefault()
    setSaving(true)
    setError("")
    try {
      if (editing) await userService.update(editing.id, form)
      else await userService.create(form)
      toast.success(`User ${editing ? "updated" : "created"} successfully.`)
      setFormOpen(false)
      await load()
    } catch (err) {
      setError(err.message)
      toast.error(err.message, "Could not save user")
    } finally { setSaving(false) }
  }

  async function remove() {
    setDeleteBusy(true)
    setError("")
    try {
      await userService.remove(deleting.id)
      toast.success(`${deleting.name} was deleted.`)
      setDeleting(null)
      await load()
    } catch (err) {
      setError(err.message)
      toast.error(err.message, "Could not delete user")
    } finally { setDeleteBusy(false) }
  }

  return <div className="mx-auto max-w-6xl space-y-5">
    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
      <div><h1 className="text-3xl font-bold">Users</h1><p className="mt-1 text-zinc-500">Manage staff accounts that can access MiniPOS.</p></div>
      <Button onClick={() => openForm()}><Plus size={17}/>Add user</Button>
    </div>
    <Card className="p-5">
      <div className="relative mb-5 max-w-md"><Search className="absolute left-3 top-2.5 text-zinc-400" size={19}/><Input className="pl-10" placeholder="Search users…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1) }}/></div>
      <ErrorMessage message={!formOpen && !deleting ? error : ""}/>
      {loading ? <LoadingState/> : !result?.data.length ? <EmptyState>No users found.</EmptyState> : <>
        <div className="overflow-x-auto"><table className="w-full text-left text-sm">
          <thead className="border-b text-zinc-500"><tr><th className="pb-3">User</th><th className="pb-3">Sign-in method</th><th className="pb-3">Created</th><th className="pb-3 text-right">Actions</th></tr></thead>
          <tbody>{result.data.map((user) => <tr key={user.id} className="border-b border-zinc-100">
            <td className="py-4"><div className="flex items-center gap-3"><span className="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-100 font-bold text-indigo-700">{user.name.charAt(0).toUpperCase()}</span><div><p className="font-semibold">{user.name}{user.id === currentUser?.id && <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">You</span>}</p><p className="text-xs text-zinc-500">{user.email}</p></div></div></td>
            <td className="py-4"><span className="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-600">{user.keycloak_id ? <><KeyRound size={13}/>SSO</> : <><UserCog size={13}/>Password</>}</span></td>
            <td className="py-4 text-zinc-500">{new Date(user.created_at).toLocaleDateString()}</td>
            <td className="py-4"><div className="flex justify-end"><Button variant="ghost" size="icon" aria-label={`Edit ${user.name}`} onClick={() => openForm(user)}><Pencil size={16}/></Button><Button variant="ghost" size="icon" aria-label={`Delete ${user.name}`} disabled={user.id === currentUser?.id} onClick={() => { setError(""); setDeleting(user) }}><Trash2 size={16}/></Button></div></td>
          </tr>)}</tbody>
        </table></div>
        <Pagination meta={result} onPageChange={setPage}/>
      </>}
    </Card>

    <Modal title={editing ? "Edit user" : "Add user"} open={formOpen} onClose={() => setFormOpen(false)}>
      <form className="space-y-4" onSubmit={submit}>
        <ErrorMessage message={error}/>
        <label className="block text-sm font-medium">Name<Input className="mt-1" value={form.name} required onChange={(event) => setForm({ ...form, name: event.target.value })}/></label>
        <label className="block text-sm font-medium">Email<Input className="mt-1" type="email" value={form.email} required onChange={(event) => setForm({ ...form, email: event.target.value })}/></label>
        <label className="block text-sm font-medium">Password<Input className="mt-1" type="password" minLength={8} required={!editing} value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })}/><span className="mt-1 block text-xs text-zinc-500">{editing ? "Leave blank to keep the current password." : "Use at least 8 characters."}</span></label>
        <div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={() => setFormOpen(false)}>Cancel</Button><Button disabled={saving}>{saving ? "Saving…" : "Save user"}</Button></div>
      </form>
    </Modal>

    <Modal title="Delete user" open={Boolean(deleting)} onClose={() => setDeleting(null)}>
      <p className="text-sm leading-6 text-zinc-600">Delete <strong className="text-zinc-900">{deleting?.name}</strong>? Their active sessions will be signed out and they will no longer be able to access MiniPOS.</p>
      <ErrorMessage message={error}/>
      <div className="mt-6 flex justify-end gap-2"><Button type="button" variant="outline" disabled={deleteBusy} onClick={() => setDeleting(null)}>Cancel</Button><Button type="button" className="bg-red-600 hover:bg-red-700" disabled={deleteBusy} onClick={remove}>{deleteBusy ? "Deleting…" : "Delete user"}</Button></div>
    </Modal>
  </div>
}
