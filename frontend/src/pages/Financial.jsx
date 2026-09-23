import { useCallback, useEffect, useState } from 'react'
import AppLayout from '../components/AppLayout'
import EntryForm from '../components/EntryForm'
import CategoryManager from '../components/CategoryManager'
import { api } from '../api/client'
import { formatCurrency, formatDate } from '../utils/format'

const STATUS_LABEL = {
  recebido: 'Recebido',
  pago: 'Pago',
  pendente: 'Pendente',
  atrasado: 'Atrasado',
  cancelado: 'Cancelado',
}

const STATUS_TONE = {
  recebido: { bg: 'var(--color-accent-bg)', fg: 'var(--color-accent-ink)' },
  pago: { bg: 'var(--color-accent-bg)', fg: 'var(--color-accent-ink)' },
  pendente: { bg: 'var(--color-warning-bg)', fg: 'var(--color-warning)' },
  atrasado: { bg: 'var(--color-danger-bg)', fg: 'var(--color-danger)' },
  cancelado: { bg: 'var(--color-border)', fg: 'var(--color-ink-soft)' },
}

const FILTROS_VAZIOS = { tipo: '', status: '', category_id: '', start: '', end: '' }

export default function Financial() {
  const [categories, setCategories] = useState([])
  const [entries, setEntries] = useState([])
  const [pagination, setPagination] = useState({ page: 1, total_pages: 1 })
  const [filters, setFilters] = useState(FILTROS_VAZIOS)
  const [showCategories, setShowCategories] = useState(false)
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [formError, setFormError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [listError, setListError] = useState('')
  const [loading, setLoading] = useState(true)

  const loadCategories = useCallback(async () => {
    const { categories } = await api.financial.listCategories()
    setCategories(categories)
  }, [])

  const loadEntries = useCallback(async (page = 1) => {
    setLoading(true)
    setListError('')
    try {
      const result = await api.financial.listEntries({ ...filters, page })
      setEntries(result.entries)
      setPagination(result.pagination)
    } catch (err) {
      setListError(err.message)
    } finally {
      setLoading(false)
    }
  }, [filters])

  useEffect(() => { loadCategories() }, [loadCategories])
  useEffect(() => { loadEntries(1) }, [loadEntries])

  function updateFilter(field) {
    return (e) => setFilters((f) => ({ ...f, [field]: e.target.value }))
  }

  async function handleCreateCategory(payload) {
    await api.financial.createCategory(payload)
    await loadCategories()
  }

  async function handleDeleteCategory(id) {
    if (!confirm('Remover esta categoria? Lançamentos já feitos com ela continuam existindo, só ficam sem categoria.')) return
    await api.financial.deleteCategory(id)
    await loadCategories()
  }

  async function handleSubmitEntry(payload) {
    setSubmitting(true)
    setFormError('')
    try {
      if (editing?.id) {
        await api.financial.updateEntry(editing.id, payload)
      } else {
        await api.financial.createEntry(payload)
      }
      setFormOpen(false)
      setEditing(null)
      await loadEntries(pagination.page)
    } catch (err) {
      setFormError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  async function handleCancelEntry(entry) {
    if (!confirm(`Cancelar "${entry.descricao}"? O lançamento fica marcado como cancelado, mas não some do histórico.`)) return
    await api.financial.cancelEntry(entry.id)
    await loadEntries(pagination.page)
  }

  function openNew() {
    setEditing(null)
    setFormError('')
    setFormOpen(true)
  }

  function openEdit(entry) {
    setEditing(entry)
    setFormError('')
    setFormOpen(true)
  }

  return (
    <AppLayout>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 12, marginBottom: 16 }}>
        <h1 style={{ margin: 0 }}>Financeiro</h1>
        <div style={{ display: 'flex', gap: 8 }}>
          <button className="btn btn-secondary" onClick={() => setShowCategories((s) => !s)}>
            {showCategories ? 'Ocultar categorias' : 'Categorias'}
          </button>
          {!formOpen && <button className="btn btn-primary" onClick={openNew}>+ Novo lançamento</button>}
        </div>
      </div>

      {showCategories && (
        <CategoryManager categories={categories} onCreate={handleCreateCategory} onDelete={handleDeleteCategory} />
      )}

      {formOpen && (
        <EntryForm
          categories={categories}
          initial={editing}
          onCancel={() => { setFormOpen(false); setEditing(null) }}
          onSubmit={handleSubmitEntry}
          submitting={submitting}
          error={formError}
        />
      )}

      <div className="card" style={{ marginBottom: 16, display: 'flex', flexWrap: 'wrap', gap: 10, alignItems: 'flex-end' }}>
        <div className="field" style={{ margin: 0 }}>
          <label>De</label>
          <input type="date" value={filters.start} onChange={updateFilter('start')} />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Até</label>
          <input type="date" value={filters.end} onChange={updateFilter('end')} />
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Tipo</label>
          <select value={filters.tipo} onChange={updateFilter('tipo')}>
            <option value="">Todos</option>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Status</label>
          <select value={filters.status} onChange={updateFilter('status')}>
            <option value="">Todos</option>
            {Object.entries(STATUS_LABEL).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
        </div>
        <div className="field" style={{ margin: 0 }}>
          <label>Categoria</label>
          <select value={filters.category_id} onChange={updateFilter('category_id')}>
            <option value="">Todas</option>
            {categories.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
        </div>
        {(filters.tipo || filters.status || filters.category_id || filters.start || filters.end) && (
          <button className="btn btn-secondary" onClick={() => setFilters(FILTROS_VAZIOS)}>Limpar filtros</button>
        )}
      </div>

      {listError && <div className="alert alert-error">{listError}</div>}

      {!loading && entries.length === 0 && (
        <div className="card" style={{ textAlign: 'center' }}>
          <p>Você ainda não possui lançamentos.</p>
          <button className="btn btn-primary" onClick={openNew}>Adicionar primeira movimentação</button>
        </div>
      )}

      <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
        {entries.map((entry) => {
          const tone = STATUS_TONE[entry.status] || STATUS_TONE.pendente
          const isEntrada = entry.tipo === 'entrada'
          return (
            <div key={entry.id} className="card" style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
              <div style={{ flex: '1 1 200px', minWidth: 0 }}>
                <div style={{ fontWeight: 600 }}>{entry.descricao}</div>
                <div style={{ fontSize: '0.85em', color: 'var(--color-ink-soft)' }}>
                  {formatDate(entry.data)}{entry.categoria_nome ? ` · ${entry.categoria_nome}` : ''}
                </div>
              </div>
              <div style={{ fontWeight: 650, color: isEntrada ? 'var(--color-accent-ink)' : 'var(--color-danger)', minWidth: 110, textAlign: 'right' }}>
                {isEntrada ? '+' : '-'} {formatCurrency(entry.valor)}
              </div>
              <span style={{ fontSize: '0.75em', padding: '3px 10px', borderRadius: 999, background: tone.bg, color: tone.fg, whiteSpace: 'nowrap' }}>
                {STATUS_LABEL[entry.status]}
              </span>
              <div style={{ display: 'flex', gap: 6 }}>
                <button className="btn btn-secondary" onClick={() => openEdit(entry)}>Editar</button>
                {entry.status !== 'cancelado' && (
                  <button className="btn btn-secondary" onClick={() => handleCancelEntry(entry)}>Cancelar</button>
                )}
              </div>
            </div>
          )
        })}
      </div>

      {pagination.total_pages > 1 && (
        <div style={{ display: 'flex', justifyContent: 'center', gap: 8, marginTop: 16 }}>
          <button className="btn btn-secondary" disabled={pagination.page <= 1} onClick={() => loadEntries(pagination.page - 1)}>Anterior</button>
          <span style={{ alignSelf: 'center', fontSize: '0.9em', color: 'var(--color-ink-soft)' }}>
            Página {pagination.page} de {pagination.total_pages}
          </span>
          <button className="btn btn-secondary" disabled={pagination.page >= pagination.total_pages} onClick={() => loadEntries(pagination.page + 1)}>Próxima</button>
        </div>
      )}
    </AppLayout>
  )
}
