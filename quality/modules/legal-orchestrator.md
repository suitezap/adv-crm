# ⚖️ Módulo: Orquestrador Jurídico e Conversão (legal-orchestrator)

## 1. Objetivo
Executar a conversão transacional e atômica de oportunidades comerciais (Leads ganhos) em estruturas jurídicas completas (`Caso` e `Processo`), vinculando contatos e configurando prazos e checklists iniciais.

## 2. Escopo
- `LegalOrchestrator::convertLeadToLegalStructure($lead)`.
- `LeadWonListener::handle($lead)`.
- Extração prioritária de Área e Prioridade a partir das Tags do Lead.
- Fallback para dados de triagem de IA (`LeadTriagem`).
- Proteção contra conversões duplicadas.

## 3. Fonte Arquitetural
- `packages/SuiteZap/LawFirm/src/Legal/Services/LegalOrchestrator.php`
- `packages/SuiteZap/LawFirm/src/Legal/Listeners/LeadWonListener.php`
- `ARCHITECTURE.md §2` (Bounded Context Legal)

## 4. Comportamentos Conhecidos
- A criação de `Caso` e `Processo` roda dentro de `DB::transaction()`.
- Se a criação de qualquer uma das entidades falhar, toda a operação sofre rollback atômico.
- Se já existir um `Processo` com o `lead_id` informado, a conversão é ignorada (idempotência).

## 5. Testes Associados
- `LEGAL-FEATURE-001`: Conversão atômica de Lead ganho em Caso e Processo vinculados (Status: `planned`).
- `LEGAL-FEATURE-002`: Rollback completo de transação caso a criação do Caso falhe (Status: `planned`).
- `LEGAL-FEATURE-003`: Rollback completo de transação caso a criação do Processo falhe (Status: `planned`).
- `LEGAL-FEATURE-004`: Proteção contra conversão duplicada de Lead já convertido em Processo (Status: `planned`).
- `LEGAL-FEATURE-005`: Priorização de Tags canônicas do Lead para preenchimento de Área e Prioridade (Status: `planned`).
- `LEGAL-FEATURE-006`: Fallback para dados de IA (LeadTriagem) quando Tags do Lead estiverem ausentes (Status: `planned`).
- `LEAD-E2E-002`: Fluxo E2E de conversão de Lead na UI (Status: `planned`).

## 6. Lacunas Conhecidas
- Nenhuma identificada no fluxo de conversão atômica.

## 7. Última Revisão
- Data: 2026-08-21
- Versão: v3.55.0
