# Contexto do Projeto — "Minha Empresa" (SaaS para MEI)

> **Regra deste arquivo**: só é permitido ADICIONAR novas seções ao final.
> Nunca apagar, reescrever ou resumir entradas anteriores. Cada entrada
> nova documenta uma etapa de desenvolvimento, do jeito que aconteceu.

---

## [Fase 1 — Arquitetura + banco + autenticação]

### 1. O que foi feito nesta atualização:
- Estrutura de pastas definida: `frontend/`, `backend/` (`public/`, `src/`,
  `api/`), `database/`, `docs/`.
- `database/database.sql`: schema MySQL completo com TODAS as tabelas do
  produto final (mesmo as que fases futuras ainda não usam) — `companies`,
  `users`, `auth_tokens`, `password_resets`, `login_attempts`, `customers`,
  `suppliers`, `products`, `services`, `sales`, `sale_items`,
  `financial_categories`, `financial_entries`, `accounts_receivable`,
  `accounts_payable`, `documents`, `obligations`, `tax_rules`,
  `appointments`, `notifications`, `settings`, `plans`, `subscriptions`,
  `admin_logs`. Seed dos planos padrão (free/pro/premium).
- Backend PHP puro (sem framework, sem Composer): autoloader manual
  (`src/autoload.php`), `Config/Env.php`, `Config/Database.php` (PDO
  singleton), `Helpers/Request.php`, `Helpers/Response.php`.
- Autenticação completa: `Auth/AuthService.php` (registro, login, logout,
  emissão/validação de token bearer com hash SHA-256 em `auth_tokens`),
  `Auth/PasswordResetService.php` (recuperação de senha), rate limiting de
  login (10 tentativas falhas / 15 min).
- `Middleware/AuthMiddleware.php`: valida token e retorna
  `{ user_id, tenant_id }` — base do multi-tenancy.
- Endpoints: `POST /auth/register`, `POST /auth/login`,
  `POST /auth/logout`, `POST /auth/forgot-password`,
  `POST /auth/reset-password`, `GET /auth/me`.
- Front controller `backend/public/index.php`: CORS via `FRONTEND_URL`,
  roteamento simples por `"{METODO} {PATH}"`, exception handler que nunca
  vaza stack trace.
- Frontend Vite + React + React Router: `AuthContext`, `ProtectedRoute`,
  `api/client.js` (fetch wrapper com Bearer token), páginas `Landing`,
  `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `Onboarding`
  (placeholder), `Dashboard` (placeholder), `AppLayout` (sidebar
  desktop / bottom nav mobile), `AuthLayout`.
- Design tokens em `src/styles/global.css`: paleta neutra fria + verde
  "cofre" (`--color-accent: #14664F`), tipografia de sistema (sem
  dependência de fonte externa), sem glassmorphism/gradiente/emoji.
- `README.md` (instalação local, deploy Hostinger, backup) e
  `docs/ROADMAP.md` (checklist das 12 fases).

### 2. Decisões Técnicas e Arquiteturais Tomadas:
- **PHP puro sem Composer/framework**: requisito explícito do projeto —
  rodar em hospedagem compartilhada (Hostinger) sem depender de Node no
  backend nem de `composer install`.
- **Token bearer com hash SHA-256** em vez de JWT: evita dependência de
  biblioteca externa; o token bruto nunca fica salvo no banco, só o hash.
- **Schema do banco criado por completo já na Fase 1**: decisão de
  desenhar todas as tabelas do produto final de uma vez, para não
  precisar de migrations dolorosas a cada fase nova.
- **Multi-tenancy aplicado no backend, nunca só no frontend**: toda tabela
  de dados de negócio tem `tenant_id`; o middleware de autenticação é a
  única fonte do `tenant_id` usado nas queries.
- **Sem fixar valores legais no código** (ex.: limite de faturamento MEI):
  tabela `tax_rules` existe para isso desde a Fase 1, mesmo sem uso ainda.
- **Design sem depender de fonte externa**: pilha de fontes do sistema,
  pensando em carregamento rápido em conexão mobile.

### 3. Estado Atual do Projeto:
- Funcionando de ponta a ponta: cadastro → login → sessão persistente →
  logout → recuperação de senha, tudo gravando/lendo do MySQL de verdade.
- Onboarding e Dashboard: só UI, sem persistência real ainda (planejado
  para a Fase 2).
- Publicado na Hostinger em domínio temporário (ver entrada da Fase 2
  para a topologia real do servidor).

### 4. Próximos Passos recomendados para a próxima IA/Sessão:
- Implementar Fase 2: endpoint de onboarding (`POST /companies`) e
  dashboard com dados reais (`GET /dashboard`).

---

## [Fase 2 — Onboarding + empresa + dashboard]

### 1. O que foi feito nesta atualização:
- `Helpers/Cnpj.php`: validação de CNPJ com dígitos verificadores reais
  (não só contagem de caracteres).
- `Services/CompanyService.php`: salva os dados do onboarding
  (`cnpj`, `razao_social`, `nome_fantasia`, `cnae`, `endereco`, `cidade`,
  `estado`, `telefone`, `email`), valida CNPJ duplicado entre tenants, e
  marca `companies.onboarding_completo = 1`.
- `api/companies/update.php`: endpoint `POST /companies` (autenticado,
  escreve apenas no `tenant_id` do token).
- `Helpers/Period.php`: resolve os períodos do dashboard (`this_month`,
  `last_month`, `last_3_months`, `this_year`, `custom` com `start`/`end`)
  e calcula automaticamente o período anterior de mesma duração para
  comparação.
- `Services/DashboardService.php`: agrega faturamento, despesas,
  resultado, quantidade de vendas, ticket médio, contas a receber/pagar
  em aberto, saldo acumulado (recebido - pago desde o início), e a lista
  de "Atenção" (contas financeiras vencendo em 7 dias ou atrasadas,
  obrigações não pagas vencendo em 14 dias, documentos com validade em
  30 dias, produtos com estoque ≤ estoque mínimo).
- `api/dashboard/summary.php`: endpoint `GET /dashboard?period=...`.
- `Helpers/Request.php`: adicionado `Request::query()` para ler query
  string (necessário para o parâmetro `period`).
- Rotas novas registradas em `backend/public/index.php`.
- Frontend: `Onboarding.jsx` reescrita para chamar `POST /companies` de
  verdade e recarregar o usuário (`AuthContext.reload()`) antes de
  redirecionar ao painel. `Dashboard.jsx` reescrita: busca
  `GET /dashboard`, mostra 8 cards de KPI, seletor de período, lista de
  atenção. `PeriodComparisonChart.jsx`: gráfico de barras em SVG/CSS
  puro (sem biblioteca de gráfico externa) comparando período atual x
  anterior. `utils/format.js`: formatação de moeda BRL e data.
  `api/client.js`: adicionados `api.saveCompany()` e `api.dashboard()`.

### 2. Decisões Técnicas e Arquiteturais Tomadas:
- **"Contas a receber/pagar" e "saldo registrado" não são presos ao
  período selecionado** — são uma foto do momento atual (tudo em aberto,
  independente de quando venceu), enquanto faturamento/despesas/resultado
  seguem o período escolhido. Decisão de produto: o usuário precisa ver o
  total pendente agora, não só o do mês filtrado.
- **Gráfico de comparação feito à mão (SVG/CSS)** em vez de biblioteca
  (ex.: Recharts): mantém o bundle do frontend leve, coerente com o
  requisito de simplicidade/mobile-first e de não depender de pacotes
  pesados numa hospedagem compartilhada.
- **Lista de "Atenção" agregando 4 fontes diferentes** (financeiro,
  obrigações, documentos, estoque) num único array padronizado
  `{ tipo, titulo, data, valor, urgencia }` — facilita renderizar tudo
  com o mesmo componente no frontend e adicionar novas fontes depois.

### 3. Estado Atual do Projeto:
- Onboarding grava a empresa de verdade no banco e libera o acesso ao
  painel (`ProtectedRoute` já verificava `onboarding_completo`, agora o
  valor é real).
- Dashboard busca dados reais da API — hoje aparece zerado porque ainda
  não existe tela para lançar entradas/saídas/vendas (isso é a Fase 3).
- **Importante**: o servidor de produção na Hostinger tem uma topologia
  diferente da estrutura local — o front controller roda em
  `public_html/api/index.php` (fora da pasta `backend/`), com os caminhos
  para `backend/src` e `backend/api` ajustados manualmente para relativo
  (`../../backend/...`). Isso significa que **toda vez que uma rota nova
  é adicionada em `backend/public/index.php` localmente, ela precisa ser
  replicada manualmente em `public_html/api/index.php` no servidor** —
  os dois arquivos não são o mesmo arquivo e não se sincronizam sozinhos.
- Domínio temporário da Hostinger em uso:
  `https://midnightblue-lobster-918894.hostingersite.com` (frontend) e
  `/api` no mesmo domínio (backend).

### 4. Próximos Passos recomendados para a próxima IA/Sessão:
- Implementar Fase 3: módulo Financeiro (CRUD de entradas e saídas,
  categorias personalizadas). É o que vai alimentar o dashboard com
  números reais pela primeira vez.
- Ao subir a Fase 3 para a Hostinger, não esquecer de replicar as novas
  rotas manualmente em `public_html/api/index.php` (ver nota acima) e
  reenviar as pastas novas de `backend/src/` e `backend/api/`.
- Ler `docs/ROADMAP.md` e `guia-tecnico-para-ia.md` antes de começar, para
  manter as convenções (multi-tenant, formato de resposta da API, tokens
  de design) já estabelecidas.

---

## [Fase 3 — Financeiro]

### 1. O que foi feito nesta atualização:
- `Services/FinancialCategoryService.php`: CRUD de categorias
  (`list`, `create`, `update`, `delete`), com checagem de nome duplicado
  por tenant+tipo. Exclusão é definitiva (a FK `fk_finentries_category`
  já é `ON DELETE SET NULL`, então lançamentos antigos não se perdem, só
  ficam sem categoria).
- `Services/FinancialEntryService.php`: CRUD de lançamentos
  (entradas/saídas) com:
  - `list()` com filtros (`start`, `end` sobre a coluna `data`, `tipo`,
    `category_id`, `status`) e paginação (`page`/`per_page`, máx. 100).
  - `create()`/`update()` com validação completa: tipo obrigatório,
    descrição obrigatória, valor > 0, data no formato `AAAA-MM-DD`,
    status validado contra o conjunto permitido por tipo (`recebido` só
    para entrada, `pago` só para saída), categoria validada (precisa
    pertencer ao tenant e ter o mesmo tipo do lançamento).
  - `cancel()`: **não apaga a linha** — muda o `status` para `cancelado`.
    Decisão de preservar histórico financeiro (nunca perder um
    lançamento de verdade).
  - Sincronização automática com `accounts_receivable`/
    `accounts_payable`: enquanto o lançamento está `pendente`/`atrasado`,
    existe uma linha correspondente nessas tabelas; quando o lançamento é
    quitado (`recebido`/`pago`) ou `cancelado`, a linha é atualizada
    (`pago_em`) ou removida.
- Endpoints novos:
  - `GET/POST/PUT/DELETE /financial/categories`
  - `GET/POST/PUT/DELETE /financial/entries`
  (PUT/DELETE identificam o registro por `?id=` na query string, não por
  segmento de rota — ver decisão técnica abaixo.)
- `Helpers/Request.php` já tinha `query()` desde a Fase 2, reaproveitado
  aqui para os filtros de listagem e para o `?id=`.
- Frontend: `pages/Financial.jsx` (página principal: filtros, lista de
  lançamentos em cards responsivos, paginação, estado vazio com CTA),
  `components/EntryForm.jsx` (formulário único reaproveitado para criar e
  editar lançamento), `components/CategoryManager.jsx` (gestão rápida de
  categorias, inline, sem rota própria). Rota `/financeiro` registrada em
  `App.jsx` (o item "Financeiro" já existia no menu desde a Fase 1,
  agora aponta para uma tela real). `api/client.js`: adicionado
  `api.financial.*` (categorias e lançamentos).

### 2. Decisões Técnicas e Arquiteturais Tomadas:
- **IDs de recurso via query string (`?id=`) em vez de segmento de rota**
  (`/financial/entries/5`): o router atual (`index.php`) faz match exato
  de string `"{METODO} {PATH}"` num array associativo, sem regex nem
  parâmetros dinâmicos. Extender o router para suportar `{id}` exigiria
  reescrever a lógica de roteamento — que já precisa ser replicada
  manualmente no `public_html/api/index.php` do servidor (ver Fase 2).
  Manter o router simples e usar query string para o id evita esse
  retrabalho e mantém a réplica manual no servidor trivial (só adicionar
  linhas na matriz `$routes`, sem mudar lógica). Se o projeto crescer
  muito, migrar para um router com regex é um refactor futuro possível.
- **"Excluir" lançamento = cancelar, nunca `DELETE` de verdade no SQL**:
  decisão de produto/contabilidade — um MEI não deve poder apagar
  evidência de um lançamento financeiro por engano; `cancelado` já
  existia como status no enum desde a Fase 1 exatamente para isso.
- **`accounts_receivable`/`accounts_payable` usam a mesma data do
  lançamento (`financial_entries.data`) como vencimento** — ainda não
  existe um campo de "data de vencimento" separado da "data do
  lançamento" no formulário. Essas tabelas ficaram órfãs desde a Fase 1;
  agora são mantidas em sincronia automaticamente a cada
  create/update/cancel de lançamento pendente/atrasado. Se no futuro for
  necessário ter uma data de vencimento diferente da data do lançamento
  (ex.: lançar a despesa hoje mas ela só vencer daqui 30 dias), será
  preciso adicionar uma coluna nova em `financial_entries` (ex.:
  `vencimento`) e ajustar `syncAccountRow()` para usá-la em vez de
  `data`.
- **`tipo` do lançamento não pode ser alterado na edição**: ao editar, o
  formulário força `tipo` para o valor original — evita inconsistência
  entre tipo, categoria escolhida (que é filtrada por tipo) e o conjunto
  de status válido.
- **Categorias e lançamentos na mesma tela (`Financial.jsx`)**, sem rota
  separada para categorias: decisão de simplicidade de produto — o MEI
  não deveria precisar navegar para uma tela "de configuração" só para
  criar uma categoria nova; fica um painel que abre/fecha na própria
  tela de lançamentos.
- **Lista de lançamentos como cards flexíveis (`flex-wrap`) em vez de
  `<table>` HTML**: no mobile, uma tabela HTML tradicional obriga rolagem
  horizontal ou fonte minúscula; o layout em cards que quebra linha
  naturalmente atende ao requisito "tabelas adaptadas para celular" sem
  precisar manter dois markups diferentes (um para desktop, outro para
  mobile).

### 3. Estado Atual do Projeto:
- Financeiro funcional de ponta a ponta: criar categoria → criar
  lançamento → editar → cancelar → filtrar por período/tipo/status/
  categoria, tudo gravando e lendo do MySQL de verdade.
- O Dashboard (Fase 2) já consulta `financial_entries` diretamente nas
  mesmas colunas (`tipo`, `status`, `data`, `valor`) que este módulo
  agora preenche — **não foi necessário alterar `DashboardService.php`**;
  os cards de faturamento, despesas, resultado, contas a receber/pagar e
  a lista de "Atenção" devem passar a mostrar números reais assim que
  houver lançamentos cadastrados.
- `accounts_receivable`/`accounts_payable` agora têm dados reais, mas
  ainda não são lidos por nenhuma tela (dashboard usa `financial_entries`
  diretamente) — ficam prontas para quando a Agenda/Notificações
  (Fase 7) precisarem de datas de vencimento estruturadas.
- Clientes/fornecedores (`customer_id`/`supplier_id` em
  `financial_entries`) **não foram expostos no formulário desta fase** —
  as tabelas `customers`/`suppliers` ainda não têm CRUD (isso é a
  Fase 4). As colunas continuam `NULL` por enquanto.

### 4. Próximos Passos recomendados para a próxima IA/Sessão:
- Implementar Fase 4: CRUD de Clientes e Fornecedores.
- Ao terminar, conectar `customer_id` em `financial_entries` (o campo já
  existe no formulário? — não, precisa ser adicionado ao `EntryForm.jsx`
  depois que a tela de clientes existir) e considerar popular o `select`
  de cliente no lançamento de entradas.
- Não esquecer, de novo, de replicar as 8 rotas novas
  (`/financial/categories` e `/financial/entries`, os 4 métodos de cada)
  manualmente em `public_html/api/index.php` no servidor, e reenviar
  `backend/src/Services/FinancialCategoryService.php`,
  `backend/src/Services/FinancialEntryService.php` e as pastas
  `backend/api/financial/categories/` e `backend/api/financial/entries/`.
