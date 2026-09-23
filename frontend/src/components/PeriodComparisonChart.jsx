import { formatCurrency } from '../utils/format'

const BARS = [
  { key: 'faturamento', label: 'Receitas', color: 'var(--color-accent)' },
  { key: 'despesas', label: 'Despesas', color: 'var(--color-danger)' },
  { key: 'resultado', label: 'Resultado', color: 'var(--color-ink)' },
]

export default function PeriodComparisonChart({ current, previous }) {
  const values = BARS.flatMap((b) => [Math.abs(current[b.key] ?? 0), Math.abs(previous[b.key] ?? 0)])
  const max = Math.max(1, ...values)
  const chartHeight = 140

  return (
    <div className="card">
      <h3 style={{ fontSize: '0.95rem', marginBottom: 16 }}>Este período x anterior</h3>
      <div style={{ display: 'flex', gap: 28, alignItems: 'flex-end', height: chartHeight }}>
        {BARS.map((b) => {
          const curH = (Math.abs(current[b.key] ?? 0) / max) * chartHeight
          const prevH = (Math.abs(previous[b.key] ?? 0) / max) * chartHeight
          return (
            <div key={b.key} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', flex: 1 }}>
              <div style={{ display: 'flex', gap: 6, alignItems: 'flex-end', height: chartHeight, width: '100%', justifyContent: 'center' }}>
                <div
                  title={`Anterior: ${formatCurrency(previous[b.key])}`}
                  style={{ width: 18, height: Math.max(2, prevH), background: 'var(--color-border)', borderRadius: 3 }}
                />
                <div
                  title={`Atual: ${formatCurrency(current[b.key])}`}
                  style={{ width: 18, height: Math.max(2, curH), background: b.color, borderRadius: 3 }}
                />
              </div>
              <div style={{ marginTop: 8, fontSize: '0.8em', color: 'var(--color-ink-soft)', textAlign: 'center' }}>
                {b.label}
              </div>
            </div>
          )
        })}
      </div>
      <div style={{ display: 'flex', gap: 16, marginTop: 12, fontSize: '0.75em', color: 'var(--color-ink-soft)' }}>
        <span><span style={{ display: 'inline-block', width: 10, height: 10, background: 'var(--color-border)', borderRadius: 2, marginRight: 4 }} />período anterior</span>
        <span><span style={{ display: 'inline-block', width: 10, height: 10, background: 'var(--color-accent)', borderRadius: 2, marginRight: 4 }} />período atual</span>
      </div>
    </div>
  )
}
