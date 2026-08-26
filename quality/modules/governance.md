# 🛡️ Módulo: Governança e Módulos Suspensos (governance)

## 1. Objetivo
Garantir a integridade de governança do repositório, integridade de arquivos de processo e proteção estática de módulos suspensos contra reativação acidental ou deleção indevida de compatibilidade.

## 2. Escopo
- Integridade documental e validação estática via `validate_test_docs.py`.
- Integridade dos arquivos de governança (`AGENTS.md`, `SKILL.md`, `GUARDRAILS.md`).
- Conformidade arquitetural do repositório.

## 3. Fonte Arquitetural
- `AGENTS.md`
- `ARCHITECTURE.md §4.88` (Descomissionamento e Remoção Definitiva do Whaticket)
- `GUARDRAILS.md`

## 4. Comportamentos Conhecidos
- O Whaticket foi descontinuado, removido do projeto e substituído pelo acesso ao Chatwoot.
- Não existem pacotes, rotas, controladores ou migrations residuais de Whaticket no repositório.

## 5. Testes Associados
- `DOCS-VAL-001`: Suíte de testes unitários do validador documental validate_test_docs.py (Status: `active`).
- `BASIC-UNIT-001`: Teste unitário básico de integridade do framework (Status: `implemented_unverified`).

## 6. Lacunas Conhecidas
- Nenhuma.

## 7. Última Revisão
- Data: 2026-08-21
- Versão: v3.55.0
