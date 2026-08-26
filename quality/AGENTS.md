# 🤖 AGENTS.md — Regras de Operação da Suíte de Qualidade (LawFirm SaaS)

## 1. Princípios Fundamentais da Infraestrutura de Qualidade

1. **Rastreabilidade Bidirecional Obrigatória**: Todo teste automatizado no repositório deve estar cadastrado no `TEST_CATALOG.yaml` e vinculado ao documento do respectivo módulo em `quality/modules/{module}.md`.
2. **Ciclo de Vida Formal dos Testes**:
   - Todo teste novo deve ser registrado como `planned` antes de sua implementação.
   - Após a criação do arquivo de teste no disco, deve transitar para `implemented_unverified`.
   - Somente após **execução real bem-sucedida** com saída comprovada em foreground, o teste pode transitar para `active` com `last_verified_version` e `last_verified_date` preenchidos.
   - **É terminantemente proibido** transitar diretamente de `planned` para `active`.
3. **Imutabilidade e Responsabilidade**:
   - Testes em quarentena (`quarantined`) ou desativados (`disabled`) exigem o campo `notes` detalhando a justificativa técnica, issue e prazo de resolução.
   - Testes descontinuados (`retired`) preservam seu ID no catálogo para auditoria histórica e o ID **jamais poderá ser reutilizado**.
   - Antes de qualquer release de produção, nenhum teste P0 ativo ou em quarentena/desativado pode permanecer com `owner: unassigned`.
4. **Isolamento de Dados**: Testes nunca devem ser executados contra bancos de produção ou homologação. Todos os testes devem passar pela trava `DatabaseSafetyGuard` com verificação do sentinel `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`.
5. **Zero Egress em E2E**: Testes no navegador devem rodar em rede Docker interna (`quality_internal`) com bloqueio de saída para a internet, utilizando serviços simulados (`mock-server`) para todas as integrações externas.

---

## 2. Protocolo de Modificação de Testes e Documentação

Ao criar, alterar ou corrigir qualquer teste:
1. Atualizar o arquivo de teste correspondente em `tests/`.
2. Atualizar o respectivo registro no `quality/TEST_CATALOG.yaml`.
3. Atualizar o documento funcional em `quality/modules/{module}.md`.
4. Registrar a alteração em `quality/CHANGELOG.md`.
5. Executar `python quality/scripts/validate_test_docs.py` e `python quality/scripts/generate_coverage_matrix.py`.
6. Confirmar que `git diff --exit-code quality/COVERAGE_MATRIX.md` retorna 0 (zero discrepâncias).
