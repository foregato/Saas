import { Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function ProtectedRoute({ children }) {
  const { user, loading } = useAuth()

  if (loading) {
    return <div className="container" style={{ paddingTop: 40 }}>Carregando…</div>
  }

  if (!user) {
    return <Navigate to="/entrar" replace />
  }

  if (!user.onboarding_completo) {
    return <Navigate to="/onboarding" replace />
  }

  return children
}
