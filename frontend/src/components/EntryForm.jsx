import { useEffect, useState } from 'react'

const STATUS_POR_TIPO = {
  entrada: [
    ['pendente', 'Pendente'],
    ['recebido', 'Recebido'],
    ['atrasado', 'Atrasado'],
  ],
  saida: [
    ['pendente', 'Pendente'],
    ['pago', 'Pago'],
    ['atrasado', 'Atrasado'],
  ],
}

const RECORRENCIAS = [
  ['nenhuma', 'Não repete'],
  ['semanal', 'Semanal'],
  ['mensal', 'Mensal'],
  ['anual', 'Anual'],
]

const vazio = {
  tipo: 'entrada',
  descricao: '',
  valor: '',
  data: new Date().toISOString().slice(0, 10),
  category_id: '',
  forma_pagamento: '',
  status: 'pendente',
  recorrencia: 'nenhuma',
  observacao: '',
}

export default function EntryForm({ categories, initial, onCancel, onSubmit, submitting, error }) {
  const [form, setForm] = useState(initial ? { ...vazio, ...initial } : vazio)

  useEffect(() => {
    setForm(initial ? { ...vazio, ...initial } : vazio)
  }, [initial])

  function update(field) {
    return (e) => setForm((f) => ({ ...f, [field]: e.target.value }))
  }

  function updateTipo(e) {
    const tipo = e.target.value
    setForm((f) => ({ ...f, tipo, category_id: '', status: 'pendente' }))
  }

  const categoriasDoTipo = categories.filter((c) => c.tipo === form.tipo)
  const isEdit = Boolean(initial?.id)

  function handleSubmit(e) {
    e.preventDefault()
    onSubmit({
      ...form,
      valor: Number(form.valor),
      category_id: form.category_id || null,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="card" style={{ marginBottom: 20 }}>
      {error && <div className="alert alert-error">{error}</div>}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 12 }}>
        <div className="field">
          <label>Tipo</label>
          <select value={form.tipo} onChange={updateTipo} disabled={isEdit}>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
          </select>
        </div>

        <div className="field" style={{ gridColumn: '1 / -1' }}>
          <label>Descrição</label>
          <input required value={form.descricao} onChange={update('descricao')} placeholder="Ex: Venda balcão, Aluguel, Internet…" />
        </div>

        <div className="field">
          <label>Valor (R$)</label>
          <input required type="number" min="0.01" step="0.01" value={form.valor} onChange={update('valor')} />
        </div>

        <div className="field">
          <label>Data</label>
          <input required type="date" value={form.data} onChange={update('data')} />
        </div>

        <div className="field">
          <label>Categoria</label>
          <select value={form.category_id} onChange={update('category_id')}>
            <option value="">Sem categoria</option>
            {categoriasDoTipo.map((c) => (
              <option key={c.id} value={c.id}>{c.nome}</option>
            ))}
          </select>
        </div>

        <div className="field">
          <label>Status</label>
          <select value={form.status} onChange={update('status')}>
            {STATUS_POR_TIPO[form.tipo].map(([v, l]) => (
              <option key={v} value={v}>{l}</option>
            ))}
          </select>
        </div>

        <div className="field">
          <label>Forma de pagamento</label>
          <input value={form.forma_pagamento} onChange={update('forma_pagamento')} placeholder="Pix, dinheiro, cartão…" />
        </div>

        <div className="field">
          <label>Repetição</label>
          <select value={form.recorrencia} onChange={update('recorrencia')}>
            {RECORRENCIAS.map(([v, l]) => (
              <option key={v} value={v}>{l}</option>
            ))}
          </select>
        </div>

        <div className="field" style={{ gridColumn: '1 / -1' }}>
          <label>Observação</label>
          <input value={form.observacao} onChange={update('observacao')} />
        </div>
      </div>

      <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
        <button className="btn btn-primary" type="submit" disabled={submitting}>
          {submitting ? 'Salvando…' : isEdit ? 'Salvar alterações' : 'Adicionar lançamento'}
        </button>
        <button className="btn btn-secondary" type="button" onClick={onCancel}>Cancelar</button>
      </div>
    </form>
  )
}
