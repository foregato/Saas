import { useState } from 'react'

export default function CategoryManager({ categories, onCreate, onDelete }) {
  const [nome, setNome] = useState('')
  const [tipo, setTipo] = useState('entrada')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await onCreate({ nome, tipo })
      setNome('')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="card" style={{ marginBottom: 20 }}>
      <h3 style={{ fontSize: '0.95rem', marginBottom: 12 }}>Categorias</h3>
      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit} style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 16 }}>
        <input
          value={nome}
          onChange={(e) => setNome(e.target.value)}
          placeholder="Nome da categoria"
          required
          style={{ flex: 1, minWidth: 160, padding: '0.6em 0.8em', border: '1px solid var(--color-border)', borderRadius: 5 }}
        />
        <select
          value={tipo}
          onChange={(e) => setTipo(e.target.value)}
          style={{ padding: '0.6em 0.8em', border: '1px solid var(--color-border)', borderRadius: 5 }}
        >
          <option value="entrada">Entrada</option>
          <option value="saida">Saída</option>
        </select>
        <button className="btn btn-secondary" type="submit" disabled={submitting}>Adicionar</button>
      </form>

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
        {categories.length === 0 && <span style={{ color: 'var(--color-ink-soft)', fontSize: '0.9em' }}>Nenhuma categoria ainda.</span>}
        {categories.map((c) => (
          <span
            key={c.id}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 6,
              fontSize: '0.85em',
              padding: '4px 10px',
              borderRadius: 999,
              background: c.tipo === 'entrada' ? 'var(--color-accent-bg)' : 'var(--color-danger-bg)',
              color: c.tipo === 'entrada' ? 'var(--color-accent-ink)' : 'var(--color-danger)',
            }}
          >
            {c.nome}
            <button
              onClick={() => onDelete(c.id)}
              aria-label={`Remover categoria ${c.nome}`}
              style={{ border: 'none', background: 'none', cursor: 'pointer', color: 'inherit', fontWeight: 700, padding: 0 }}
            >
              ×
            </button>
          </span>
        ))}
      </div>
    </div>
  )
}
