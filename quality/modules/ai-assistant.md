# 🤖 Módulo: Assistentes de IA e Triagem Jurídica (ai-assistant)

## 1. Objetivo
Prover automações jurídicas e comerciais baseadas em Inteligência Artificial para análise de fatos, checklists documentais, propostas de honorários e scripts de vendas diretamente na ficha do Lead.

## 2. Escopo
- 4 Ferramentas no painel de Leads (`pre-triagem-lead`, `pre-triagem-checklist`, `gerador-proposta`, `script-vendas`).
- `AssistantController::execute` (validação de saldo, criação de histórico e dispatch de Job).
- `ProcessAiAssistant` (Job assíncrono, chamada N8N e gravação de metadados).
- `AssistantHistory` e modelo de telemetria de custos (`total_cost`, `real_cost`).
- Débito de SuiteCoins e registros em `saas_transactions`.
- Renderização Markdown no browser via `marked.js` e sanitização via `DOMPurify`.

## 3. Fonte Arquitetural
- `packages/SuiteZap/LawFirm/src/AI/Http/Controllers/Admin/AssistantController.php`
- `packages/SuiteZap/LawFirm/src/AI/Jobs/ProcessAiAssistant.php`
- `packages/SuiteZap/LawFirm/src/AI/Models/AssistantHistory.php`
- `packages/SuiteZap/LawFirm/src/Resources/views/leads/lead-tools-panel.blade.php`
- `ARCHITECTURE.md §2` e `SKILL.md §4`

## 4. Comportamentos Conhecidos
- **Contrato do Controller**: Retorna HTTP 503 se N8N não configurado no MotherShip; se saldo OK, debita SuiteCoins antecipadamente, grava status `queued` e retorna HTTP 200.
- **Contrato do Job**: Transiciona `processing` $\rightarrow$ `completed` ou `failed` com retorno gracioso (sem lançar exceptions que matem o worker).
- **Frontend**: Persiste e recebe Markdown bruto do backend; o navegador renderiza com `marked.js` e sanitiza com `DOMPurify`.

## 5. Testes Associados
- `LEAD-AI-001`: Execução assíncrona do assistente 'pre-triagem-lead' (Status: `planned`).
- `LEAD-AI-002`: Execução assíncrona do assistente 'pre-triagem-checklist' (Status: `planned`).
- `LEAD-AI-003`: Execução assíncrona do assistente 'gerador-proposta' (Status: `planned`).
- `LEAD-AI-004`: Execução assíncrona do assistente 'script-vendas' (Status: `planned`).
- `LEAD-AI-005`: Transições canônicas queued -> processing -> completed/failed (Status: `planned`).
- `LEAD-AI-006`: Persistência íntegra do Markdown bruto em generated_content (Status: `planned`).
- `LEAD-AI-007`: Débito único de SuiteCoins e registro em saas_transactions (Status: `planned`).
- `LEAD-AI-008`: Falha HTTP do N8N marca histórico como failed sem derrubar o worker (Status: `planned`).
- `LEAD-AI-009`: N8N ausente retorna o contrato HTTP 503 no Controller (Status: `planned`).
- `LEAD-AI-010`: Saldo insuficiente bloqueia execução antes do débito e do dispatch (Status: `planned`).
- `LEAD-AI-011`: Paginação e isolamento do histórico de execuções (Status: `planned`).
- `LEAD-AI-012`: Idempotência: repetição/retry não gera débito ou execução duplicada (Status: `planned`).
- `LEAD-E2E-001`: Fluxo E2E de execução de IA na UI (Status: `planned`).
- `LEAD-E2E-003`: Validação E2E de renderização Markdown e sanitização DOMPurify (Status: `planned`).

## 6. Lacunas Conhecidas
- `GAP-001`: Falha do Job após o débito não produz estorno automático de SuiteCoins no comportamento atual.

## 7. Última Revisão
- Data: 2026-08-21
- Versão: v3.55.0
