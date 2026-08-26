# 🛡️ Infraestrutura Permanente de Qualidade e Governança Viva
**LawFirm CRM / SuiteZap** — SaaS Jurídico Multi-Tenant

Este diretório contém a memória operacional versionada, o catálogo de testes automatizados, as matrizes de rastreabilidade e as ferramentas de validação contínua do LawFirm CRM.

---

## 1. Estrutura do Diretório `quality/`

```text
quality/
├── AGENTS.md                   # Regras de processo e governança da suíte de qualidade
├── README.md                   # Este índice central e guia de arquitetura
├── TEST_CATALOG.yaml           # Catálogo formal de testes (Fonte da Verdade com ciclo de vida)
├── COVERAGE_MATRIX.md          # Matriz de cobertura e rastreabilidade (Gerada automaticamente)
├── CHANGELOG.md                # Histórico cronológico de alterações e manutenções de testes
├── KNOWN_GAPS.md               # Registro de lacunas conhecidas, débitos e comportamentos aceitos
├── RELEASE_CHECKLIST.md        # Checklist operacional de validação para homologação e release
├── adr/                        # Architecture Decision Records específicos de Qualidade
│   ├── ADR-001-multi-database-isolation.md
│   ├── ADR-002-playwright-python-stack.md
│   ├── ADR-003-ai-testing-strategy.md
│   └── ADR-004-document-validation-gate.md
├── modules/                    # Documentação viva de requisitos por módulo funcional
│   ├── auth.md                 # Autenticação e Sessão Multi-Tenant
│   ├── chatwoot.md             # Atendimento / Chatwoot e Dual Inbox
│   ├── lead.md                 # Gestão de Leads e Qualificação
│   ├── legal-orchestrator.md   # Conversão Atômica Lead -> Caso -> Processo
│   ├── ai-assistant.md         # Assistentes de IA e Triagem Jurídica
│   ├── tenant-isolation.md     # Provas de Isolamento de Dados e Redis
│   └── governance.md           # Integridade Estática e Módulos Suspensos
├── runbooks/                   # Guias práticos de diagnóstico e execução
│   ├── run-tests-local.md      # Como executar a suíte localmente
│   ├── run-tests-docker.md     # Como executar a suíte via Docker Compose
│   └── investigate-failures.md # Diagnóstico de falhas com logs e traces
└── scripts/                    # Automação de validação documental e geração de relatórios
    ├── validate_test_docs.py   # Validador de integridade documental (12 regras)
    ├── generate_coverage_matrix.py # Gerador automático da COVERAGE_MATRIX.md
    ├── requirements-quality.txt# Dependências Python fixadas para os scripts
    └── tests/                  # Testes unitários dos scripts de qualidade
        └── test_validate_test_docs.py
```

---

## 2. Como Executar a Validação Documental

```bash
# Instalar dependências dos scripts de qualidade
pip install -r quality/scripts/requirements-quality.txt

# Executar validação estática das 12 regras de integridade
python quality/scripts/validate_test_docs.py

# Gerar e atualizar a matriz de cobertura
python quality/scripts/generate_coverage_matrix.py

# Verificar integridade no Git
git diff --exit-code quality/COVERAGE_MATRIX.md
```

---

## 3. Filosofia de Governança

1. **Documentação Viva**: Nenhum teste existe sem estar no catálogo e vinculado a um documento de módulo em `quality/modules/`.
2. **Ciclo em 3 Passos**: `planned` $\rightarrow$ `implemented_unverified` $\rightarrow$ `active`.
3. **Zero Falsos Positivos**: Relatórios refletem rigorosamente execuções reais com saída comprovada.
