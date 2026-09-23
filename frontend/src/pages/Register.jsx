import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import AuthLayout from '../components/AuthLayout'
import { useAuth } from '../context/AuthContext'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ nome: '', email: '', telefone: '', senha: '' })
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
      await register(form)
      navigate('/onboarding')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AuthLayout
      title="Criar conta"
      footer={<>Já tem conta? <Link to="/entrar">Entrar</Link></>}
    >
      {error && <div className="alert alert-error">{error}</div>}
      <form onSubmit={handleSubmit}>
        <div className="field">
          <label htmlFor="nome">Nome</label>
          <input id="nome" required value={form.nome} onChange={update('nome')} />
        </div>
        <div className="field">
          <label htmlFor="email">E-mail</label>
          <input id="email" type="email" required value={form.email} onChange={update('email')} />
        </div>
        <div className="field">
          <label htmlFor="telefone">Telefone</label>
          <input id="telefone" value={form.telefone} onChange={update('telefone')} />
        </div>
        <div className="field">
          <label htmlFor="senha">Senha</label>
          <input id="senha" type="password" required minLength={8} value={form.senha} onChange={update('senha')} />
        </div>
        <button className="btn btn-primary" type="submit" disabled={submitting} style={{ width: '100%' }}>
          {submitting ? 'Criando conta…' : 'Criar conta'}
        </button>
      </form>
    </AuthLayout>
  )
}
