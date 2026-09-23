import { Link } from 'react-router-dom'

const RECURSOS = [
  { titulo: 'Financeiro', texto: 'Registre entradas e saídas e veja o resultado do mês sem planilha.' },
  { titulo: 'Vendas', texto: 'Lance vendas de produtos ou serviços e o estoque se ajusta sozinho.' },
  { titulo: 'Clientes e fornecedores', texto: 'Histórico de compras, pagamentos pendentes e contato, tudo num lugar.' },
  { titulo: 'Documentos', texto: 'Guarde CNPJ, contratos e comprovantes com aviso antes do vencimento.' },
  { titulo: 'Obrigações do MEI', texto: 'Acompanhe DAS e declarações com data de vencimento visível.' },
  { titulo: 'Relatórios', texto: 'Exporte faturamento, despesas e vendas em CSV quando precisar.' },
]

const PERGUNTAS = [
  {
    p: 'Preciso entender de contabilidade para usar?',
    r: 'Não. As telas usam linguagem simples e mostram primeiro o que importa: quanto entrou, quanto saiu e o que está pendente.',
  },
  {
    p: 'O sistema envia minhas declarações para a Receita Federal?',
    r: 'Não. Hoje o sistema organiza suas informações e prazos. Ele não envia nada automaticamente a órgãos oficiais.',
  },
  {
    p: 'Posso acessar pelo celular?',
    r: 'Sim, a interface é pensada primeiro para o celular, com atalhos para lançar uma venda ou uma despesa rapidamente.',
  },
]

export default function Landing() {
  return (
    <div>
      <header className="container" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: 24, paddingBottom: 24 }}>
        <strong>Minha Empresa</strong>
        <nav style={{ display: 'flex', gap: 16, alignItems: 'center' }}>
          <Link to="/entrar">Entrar</Link>
          <Link to="/cadastro" className="btn btn-primary">Começar agora</Link>
        </nav>
      </header>

      <section className="container" style={{ paddingTop: 32, paddingBottom: 56, maxWidth: 720 }}>
        <h1 style={{ fontSize: '2.4rem' }}>Tenha sua empresa sob controle.</h1>
        <p style={{ fontSize: '1.05rem' }}>
          Controle dinheiro, vendas, clientes, documentos e obrigações do seu MEI em um único lugar,
          sem precisar entender de contabilidade.
        </p>
        <Link to="/cadastro" className="btn btn-primary">Começar agora</Link>
      </section>

      <section className="container" style={{ paddingBottom: 56 }}>
        <h2>Como funciona</h2>
        <p>Você cadastra sua empresa uma vez. Depois, registra vendas e despesas conforme acontecem — o painel calcula o resto.</p>
      </section>

      <section className="container" style={{ paddingBottom: 56 }}>
        <h2>Recursos</h2>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16 }}>
          {RECURSOS.map((r) => (
            <div className="card" key={r.titulo}>
              <h3 style={{ fontSize: '1rem' }}>{r.titulo}</h3>
              <p>{r.texto}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="container" style={{ paddingBottom: 56, maxWidth: 640 }}>
        <h2>Para quem é</h2>
        <p>Para quem já tem CNPJ de MEI e quer parar de controlar a empresa em anotações soltas, planilhas ou apenas de memória.</p>
      </section>

      <section className="container" style={{ paddingBottom: 56, maxWidth: 640 }}>
        <h2>Perguntas frequentes</h2>
        {PERGUNTAS.map((item) => (
          <div key={item.p} style={{ marginBottom: 20 }}>
            <h3 style={{ fontSize: '1rem' }}>{item.p}</h3>
            <p>{item.r}</p>
          </div>
        ))}
      </section>

      <section className="container" style={{ paddingBottom: 72, textAlign: 'center' }}>
        <h2>Pronto para organizar sua empresa?</h2>
        <Link to="/cadastro" className="btn btn-primary">Começar agora</Link>
      </section>

      <footer className="container" style={{ paddingBottom: 40, color: 'var(--color-ink-soft)', fontSize: '0.85em' }}>
        Minha Empresa — painel de controle para MEI.
      </footer>
    </div>
  )
}
