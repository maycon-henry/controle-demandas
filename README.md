# Prazo Certo · Controle de Demandas

Aplicação da **Bitarães Assessoria e Consultoria Contábil** para registrar cada demanda de cliente do
início ao fim e gerar o **Relatório do dia** (tela, PDF e texto para WhatsApp). Implementa a
*Especificação técnica v1.0* (28/10/2026).

Laravel 13 · PHP 8.4 · PostgreSQL 16 · deploy automático na Render.

## Rodar localmente

Pré-requisitos: PHP 8.4 com `pdo_pgsql`, `intl`, `gd`, `zip`, `fileinfo`; Composer; Docker (para o Postgres).

```bash
docker run -d --name prazocerto-pg -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=prazocerto -p 54329:5432 postgres:16-alpine
docker exec prazocerto-pg psql -U postgres -c "CREATE DATABASE prazocerto_test"

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # equipe fictícia + 10 demandas do Apêndice A
php artisan serve
```

Acesse http://localhost:8000 — `gestor@bitaraes.local` / `demanda123` (ou `ana@`, `bruno@`, `carla@`,
`diego@`, `eduardo@` como colaboradores).

Para ver exatamente os prints da especificação (quarta-feira, 28/10/2026, 19h30), coloque no `.env`:
`RELOGIO_FIXO="2026-10-28 19:30"` (ignorado em produção).

## Testes

```bash
php artisan test        # 61 testes: seção 14 completa, permissões, login, PDF, CSV, migração
vendor/bin/pint --test  # estilo
```

## Deploy na Render (automático)

1. No painel da Render: **New → Blueprint** → escolha este repositório. O `render.yaml` cria o
   PostgreSQL e o serviço web (Docker).
2. Preencha as variáveis marcadas como manuais: `APP_URL`, `GESTOR_NOME`, `GESTOR_EMAIL`,
   `GESTOR_SENHA` (primeiro gestor; troca obrigatória no 1º acesso) e `MAIL_*` (SMTP para convites e
   redefinição de senha).
3. A partir daí, **cada push na `main`** roda o CI (testes + build) e, se passar, a Render faz o deploy.
   A cada deploy o contêiner roda as migrações e cria o primeiro gestor se ainda não existir.

Planos no `render.yaml`: web `starter` e banco `basic-256mb` (pagos; o Postgres gratuito da Render
expira em 30 dias). A Render não tem região no Brasil; está em `virginia`.

## Migrar os dados do protótipo (seção 12)

```bash
php artisan demandas:importar caminho/da-exportacao.json --conferir=2026-10-28 --conferir=2026-10-29
```

Idempotente (rodar duas vezes não duplica). Responsáveis que não existirem viram usuários inativos
`migrado+nome@bitaraes.local` para o gestor ajustar.

## Estrutura

Arquitetura em camadas (`Domain` → `Application` → `Infrastructure`/`Http`), regras e convenções em
[AGENTS.md](AGENTS.md).

| Rota | O quê |
|---|---|
| `/relatorio`, `/tarefas`, `/equipe` | As três abas |
| `/usuarios` (gestor), `/clientes`, `/ajuda` | Gestão e manual rápido |
| `/relatorio-dia/pdf`, `/demandas/export.csv` | PDF e CSV |
| `/api/...` | API JSON usada pela interface (seção 11.1) |
