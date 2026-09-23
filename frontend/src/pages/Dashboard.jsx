import { useEffect, useState, useCallback } from 'react'
import AppLayout from '../components/AppLayout'
import PeriodComparisonChart from '../components/PeriodComparisonChart'
import { useAuth } from '../context/AuthContext'
import { api } from '../api/client'
import { formatCurrency, formatDate } from '../utils/format'

const PERIODOS = [
  { value: 'this_month', label: 'Este mês' },
  { value: 'last_month', label: 'Mês passado' },
  { value: 'last_3_months', label: 'Últimos 3 meses' },
  { value: 'this_year', label: 'Este ano' },
]

const URGENCIA_LABEL = {
  atrasado: 'Atrasado',
  proximo: 'Vencendo em breve',
  atencao: 'Atenção',
}

function Kpi({ label, value, tone }) {
  return (
    <div className="card">
      <div style={{ fontSize: '0.8em', color: 'var(--color-ink-soft)', marginBottom: 6 }}>{label}</div>
      <div style={{ fontSize: '1.4rem', fontWeight: 650, color: tone || 'var(--color-ink)' }}>{value}</div>
    </div>
  )
}

export default function Dashboard() {
  const { user } = useAuth()
  const [period, setPeriod] = useState('this_month')
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = useCallback(async (p) => {
    setLoading(true)
    setError('')
    try {
      const result = await api.dashboard({ period: p })
      setData(result)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(period)
  }, [period, load])

  return (
    <AppLayout>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 12, marginBottom: 20 }}>
        <div>
          <h1 style={{ marginBottom: 4 }}>Olá, {user?.nome?.split(' ')[0]}</h1>
          <p style={{ margin: 0 }}>{user?.nome_fantasia || user?.razao_social}</p>
        </div>
        <select value={period} onChange={(e) => setPeriod(e.target.value)} className="field" style={{ margin: 0, width: 180 }}>
          {PERIODOS.map((p) => (
            <option key={p.value} value={p.value}>{p.label}</option>
          ))}
        </select>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {loading && !data && <p>Carregando painel…</p>}

      {data && (
        <>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 14, marginBottom: 20 }}>
            <Kpi label="Faturamento do período" value={formatCurrency(data.faturamento)} tone="var(--color-accent-ink)" />
            <Kpi label="Despesas do período" value={formatCurrency(data.despesas)} tone="var(--color-danger)" />
            <Kpi label="Resultado" value={formatCurrency(data.resultado)} tone={data.resultado >= 0 ? 'var(--color-accent-ink)' : 'var(--color-danger)'} />
            <Kpi label="Saldo registrado" value={formatCurrency(data.saldo_registrado)} />
            <Kpi label="Contas a receber" value={formatCurrency(data.contas_a_receber)} />
            <Kpi label="Contas a pagar" value={formatCurrency(data.contas_a_pagar)} />
            <Kpi label="Vendas no período" value={data.quantidade_vendas} />
            <Kpi label="Ticket médio" value={formatCurrency(data.ticket_medio)} />
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'minmax(280px, 1fr) minmax(260px, 1fr)', gap: 16, alignItems: 'start' }}>
            <PeriodComparisonChart current={data} previous={data.comparacao_periodo_anterior} />

            <div className="card">
              <h3 style={{ fontSize: '0.95rem', marginBottom: 12 }}>Atenção</h3>
              {data.atencao.length === 0 ? (
                <p style={{ margin: 0 }}>Nada pendente por aqui — tudo em dia.</p>
              ) : (
                <ul style={{ listStyle: 'none', margin: 0, padding: 0, display: 'flex', flexDirection: 'column', gap: 10 }}>
                  {data.atencao.map((item, i) => (
                    <li key={i} style={{ display: 'flex', justifyContent: 'space-between', gap: 8, fontSize: '0.9em', borderBottom: '1px solid var(--color-border)', paddingBottom: 8 }}>
                      <div>
                        <div>{item.titulo}</div>
                        {item.data && <div style={{ color: 'var(--color-ink-soft)', fontSize: '0.85em' }}>{formatDate(item.data)}</div>}
                      </div>
                      <span
                        style={{
                          alignSelf: 'flex-start',
                          fontSize: '0.75em',
                          padding: '3px 8px',
                          borderRadius: 999,
                          background: item.urgencia === 'atrasado' ? 'var(--color-danger-bg)' : 'var(--color-warning-bg)',
                          color: item.urgencia === 'atrasado' ? 'var(--color-danger)' : 'var(--color-warning)',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {URGENCIA_LABEL[item.urgencia] || item.urgencia}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        </>
      )}
    </AppLayout>
  )
}
