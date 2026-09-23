import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import AuthLayout from '../components/AuthLayout'
import { api } from '../api/client'

export default function ResetPassword() {
  const [params] = useSearchParams()
  const token = params.get('token') || ''
  const navigate = useNavigate()
  const [senha, setSenha] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await api.resetPassword(token, senha)
      navigate('/entrar')
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  if (!token) {
    return (
      <AuthLayout title="Redefinir senha" footer={<Link to="/recuperar-senha">Solicitar novo link</Link>}>
        <div className="alert alert-error">Link inválido. Solicite uma nova recuperação de senha.</div>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout title="Redefinir senha">
      {error && <div className="alert alert-error">{error}</div>}
      <form onSubmit={handleSubmit}>
        <div className="field">
          <label htmlFor="senha">Nova senha</label>
          <input id="senha" type="password" required minLength={8} value={senha} onChange={(e) => setSenha(e.target.value)} />
        </div>
        <button className="btn btn-primary" type="submit" disabled={submitting} style={{ width: '100%' }}>
          {submitting ? 'Salvando…' : 'Salvar nova senha'}
        </button>
      </form>
    </AuthLayout>
  )
}
