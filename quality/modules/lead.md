# 🎯 Módulo: Gestão de Leads e Qualificação (lead)

## 1. Objetivo
Gerenciar a captação, ciclo de estágios no funil de vendas (Pipeline) e enriquecimento de dados pré-jurídicos de potenciais clientes do escritório.

## 2. Escopo
- Cadastro, listagem e edição de Leads.
- Associação com Contatos (`Person`) e Organizações.
- Painel de ferramentas de apoio e qualificação jurídica (`lead-tools-panel`).
- Transição de estágios (Novo, Acompanhamento, Qualificado, Negociação, Ganho, Perdido).

## 3. Fonte Arquitetural
- `packages/Webkul/Lead/`
- `packages/SuiteZap/LawFirm/src/Resources/views/leads/lead-tools-panel.blade.php`
- `ARCHITECTURE.md §2`

## 4. Comportamentos Conhecidos
- Quando um lead atinge o estágio `won` (Ganho), o listener `LeadWonListener` é disparado automaticamente.
- Se o lead estiver marcado como `lost` (Perdido), o painel de qualificação fica oculto.

## 5. Testes Associados
- Coberto indiretamente por `LEGAL-FEATURE-001` a `004` e `LEAD-E2E-002` (Status: `planned`).

## 6. Lacunas Conhecidas
- Testes de atributos customizados dinâmicos do Lead planejados para expansão futura.

## 7. Última Revisão
- Data: 2026-08-21
- Versão: v3.55.0
