import { NavLink } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const NAV_ITEMS = [
  { to: '/painel', label: 'Início' },
  { to: '/financeiro', label: 'Financeiro' },
  { to: '/vendas', label: 'Vendas' },
  { to: '/clientes', label: 'Clientes' },
  { to: '/mais', label: 'Mais' },
]

export default function AppLayout({ children }) {
  const { user, logout } = useAuth()

  return (
    <div style={{ display: 'flex', minHeight: '100%' }}>
      <aside
        className="app-sidebar"
        style={{
          width: 240,
          borderRight: '1px solid var(--color-border)',
          padding: '24px 16px',
          display: 'flex',
          flexDirection: 'column',
          gap: 4,
        }}
      >
        <div style={{ fontWeight: 700, marginBottom: 20, paddingLeft: 8 }}>Minha Empresa</div>
        {NAV_ITEMS.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            style={({ isActive }) => ({
              padding: '10px 8px',
              borderRadius: 6,
              color: isActive ? 'var(--color-accent-ink)' : 'var(--color-ink)',
              background: isActive ? 'var(--color-accent-bg)' : 'transparent',
              fontWeight: isActive ? 600 : 400,
            })}
          >
            {item.label}
          </NavLink>
        ))}
        <div style={{ marginTop: 'auto', fontSize: '0.85em' }}>
          <div style={{ color: 'var(--color-ink-soft)', marginBottom: 8 }}>{user?.nome}</div>
          <button className="btn btn-secondary" onClick={logout} style={{ width: '100%' }}>Sair</button>
        </div>
      </aside>

      <main style={{ flex: 1, paddingBottom: 'calc(var(--bottomnav-height) + var(--safe-bottom) + 16px)' }}>
        <div className="container" style={{ paddingTop: 24 }}>{children}</div>
      </main>

      <nav
        className="app-bottomnav"
        style={{
          display: 'none',
          position: 'fixed',
          bottom: 0, left: 0, right: 0,
          height: 'calc(var(--bottomnav-height) + var(--safe-bottom))',
          paddingBottom: 'var(--safe-bottom)',
          background: 'var(--color-surface)',
          borderTop: '1px solid var(--color-border)',
          justifyContent: 'space-around',
          alignItems: 'center',
        }}
      >
        {NAV_ITEMS.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            style={({ isActive }) => ({
              fontSize: '0.75em',
              color: isActive ? 'var(--color-accent-ink)' : 'var(--color-ink-soft)',
              fontWeight: isActive ? 600 : 400,
            })}
          >
            {item.label}
          </NavLink>
        ))}
      </nav>

      <style>{`
        @media (max-width: 860px) {
          .app-sidebar { display: none; }
          .app-bottomnav { display: flex !important; }
        }
      `}</style>
    </div>
  )
}
