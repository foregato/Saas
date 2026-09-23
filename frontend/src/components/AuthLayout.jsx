import { Link } from 'react-router-dom'

export default function AuthLayout({ title, children, footer }) {
  return (
    <div style={{ minHeight: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24 }}>
      <div style={{ width: '100%', maxWidth: 380 }}>
        <Link to="/" style={{ display: 'block', marginBottom: 24, fontWeight: 700, color: 'var(--color-ink)' }}>
          Minha Empresa
        </Link>
        <div className="card">
          <h1 style={{ fontSize: '1.3rem' }}>{title}</h1>
          {children}
        </div>
        {footer && <div style={{ marginTop: 16, fontSize: '0.9em', textAlign: 'center' }}>{footer}</div>}
      </div>
    </div>
  )
}
