# 📋 REGRAS DE RELEASE — LawFirm CRM

> **Obrigatório para qualquer agente** (Antigravity, OpenCode, Hermes ou humano) que bumps a versão, constrói a imagem ou publica no Docker Hub.
> Status: **ATIVA** desde 2026-09-30 · Task que a instituiu: `DOC-002` + `DOC-003`

---

## Por que esta regra existe

Três releases seguidas (`v3.56.0`, `v3.56.1`, `v3.56.2` — de 15 a 23/09/2026) foram publicadas no Docker Hub e registradas no `ARCHITECTURE.md` **sem entrada em nenhum CHANGELOG**. Levou dois meses e três tasks de auditoria (`DOC-001`, `DOC-002`, `DOC-003`) para fechar.

A causa **não foi um agente específico**. Nenhum dos agentes tem o procedimento de release como obrigação verificada, e o `RELEASE_CHECKLIST.md` — que existe e é lido no momento do bump — só mencionava changelog para registrar o digest da imagem.

**Não confie na disciplina. Os guardrails abaixo existem porque confiar nela falhou três vezes.**

---

## Sequência obrigatória de release

Execute **nesta ordem**. Um passo pulado invalida a release.

| # | Passo | Verificação |
|---|-------|-------------|
| 1 | Alterar `LawFirmServiceProvider::VERSION` | `grep "const VERSION" packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php` |
| 2 | Atualizar `docker/entrypoint.sh` (`LF vX.Y.Z`) e `docker-stack-template.yml` (`suitezap/lawfirm:vX.Y.Z`) | ambos devem bater com o passo 1 |
| 3 | Criar ADR na seção `4.x` subsequente do `ARCHITECTURE.md` | seção numerada seguinte à última |
| 4 | **Entrada no `CHANGELOG.md` (raiz) — no TOPO** | ver "Ordem do changelog" abaixo |
| 5 | **Entrada no `quality/CHANGELOG.md` — no TOPO** | mesma versão, com o efeito sobre testes/qualidade |
| 6 | Atualizar `.ai/BASELINE.md` (tabela de versões) | `CODE_VERSION` e `DOCUMENTED_VERSION` |
| 7 | **Rodar o validador — tem que sair verde** | `python quality/scripts/validate_test_docs.py` → `exit 0` |
| 8 | Só então build/push da imagem | `docker build` + `docker push` das tags |

> **O passo 7 é o gate.** As Regras 14 e 15 do validador comparam a versão do código com os changelogs e **falham o `lawfirm-ci.yml`** se houver divergência. Não publique imagem com o validador vermelho.

---

## Ordem do changelog (erro recorrente)

As entradas vão em **ordem decrescente de versão, sempre no topo do arquivo**.

```
## **LF v3.56.3 (...)**     ← mais recente, PRIMEIRO
## **LF v3.56.2 (...)**
## **LF v3.56.1 (...)**
## **LF v3.56.0 (...)**
## **LF v3.55.1 (...)**     ← mais antiga, por último
```

Os validadores assumem que a **primeira ocorrência é a mais recente**. Inserir a entrada no meio do arquivo faz a regra reportar a versão errada — isso aconteceu durante a `DOC-002` e custou uma iteração de correção.

**Cobertura de série:** toda versão presente no `CHANGELOG.md` raiz precisa existir também no `quality/CHANGELOG.md` (Regra 15). Não basta documentar a release atual.

---

## Os dois guardrails e o que cada um pega

| Regra | Arquivo | Detecta | Exemplo real |
|-------|---------|---------|--------------|
| **14 — Consistência de Versão** | `quality/scripts/validate_test_docs.py` | Release publicada sem documentação, ou changelog defasado | Código em 3.56.2, changelog em 3.55.1 |
| **15 — Cobertura da Série** | mesmo arquivo | Versão presente na raiz e ausente no changelog de qualidade | 3.56.0 e 3.56.1 fora do `quality/CHANGELOG.md` com o validador verde |

Cobertura: **deriva de ponta** (release sem doc) e **deriva de intervalo** (release doc na raiz, ausente no derivado).

---

## Regras permanentes

1. **O código é a fonte de verdade da versão.** `LawFirmServiceProvider::VERSION`. CHANGELOG, `BASELINE.md` e ADR são derivados — se divergirem, quem está errado é o derivado.
2. **Não confie em `:latest`.** Produção usa tag ou digest imutável (ADR-GOV-004).
3. **Nunca publique imagem com validador vermelho.** O gate é o passo 7.
4. **A imagem reflete o disco, não um commit.** O ADR §4.94 registrou uma release cuja imagem continha alterações ainda não commitadas. Faça commit **antes** do build.
5. **Não altere código de módulos suspensos** (`AGENTS.md` §8: Faturas WhatsApp, Alertas de Prazo, Importação de Histórico, Agendador de Prazos) sem aprovação explícita.
6. **Sem credenciais em código, changelog ou `.ai/`.** Use aliases (`QA_ADMIN_CREDENTIAL`, `CHATWOOT_TEST_TOKEN`). Vale para o changelog — ele é versionado e sincronizado.

---

## Sobre autoria

**Commits são assinados `SuiteZap <suitezap@gmail.com>` — o git não distingue agente.** Não é possível atribuir um erro a um agente pela autoria do commit. A única fonte de atribuição é o campo *Owner* do `.ai/TASKS.md`.

Consequência prática: **não defenda nem acuse um agente com base em memória.** Consulte o `TASKS.md` e, se o registro não sustentar a conclusão, diga que não é verificável.

---

## Verificação final antes do push

```bash
python quality/scripts/validate_test_docs.py; echo "exit=$?"     # tem que ser 0
grep -m1 "const VERSION" packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php
grep -E "^## " CHANGELOG.md | head -3                              # versão no topo
grep -E "^## " quality/CHANGELOG.md | head -3                       # mesma versão no topo
git status --porcelain                                              # só o escopo do lock
```

---

## Relacionamentos
- [[AGENTS.md]] — regras gerais de governança
- [[RELEASE_CHECKLIST.md]] — checklist de homologação (passos complementares)
- [[.ai/DECISIONS.md]] — ADR-GOV-004 (política de imagens)
- [[.ai/BASELINE.md]] — tabela de versões
- `incidents/INC-2026-09-30-doc-drift-v356.md` — a reincidência que motivou esta regra
