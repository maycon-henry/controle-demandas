# AGENTS.md — Prazo Certo (Controle de Demandas)

Guia obrigatório para qualquer agente de IA ou pessoa desenvolvedora que altere este repositório.
Leia inteiro antes de mudar código. Em caso de conflito, a ordem de prioridade é:
**1. especificação técnica v1.0 (seção citada) → 2. este arquivo → 3. preferência pessoal.**

---

## 1. O produto

Aplicação web da **Bitarães Assessoria e Consultoria Contábil** para registrar cada demanda de cliente
do início ao fim e gerar, todo dia, o relatório: o que foi feito, o que ficou para depois, o que é
para o primeiro horário de amanhã, o que entrou e o que está atrasado.

- **Fonte da verdade:** *Especificação técnica v1.0* (28/10/2026). Toda regra no código cita a seção
  (ex.: `// seção 6.3`). Quando o protótipo e a especificação divergem, **vale a especificação**.
- Textos visíveis ao usuário são os da **seção 9**, palavra por palavra. Não "melhore" a redação.
- Interface somente em **português do Brasil**. Nomes de domínio no código também em português
  (`demanda`, `prazo`, `adiamento`), como no modelo de dados.

## 2. Stack

| Camada | Tecnologia |
|---|---|
| Back-end | Laravel 13 · PHP 8.4 |
| Banco | PostgreSQL 16 (extensões `citext` e `unaccent`) |
| Front-end | Blade + JavaScript em módulos ES nativos (`public/js`), **sem build** |
| PDF | dompdf (`barryvdh/laravel-dompdf`) |
| Deploy | Docker na Render (`render.yaml`), CI no GitHub Actions |

## 3. Arquitetura (camadas e direção das dependências)

```
Http (controllers, requests, apresentadores)  ─┐
Console (comandos)                             ├──▶ Application (casos de uso) ──▶ Domain (regras puras)
                                               │            │
Infrastructure (Eloquent, consultas SQL) ◀─────┘            └──▶ Models (Eloquent)
```

| Pasta | Responsabilidade | Pode depender de |
|---|---|---|
| `app/Domain` | Regras de negócio **puras**: status (6.1), relatório do dia (6.2), desempenho (6.7), formatação (6.8). Sem Eloquent, sem `Request`, sem `now()`. | Só Carbon e o próprio Domain |
| `app/Application` | Casos de uso (`CriarDemanda`, `AdiarDemanda`…), cada um numa transação e gravando o evento do histórico. Saídas (PDF, CSV, WhatsApp) e migração. | Domain, Models, Infrastructure |
| `app/Infrastructure` | Consultas de leitura e mapeamento Eloquent → Domain. Filtros SQL que espelham as regras. | Domain, Models |
| `app/Http` | Controllers finos, Form Requests (validação + mensagens da seção 9), apresentadores JSON. | Todas as anteriores |
| `app/Models` | Eloquent: tabelas, casts e relações. **Nenhuma regra de negócio.** | — |

Regras de ouro:

1. **Status nunca é gravado.** É sempre calculado por `CalculadoraStatus` a partir de `fim`, `prazo`,
   adiamentos e do momento atual. Não crie coluna de status.
2. **Um só lugar para cada regra.** Tela, PDF, CSV e texto do WhatsApp usam o mesmo Domain.
   Se precisar de uma regra em SQL (filtros paginados), ela fica em `EscopoSituacaoSql` **e** precisa
   de teste que compare com o Domain (`MigracaoEConsistenciaTest`).
3. **O tempo vem do `Relogio`**, injetado. Nunca use `now()`/`Carbon::now()` em regra de negócio.
   Todo "dia" é calculado em `America/Sao_Paulo` via `Fuso::dia()`.
4. **Toda escrita grava evento** de histórico com o autor, na mesma transação (seção 6.6).
5. **Exclusão é lógica** (`excluido_em`). Toda consulta de tela usa o escopo `ativas()`.
6. **Permissão é verificada no servidor** (Gates em `AppServiceProvider`); esconder botão não basta.

## 4. Princípios SOLID — como aplicamos aqui

- **S — Responsabilidade única.** Uma classe, um motivo para mudar. Ex.: `AdiarDemanda` só adia;
  `TextosHistorico` só monta frases; `ApresentadorDemanda` só formata para a tela.
  Controller não tem regra: valida (Form Request), chama caso de uso, responde.
- **O — Aberto/fechado.** Novas regras de status entram como nova classe `RegraStatus` na cadeia,
  sem editar as existentes. Novas saídas de relatório são novas classes que recebem `RelatorioDia`.
- **L — Substituição de Liskov.** Qualquer `Relogio` (`RelogioSistema`, `RelogioFixo`) funciona onde
  outro funciona. Implementações não podem lançar exceções nem mudar contratos que a interface não prevê.
- **I — Segregação de interfaces.** Interfaces pequenas e específicas (`Relogio::agora()`,
  `RegraStatus::avaliar()`). Não crie interface "faz-tudo".
- **D — Inversão de dependência.** Dependa de abstrações e receba-as pelo construtor (container do
  Laravel). Nada de `new` de serviços dentro de casos de uso, exceto objetos de valor/DTOs.

## 5. Padrões de projeto em uso (reutilize antes de inventar)

| Padrão | Onde | Para quê |
|---|---|---|
| **Chain of Responsibility** | `Domain/Demandas/Status/CalculadoraStatus` + `Regras/*` | As 7 regras da seção 6.1, avaliadas em ordem |
| **Strategy** | `Relogio` (`RelogioSistema` / `RelogioFixo`) | Trocar a fonte do "agora" em testes e demos |
| **Builder** | `GeradorRelatorioDia` → `RelatorioDia` | Montar as seções e indicadores do dia |
| **Command / Use Case** | `Application/Demandas/*` | Uma ação do usuário = uma classe com `executar()` |
| **Data Mapper** | `Infrastructure/Persistencia/MapeadorDemanda` | Eloquent → `DemandaDados` (domínio imutável) |
| **Presenter** | `Http/Apresentacao/*` | Domínio → JSON com textos já formatados |
| **Value Object / DTO** | `DemandaDados`, `Status`, `DadosFormulario`, `Periodo` | Dados imutáveis e autoexplicativos |
| **Optimistic Locking** | coluna `versao` + `ConflitoDeVersao` | Duas pessoas editando a mesma demanda (seção 10) |

## 6. Clean Code — regras práticas

- **Nomes revelam intenção**, em português do domínio: `pendenteNoDia`, `prazoDepois`, `ficouParaDepois`.
  Nada de `$data2`, `$tmp`, `$x` fora de closures triviais.
- **Funções pequenas** que fazem uma coisa; prefira retorno antecipado a `if` aninhado.
- **Sem números mágicos:** use constantes nomeadas (`TENTATIVAS = 5`, `POR_PAGINA = 50`).
- **Comentários explicam o porquê** e citam a seção da especificação; o código explica o como.
- **Imutabilidade por padrão:** `readonly`, `CarbonImmutable`, classes `final`.
- **Tipos explícitos** em parâmetros e retornos; `@param`/`@return` só quando o PHP não expressa
  (ex.: `list<DemandaDados>`).
- **Sem duplicação:** textos do histórico em `TextosHistorico`; formatos em `Formatador`;
  listas fixas em `ListasFixas`; mensagens de validação nos Form Requests.
- **Erros de regra** lançam `RegraViolada` (vira 422 com a mensagem); conflito, `ConflitoDeVersao` (409).
- Siga o estilo do código ao redor. `vendor/bin/pint` é obrigatório (o CI falha sem ele).

## 7. Front-end

- Módulos em `public/js`: `nucleo.js` (API, avisos, datas, rascunhos), `linha.js` (componente da
  seção 7.3), `formulario.js` (7.4), `abas.js` (7.2, 7.5, 7.6), `app.js` (orquestração).
- **Toda formatação de data/status vem do servidor**; o JS só exibe. Não reimplemente regra no JS.
- Sempre escape conteúdo com `esc()` ao montar HTML.
- Atualização em tempo real: consulta a `/api/versao` a cada 5 s; o que o usuário digita fica em
  `rascunhos` e sobrevive às re-renderizações. Não quebre isso.
- Painéis abrem **dentro da linha** (sem modais); só um painel de ação aberto por vez.
- CSS em `public/css/app.css` com variáveis; tema escuro via `prefers-color-scheme`.
  Layout de cartões abaixo de 900 px; nenhuma rolagem horizontal da página.

## 8. Testes

- Rodar: `php artisan test` (precisa de PostgreSQL; veja o README).
- `tests/Unit`: regras puras com os dados do Apêndice A (`tests/Support/DadosExemplo.php`).
- `tests/Feature`: API, permissões, login, ciclo de vida, PDF, CSV, migração e consistência SQL × Domain.
- **Toda regra nova ou alterada precisa de teste.** Casos da seção 14 nunca podem quebrar.
- Use `$this->fixarRelogio()` para o momento de referência (28/10/2026 19h30).

## 9. Fluxo de trabalho

1. Leia a seção da especificação envolvida.
2. Escreva/ajuste o teste.
3. Implemente na camada certa (seção 3).
4. `vendor/bin/pint` e `php artisan test` verdes.
5. Commits pequenos, mensagem em português no imperativo (`Adiciona…`, `Corrige…`).
6. Push na `main` → CI → deploy automático na Render.

## 10. Nunca faça

- Gravar status, apagar eventos, ou excluir demanda fisicamente.
- Usar `now()` em regra de negócio ou calcular "dia" fora do fuso de Brasília.
- Colocar regra de negócio em controller, model, view ou JavaScript.
- Mudar texto da seção 9 sem mudança correspondente na especificação.
- Commitar `.env`, credenciais ou dados reais de clientes (LGPD).
