# INC-2026-09-30 — Furos na documentação de qualidade (auditoria de referências)

- **Detectado em:** 2026-09-30 pelo Hermes (QA Architect), auditoria de referências cruzadas entre `.ai/`, `quality/` e o repositório real
- **Severidade:** média (documental) · **Task:** `DOC-005` (lock `.ai/locks/DOC-005.lock.yaml`)
- **Base:** `d3b6223f` (pós-merge do `DOC-003` na `2.1`)

## Evento

Auditoria sistemática de cada afirmação documental contra o que existe em disco. O validador oficial (`validate_test_docs.py`, 15 regras) reporta **0 erros** — e mesmo assim há documentação que aponta para arquivo inexistente, afirma cobertura que não existe, e cita incidentes ausentes do repositório.

**Método:** varredura de todos os caminhos referenciados em `.ai/**/*.md` e `quality/**/*.md`, conferência cruzada entre `TEST_CATALOG.yaml` e `COVERAGE_MATRIX.md`, e verificação de `test_file` / `documentation` / `source_references` contra o disco.

## Furos encontrados

### 1. Catálogo cita teste inexistente
`CHATWOOT-E2E-001` (status `planned`) declara `test_file: tests/e2e/workflows/test_chatwoot_sac_workflow.py`, que **não existe** no repositório. O mesmo caminho é citado em `quality/COVERAGE_MATRIX.md:83`.

### 2. O validador não cobre esse caso (furo de guardrail)
A Regra 4 (`Existência de arquivo`) só verifica `test_file` para os status `implemented_unverified`, `active`, `quarantined` e `disabled` — **`planned` está fora**. Resultado: um teste planejado pode citar um arquivo que nunca existiu e o CI passa.

É a mesma classe de defeito da Regra 14 (cobertura de ponta, não de intervalo), agora no eixo status↔arquivo.

### 3. Incidente citado e ausente do repositório
`.ai/TASKS.md` referencia `.ai/incidents/INC-2026-09-15-sync-conflict-git-index.md`. O arquivo **não está no repositório** — existe apenas na árvore local de `feature/ajustes-no-leads-view`, como trabalho não commitado. A SSOT cita um documento que a SSOT não contém.

### 4. "20 testes ativos" não significa 20 testes válidos
Os 20 testes `active` declaram `last_verified_version` em `v3.55.0` (7 testes) ou `v3.55.1` (13 testes). O código está em **v3.56.3**. Ou seja: **nenhum teste ativo foi verificado na versão corrente** — a cobertura nominal está 3 releases atrás. Os mais antigos são de 21–22/08 (`DOCS-VAL-001`, `BASIC-UNIT-001`, `AUTH-FEATURE-001`, `CHATWOOT-FEATURE-001`, `SEC-GUARD-001`).

### 5. Referência a rota inexistente
`.ai/incidents/INC-2026-09-27-processos-casos-lookup-eav.md` cita `packages/Webkul/LawFirm/src/Routes/web.php`; as rotas reais estão em `packages/Webkul/Admin/src/Routes/Admin/web.php` e `.../Front/web.php`. Provável referência herdada do fork.

### 6. Débitos abertos não visíveis no catálogo
`quality/KNOWN_GAPS.md` mantém `GAP-002` (domínios fora da Fase 1) e `GAP-003` (testes de IA rodam com mock, `AI_REAL_TESTS=false`) abertos. Nenhum aparece no `TEST_CATALOG.yaml` nem no `.ai/TASKS.md` — a leitura do catálogo sugere cobertura que não existe.

## O que está correto (verificado)

- `COVERAGE_MATRIX.md` e `TEST_CATALOG.yaml` **batem** entre si: nenhum id órfão em qualquer direção.
- Todos os 14 módulos de `quality/modules/` existem e todos são citados por ao menos um teste.
- `docker/testing/Dockerfile.playwright` existe (a referência do ADR-002 está correta).
- Nenhuma referência a arquivo inexistente em `AGENTS.md` nem no `RELEASE_CHECKLIST.md`.

## Impacto

- Leitor pode concluir que existe suíte E2E de Chatwoot/SAC quando não existe.
- **Validador verde não é sinônimo de documentação íntegra** — a confiança excessiva no gate é o risco maior, porque induz a não verificar.
- Decisões de prontidão para o piloto baseadas em "48 testes catalogados" ou "20 ativos" superestimam a cobertura real.

## Correção (`DOC-005`)

1. `.ai/REGRAS-OPENCODE-OPERACAO.md` criado com a seção "Furos de documentação que você pode tropeçar", listando os 5 furos acima e o que fazer em cada um.
2. `OPS-WEBHOOK-001` **dividida** em três tasks com donos explícitos no `.ai/TASKS.md`: `ENV-001` (OpenCode), `SEC-001` (operador), `VER-001` (Hermes), com a mãe dependente das três. Justificativa: `ADR-GOV-002` exige um `WRITE OWNER` por vez e as etapas não corroem no mesmo escopo.
3. `AGENTS.md` §1.7 agora referencia o terceiro documento de regras.

## Pendente (não corrigido aqui — requer decisão)

- **Estender a Regra 4 para `planned`** fecha o furo 2 no guardrail. É a mesma mecânica do `DOC-003`; não aplicado para não decidir sozinho o escopo do validador.
- Decidir o destino de `CHATWOOT-E2E-001`: implementar o arquivo, ou remover a entrada do catálogo e da matriz.
- Commit do `INC-2026-09-15` (hoje só existe na árvore local) e execução da ação corretiva: excluir `.git/` do Syncthing (`ADR-GOV-003`).

## Prevenção

Nenhuma regra nova de *conteúdo* — os furos são de **coerência entre documentos**, e o validador cobre agora 15 regras focadas em versão e status. A lição estrutural: **gate automatizado que cobre o caso principal não garante ausência de casos análogos.** Ao escrever uma regra, perguntar sempre "qual é o caso irmão que ela não pega?".

---

*Auditoria: Hermes (QA Architect) · 2026-09-30 · base `d3b6223f`*
