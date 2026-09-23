import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import AuthLayout from '../components/AuthLayout'
import { api } from '../api/client'
import { useAuth } from '../context/AuthContext'

const CAMPOS = [
  ['cnpj', 'CNPJ', true],
  ['razao_social', 'Razão social', true],
  ['nome_fantasia', 'Nome fantasia', false],
  ['cnae', 'CNAE / atividade principal', false],
  ['endereco', 'Endereço', false],
  ['cidade', 'Cidade', false],
  ['estado', 'Estado (sigla, ex: SP)', false],
  ['telefone', 'Telefone', false],
  ['email', 'E-mail', false],
]

export default function Onboarding() {
  const { reload } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState(Object.fromEntries(CAMPOS.map(([k]) => [k, ''])))
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  function update(field) {
    return (e) => setForm((f) => ({ ...f, [field]: e.target.value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await api.saveCompany(form)
      await reload()
      navigate('/painel')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout title="Vamos configurar sua empresa">
      {error && <div className="alert alert-error">{error}</div>}
      <p>
        Isso leva menos de um minuto. Você pode ajustar esses dados depois
        em Configurações.
      </p>
      <form onSubmit={handleSubmit}>
        {CAMPOS.map(([field, label, required]) => (
          <div className="field" key={field}>
            <label htmlFor={field}>{label}</label>
            <input
              id={field}
              required={required}
              value={form[field]}
              onChange={update(field)}
              maxLength={field === 'estado' ? 2 : undefined}
            />
          </div>
        ))}
        <button className="btn btn-primary" type="submit" disabled={submitting} style={{ width: '100%' }}>
          {submitting ? 'Salvando…' : 'Continuar'}
        </button>
      </form>
    </AuthLayout>
  )
}
