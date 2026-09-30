# INC-2026-09-30 — Lacuna de cobertura no `quality/CHANGELOG.md` (furo na Regra 14)

- **Detectado em:** 2026-09-30 pelo Hermes (QA Architect), ao validar o merge do `DOC-002` feito pelo Antigravity
- **Severidade:** baixa (documental) · **Task:** `DOC-003` (lock `.ai/locks/DOC-003.lock.yaml`)
- **Base:** `8f563040` (pós-merge do `DOC-002` na `2.1`)

## Evento

A **Regra 14** (Consistência de Versão), criada no `DOC-002` para impedir o `BASELINE_VERSION_MISMATCH`, **aprovava um changelog com lacunas no meio da série**. Ela compara `LawFirmServiceProvider::VERSION` apenas com a **entrada mais recente** de cada CHANGELOG: se a release atual estiver documentada, ela aprova — independentemente de versões intermediárias faltando.

Estado real em `8f563040` (código em v3.56.3):

| Arquivo | Versões declaradas (3.56.x) | Lacuna |
|---|---|---|
| `CHANGELOG.md` (raiz) | 3.56.0 · 3.56.1 · 3.56.2 · 3.56.3 | — |
| `quality/CHANGELOG.md` | 3.56.2 · 3.56.3 | **3.56.0 e 3.56.1 ausentes** |

O validador retornava **exit 0 (verde)**. A v3.56.0 e a v3.56.1 estavam documentadas na raiz e nos ADRs §4.92/§4.93, mas não no changelog de qualidade — exatamente o tipo de buraco que o `DOC-002` existe para eliminar, escapando por uma definição diferente.

## Causa raiz

- **De desenho da regra:** a Regra 14 foi especificada como "o código bate com o changelog mais recente". É o teste mínimo que detecta *deriva de ponta* (release publicada sem nenhuma documentação), mas não detecta *deriva de intervalo* (release documentada na raiz e ausente no arquivo derivado).
- **De convenção não verificada:** o `quality/CHANGELOG.md` nunca teve regra exigindo paridade com a raiz. A suíte de qualidade e o changelog de produto são documentos com ritmos distintos, mas a paridade era presumida, nunca verificada.

Não é falha de agente nem de bump nesta ocasião — o Antigravity documentou corretamente a v3.56.3 nos dois arquivos. O furo era do guardrail.

## Impacto

- Leitor do changelog de qualidade não encontra a v3.56.0 nem a v3.56.1, embora ambas tenham sido publicadas e tenham ADR.
- Baixo: nenhum impacto em runtime, e a raiz do repositório está completa — quem busca a versão encontra.

## Correção (`DOC-003`)

1. **Regra 15 (Cobertura da Série)** adicionada ao `validate_test_docs.py`: extrai a série de versões do `CHANGELOG.md` raiz e exige que **toda** versão da série corrente (`major.minor` do código) esteja presente no `quality/CHANGELOG.md`. Escopo deliberadamente restrito à série corrente — séries antigas (3.55.x e anteriores) têm convenções diferentes e ficariam fora.
2. Entradas retroativas da **v3.56.0** e **v3.56.1** adicionadas ao `quality/CHANGELOG.md`, em ordem decrescente.
3. A Regra 14 foi refatorada para deduplicar a série preservando a ordem de aparição, preparando a Regra 15 sem reparsear o arquivo.

## Verificação

| Cenário | Resultado esperado | Obtido |
|---|---|---|
| Estado real pré-correção (`8f563040`) | falhar com 2 lacunas | ✅ erro da Regra 15 citando v3.56.1, v3.56.0 |
| Após adicionar as entradas | verde | ✅ exit 0 |
| Remover v3.56.1 do `quality/CHANGELOG.md` | falhar citando a versão | ✅ Regra 15, 1 versão |
| Código em v3.57.0 (série diferente) | Regra 14 falha; Regra 15 **não** exige as 3.56.x | ✅ só Regra 14 disparou |

O teste da série diferente é o que confirma que a Regra 15 é cirúrgica: ela não exige que versões antigas continuem documentadas no arquivo derivado, o que geraria dívida histórica infinita.

## Prevenção

- Bump futuro com buraco no meio da série agora **quebra o `lawfirm-ci.yml`** pela Regra 15, mesmo que a release atual esteja documentada corretamente.
- Regra 14 + Regra 15 cobrem os dois modos de falha: *deriva de ponta* (release sem nenhuma doc) e *deriva de intervalo* (release sem doc no arquivo derivado).
