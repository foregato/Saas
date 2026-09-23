# Minha Empresa — SaaS de gestão para MEI

Painel de controle empresarial para MEIs brasileiros: financeiro, vendas,
clientes, produtos, documentos, obrigações e relatórios em um só lugar.

## Status do projeto

Este repositório é construído em fases (ver `docs/ROADMAP.md`). O que está
pronto até agora:

- ✅ **Fase 1 — Arquitetura + banco + autenticação**: estrutura de pastas,
  `database.sql` completo (todas as tabelas do produto final), autenticação
  (cadastro, login, logout, recuperação de senha) com multi-tenancy aplicado
  no backend, e o esqueleto do frontend (landing page, telas de auth,
  layout com sidebar/bottom nav).
- ⏳ **Fase 2 em diante** (onboarding real, dashboard com dados, financeiro,
  vendas, clientes, documentos, obrigações, relatórios, admin do SaaS, PWA):
  ainda não implementadas. Peça para continuar a próxima fase quando quiser.

Todas as telas e chamadas de API já criadas são reais e funcionam de ponta a
ponta (não é apenas visual): cadastro grava no MySQL, login gera token,
rotas privadas exigem token válido.

## Stack

- **Frontend**: Vite + React + React Router + CSS puro
- **Backend**: PHP 8+ (sem framework, sem dependência de Composer —
  compatível com hospedagem compartilhada)
- **Banco**: MySQL (compatível com phpMyAdmin)
- **Hospedagem alvo**: Hostinger (hospedagem compartilhada)

## Estrutura

```
frontend/   → SPA React (Vite)
backend/    → API REST em PHP puro
  public/   → único diretório exposto ao público (front controller)
  src/      → classes da aplicação (Config, Auth, Middleware, Helpers...)
  api/      → scripts de endpoint, incluídos pelo router em public/index.php
database/   → database.sql (schema completo)
docs/       → documentação adicional
```

## Como rodar localmente

### 1. Banco de dados

1. Crie um banco MySQL local (ou use o phpMyAdmin do XAMPP/MAMP/Laragon).
2. Importe `database/database.sql` — ele já cria o banco `mei_saas` e todas
   as tabelas, incluindo os planos padrão (free/pro/premium).

### 2. Backend

1. Copie `.env.example` para `backend/.env` e preencha os dados do banco:
   ```
   DB_HOST=127.0.0.1
   DB_DATABASE=mei_saas
   DB_USERNAME=root
   DB_PASSWORD=
   FRONTEND_URL=http://localhost:5173
   ```
2. Suba um servidor PHP embutido apontando para `backend/public`:
   ```
   php -S localhost:8000 -t backend/public
   ```
3. A API responde em `http://localhost:8000/api/...` (ex.: `POST /api/auth/register`).

> Não é necessário Composer — o autoload é feito por um autoloader simples
> em `backend/src/autoload.php`.

### 3. Frontend

1. Copie `frontend/.env.example` para `frontend/.env` e ajuste `VITE_API_URL`
   se necessário.
2. Instale e rode:
   ```
   cd frontend
   npm install
   npm run dev
   ```
3. Acesse `http://localhost:5173`.

## Publicando na Hostinger (hospedagem compartilhada)

1. **Banco**: crie o banco MySQL pelo hPanel da Hostinger, anote host,
   usuário, senha e nome do banco. Importe `database/database.sql` pelo
   phpMyAdmin (aba "Importar").
2. **Backend**: envie o conteúdo da pasta `backend/` para o servidor (via
   Gerenciador de Arquivos ou FTP). Aponte o domínio/subdomínio da API para
   `backend/public` (é o único diretório que deve ficar acessível
   publicamente — `src/`, `api/` e o `.env` não devem estar em uma pasta
   servida diretamente). Crie `backend/.env` no servidor com as credenciais
   reais do banco e `FRONTEND_URL` apontando para o domínio de produção do
   frontend.
3. **Frontend**: rode `npm run build` localmente (ou em CI); isso gera
   `frontend/dist`. Envie o conteúdo de `dist/` para a pasta pública do
   domínio principal (`public_html`). Configure `frontend/.env` com
   `VITE_API_URL` apontando para a URL pública da API **antes** do build.
4. **URLs de produção**: confira que `FRONTEND_URL` (backend) e
   `VITE_API_URL` (frontend) apontam um para o outro corretamente — é isso
   que faz o CORS funcionar.

## Criando o primeiro administrador da plataforma

A área administrativa (seção 24 do briefing) chega na fase "Admin do SaaS".
Quando implementada, o primeiro admin será promovido rodando, direto no
banco (phpMyAdmin → SQL), depois de já ter uma conta cadastrada normalmente:

```sql
UPDATE users SET is_platform_admin = 1 WHERE email = 'seu-email@exemplo.com';
```

A coluna já existe no schema da Fase 1 (`users.is_platform_admin`), então
isso já funciona mesmo antes da tela de admin existir.

## Backup do banco

Pelo phpMyAdmin: selecione o banco `mei_saas` → aba "Exportar" → formato
SQL → Executar. Guarde o `.sql` gerado fora do servidor (ex.: Google Drive).
Recomenda-se agendar isso periodicamente quando o sistema estiver em
produção com dados reais.

## Segurança já aplicada na Fase 1

- Senhas com `password_hash`/`password_verify` (bcrypt)
- Todas as queries usam prepared statements (PDO)
- Tokens de sessão são hashes SHA-256 armazenados no banco (o token bruto
  nunca fica salvo); expiram em 30 dias e podem ser revogados
- Multi-tenancy: toda tabela de dados tem `tenant_id`; o middleware de
  autenticação retorna o `tenant_id` do token e cada query já criada filtra
  por ele — isso é aplicado no backend, nunca só no frontend
- Rate limiting simples de login (10 tentativas falhas / 15 min por e-mail)
- Recuperação de senha não revela se um e-mail existe na base
- `.env` fora do diretório público (`backend/public`) e listado no
  `.gitignore`
- Erros nunca retornam stack trace ou detalhes internos ao cliente

Envio de e-mail (verificação de conta, link de recuperação de senha) ainda
não está integrado — por enquanto o token de recuperação é gravado no log
do servidor (`error_log`) para uso em desenvolvimento. Integrar um
provedor de e-mail é um bom próximo passo antes de produção real.
"# Saas" 
