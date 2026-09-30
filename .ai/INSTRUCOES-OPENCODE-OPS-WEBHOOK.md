# 🚀 INSTRUÇÕES PARA O OPENCODE — Implementar o `OPS-WEBHOOK-001`

> **Destinatário:** OpenCode (Implementer) · **Preparado por:** Hermes (QA Architect) · **Data:** 2026-09-30
> **Leia antes:** `.ai/REGRAS-OPENCODE-OPERACAO.md` (obrigatório), `.ai/REGRAS-DE-CONCORRENCIA.md` (lock/worktree), `quality/runbooks/webhook-secrets.md` (runbook)

---

## 1. O que você vai fazer — e onde para

A task-mãe `OPS-WEBHOOK-001` foi **dividida em três** porque o `ADR-GOV-002` exige um `WRITE OWNER` por vez e as etapas não corroem no mesmo escopo.

| Task | Dono | O que |
|---|---|---|
| **`OPS-WEBHOOK-ENV-001`** | **VOCÊ** | Stack de testes + provar fail-closed → aceito |
| `OPS-WEBHOOK-SEC-001` | DSK7 (operador) | Criar tokens nos painéis e gravá-los no banco |
| `OPS-WEBHOOK-VER-001` | Hermes | Veredito final |

**Sua task é a `ENV-001`.** Quando precisar dos tokens, **pare e reporte** — não improvise valor.

---

## 2. Sequência de execução

### Passo 1 — Lock e task

```bash
# criar .ai/locks/OPS-WEBHOOK-ENV-001.lock.yaml (formato em .ai/locks/README.md)
# owner: OPENCODE
# write_scope: o que voce realmente for alterar (verifique antes!)
```
Transicionar `OPS-WEBHOOK-ENV-001` para `IN_PROGRESS` no `.ai/TASKS.md`.

### Passo 2 — Ambiente

```bash
git checkout 2.1 && git pull --ff-only origin 2.1
cp .env.example .env
docker compose -f docker-compose.test.yml --profile e2e up -d
```

### Passo 3 — Guard de isolamento (obrigatório antes de qualquer requisição)

```bash
docker compose -f docker-compose.test.yml exec app-tenant-a env \
  | grep -E "APP_ENV|TEST_ENVIRONMENT_ACK|DB_DATABASE"
```
Esperado: `APP_ENV=testing`, `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`, bancos com sufixo `_test`.

🛑 **Qualquer banco sem sufixo `_test` → pare e reporte.** É violação de segurança.

### Passo 4 — Provar fail-closed ANTES de qualquer cadastro

Se o endpoint já retorna 2xx sem token, há bug de segurança — o cadastro não resolve.

```bash
# 1) sem token -> DEVE negar (401/403)
curl -i -X POST http://localhost/api/webhooks/asaas \
  -H "Content-Type: application/json" -d '{}'
```

### Passo 5 — Provar aceitação (quando o operador tiver cadastrado)

```bash
# 2) com token (via alias, NUNCA literal no comando/log)
curl -i -X POST http://localhost/api/webhooks/asaas \
  -H "asaas-access-token: $ASAAS_WEBHOOK_TOKEN" \
  -H "Content-Type: application/json" -d '{}'
# sinal de sucesso: DEIXOU de ser 401. Pode ser 4xx de payload invalido.
```

Repita para os 4 endpoints: `api/webhooks/chatwoot`, `api/webhooks/whatsapp-messenger/{tenant}`, `api/lawfirm/saas/webhook`.

### Passo 6 — Encerrar

Lock `RELEASED`, `TASKS.md` e `LOG_INDEX.md` atualizados, entrada em `.ai/logs/OPENCODE.md`.

---

## 3. Regra inviolável — segredo não passa por você

`ADR-GOV-005`. **Nunca**: pedir/receber/registrar valor de token em chat, arquivo, log ou commit. **Sempre**: alias (`CHATWOOT_TEST_TOKEN`, `QA_ADMIN_CREDENTIAL`, `EVOLUTION_WEBHOOK_SECRET`) e `pytest.skip` quando ausente.

O operador grava o valor direto no banco. Você **verifica que está lá** (existe? o endpoint aceita?), sem que o valor passe por você.

---

## 4. O que você **não** pode declarar

Você **não** fecha a `OPS-WEBHOOK-001`. E há um limite material:

> Mesmo com tudo verde, **a conciliação financeira ponta a ponta não é validável agora** — a cadeia `QA-DATA-001 → QA-HARNESS-001 → QA-JUR-001` está `BLOCKED` e os testes `implemented_unverified` não têm onde rodar. Reportar "pronto para o piloto" seria falso.

Seu relatório deve declarar o que **foi provado** (endpoints negando/aceitando, stack de pé, guard OK) e o que **ficou de fora**.

---

## 5. Furos de documentação — não confie cegamente

| Furo | O que fazer |
|---|---|
| 20 testes `active` foram verificados em **v3.55.x**, código em **v3.56.3** | "20 ativos" ≠ 20 válidos. Nenhum foi verificado na versão corrente |
| `GAP-002` / `GAP-003` abertos em `quality/KNOWN_GAPS.md` | O catálogo não mostra esses débitos; cobertura real é menor que a nominal |
| `INC-2026-09-15` não está no repo | Desconfie de referências a `.ai/incidents/` que você não encontrar |
| `INC-2026-09-27` cita rota inexistente | Herança do fork; confira o caminho real antes de seguir docs |

---

## 6. Além disso (opcional, se houver tempo)

Implementar `CHATWOOT-E2E-001` — **já foi criado** em `tests/e2e/workflows/test_chatwoot_sac_workflow.py` (e o Page Object em `pages/chatwoot_page.py`), em status `implemented_unverified`. Cobre ACL do menu SAC, middleware 403 sem o add-on e isolamento da assinatura entre tenants. Está no mesmo escopo de QA que você vai mexer, e **precisa do ambiente do Passo 2** para rodar.

Se rodar, atualize o catálogo: `CHATWOOT-E2E-001` → `active` com `last_verified_version` e `last_verified_date`.

---

## 7. Checklist

- [ ] Lock criado, task em `IN_PROGRESS`, `write_scope` real
- [ ] Stack de 9 serviços de pé
- [ ] Guard de isolamento confirmado
- [ ] 4 endpoints **negando** sem token (prova de fail-closed)
- [ ] 4 endpoints **aceitando** com token (via alias)
- [ ] Nenhum segredo em log, arquivo versionado ou relatório
- [ ] Limite de "pronto para o piloto" declarado
- [ ] Lock `RELEASED`, `TASKS.md` + `LOG_INDEX.md` + log atualizados

---

*Task: `OPS-WEBHOOK-ENV-001` · Preparado por Hermes, 2026-09-30*
