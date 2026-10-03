# 📜 REGRAS PARA O OPENCODE — Operação, Segredos e Qualidade

> **Escopo:** `OPS-WEBHOOK-001` e afins. Complementa `.ai/REGRAS-DE-RELEASE.md` e `.ai/REGRAS-DE-CONCORRENCIA.md` — **leia os três**.
> **Owner do documento:** Hermes (QA Architect) · **Destinatário:** OpenCode (Implementer) · **Data:** 2026-09-30

---

## 0. Seu papel nesta task (leia antes de começar)

Você é o `IMPLEMENTER` em `Dev Environment` (`AGENTS.md` §2) — que é **onde está o Docker**. Por isso a maior parte do `OPS-WEBHOOK-001` é sua.

Mas **esta task não é inteiramente sua**, e isso é deliberado. Três partes, três donos:

| Parte | Dono | O que é |
|---|---|---|
| `OPS-WEBHOOK-ENV-001` | **OPENCODE** (você) | Subir o stack, verificar guards, comprovar fail-closed → aceito |
| `OPS-WEBHOOK-SEC-001` | **DSK7 (operador)** | Criar tokens nos painéis externos e gravá-los direto no banco |
| `OPS-WEBHOOK-VER-001` | **HERMES** | Validar comportamento e encerrar a `OPS-WEBHOOK-001` |

**Por que dividir:** o `ADR-GOV-002` exige **um `WRITE OWNER` por vez**, e as três etapas não corroem no mesmo escopo. Passar tudo para um agente único mistura ambiente, segredo e veredito — e o terceiro é QA, não implementação.

**Onde você para:** quando os tokens não existirem. **Pare e reporte**; não invente valor, não use placeholder em código, não siga com token de teste "só para validar".

---

## 1. Regra inviolável — segredo não passa por agente

`ADR-GOV-005`: nenhuma credencial real em arquivos, logs, handoffs, `.ai/`, commits ou issues.

**O que você NUNCA faz:**
- Pedir, receber ou registrar o valor de um token de webhook em conversa, arquivo ou variável que vá para o git.
- Escrever token em `.env` **versionado**, em `docker-compose*.yml`, ou em qualquer arquivo sob versionamento.
- Colocar segredo em `print()`, `Log::`, `dd()`, exceção ou mensagem de erro.
- Usar token de produção em ambiente de teste, ou o inverso.

**O que você faz:**
- Usar **aliases** nos scripts: `CHATWOOT_TEST_TOKEN`, `QA_ADMIN_CREDENTIAL`, `MOTHERSHIP_TEST_CREDENTIAL`, `EVOLUTION_WEBHOOK_SECRET`.
- Ler de variável de ambiente com **skip explícito** quando ausente — o padrão já existente no `conftest.py`:
  ```python
  token = os.getenv("CHATWOOT_TEST_TOKEN")
  if not token:
      pytest.skip("CHATWOOT_TEST_TOKEN não definida — injetar via secret store.")
  ```
- Pedir ao operador **que ele grave direto no banco**, fora de qualquer canal que você leia.

> **Justificativa:** o valor do token passa pelo histórico do chat, pelo log do terminal e possivelmente pelo buffer do agente. Uma vez exposto, um segredo é considerado comprometido e precisa ser rotacionado. Por isso o operador insere sozinho.

---

## 2. Os 6 cadastros — quem faz o quê

| # | Onde | Chave | Dono |
|---|---|---|---|
| 1 | `mothership.app_config` | `api_secret` | **você** (SQL em banco local) |
| 2 | `tenants` | `chatwoot_webhook_token` | **você** |
| 3 | `mothership.infrastructure_nodes.meta_data` | `webhook_token` (Asaas) | **operador** cria · **você** verifica |
| 4 | `mothership.infrastructure_nodes.meta_data` | `webhook_secret` (Evolution) | **operador** cria · **você** verifica |
| 5 | `mothership.infrastructure_nodes.meta_data` | `sac_password` | **operador** |
| 6 | Painel Asaas + painel Evolution | URL e token de webhook | **operador** (exige login) |

**Ordem:** 1 e 2 primeiro (habilitam a comunicação), depois 3 (o mais crítico para o piloto), depois 4, depois 5.

**Obrigatório antes de validar:** o valor gerado no painel precisa estar gravado **nos dois lados** — no nó do MotherShip *e* no painel externo. Divergência aí produz falha silenciosa que é difícil de diagnosticar.

---

## 3. Ambiente — o que você monta

```bash
git checkout 2.1 && git pull --ff-only origin 2.1
cp .env.example .env
docker compose -f docker-compose.test.yml --profile e2e up -d
```

Serviços esperados (9): `mysql-test`, `mothership-db-test`, `redis-test`, `mock-server`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b`, `playwright-test`.

**Guard de isolamento — obrigatório antes de qualquer coisa:**
```bash
docker compose -f docker-compose.test.yml exec app-tenant-a env | grep -E "APP_ENV|TEST_ENVIRONMENT_ACK|DB_DATABASE"
```
Esperado: `APP_ENV=testing`, `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`, bancos com sufixo `_test`.

🛑 **Se qualquer container apontar para banco sem sufixo `_test`, pare imediatamente** e reporte. O `DatabaseSafetyGuard` existe para impedir acesso a dados de produção; ignorá-lo é violação de segurança.

---

## 4. Prova de fail-closed — a ordem correta

**Não cadastre segredo antes de provar que o caminho nega.** Se o endpoint já retorna 200 sem token, há bug de segurança e o cadastro não resolve.

**Passo 1 — sem segredo (deve negar):**
```bash
curl -i -X POST http://localhost/api/webhooks/asaas \
  -H "Content-Type: application/json" -d '{}'
# esperado: 401 ou 403 — NUNCA 2xx
```

**Passo 2 — com segredo (deve deixar de ser "sem token"):**
```bash
curl -i -X POST http://localhost/api/webhooks/asaas \
  -H "asaas-access-token: $ASAAS_WEBHOOK_TOKEN" \
  -H "Content-Type: application/json" -d '{}'
# esperado: deixa de ser 401. Pode ser 4xx de payload inválido — o sinal é que saiu do "sem token".
```

O mesmo para os outros endpoints: `api/webhooks/chatwoot` (HMAC `X-Chatwoot-Signature`), `api/webhooks/whatsapp-messenger/{tenant}` (`X-Webhook-Token`), `api/lawfirm/saas/webhook` (`X-SAAS-TOKEN`).

---

## 5. Critério de conclusão — o que você NÃO pode declarar

Você fecha a **`OPS-WEBHOOK-ENV-001`** quando: stack sobe, guards confirmados, e os 4 endpoints passam de "nega" para "aceita token".

Você **não** pode fechar a **`OPS-WEBHOOK-001`**. Ela exige:
- Os 6 cadastros verificados ponta a ponta (parte do operador)
- **Conciliação financeira validada** — evento de pagamento real chegando e acknowledged
- Veredito do `HERMES` (`OPS-WEBHOOK-VER-001`)

> **Honestidade obrigatória:** mesmo com tudo verde, **a conciliação ponta a ponta não é validável agora**. A cadeia `QA-DATA-001 → QA-HARNESS-001 → QA-JUR-001` está `BLOCKED` e os 24 testes `implemented_unverified` não têm onde rodar. Dizer "pronto para o piloto" seria falso. Reporte o que foi provado e o que ficou de fora.

---

## 6. Procedimento obrigatório (`.ai/REGRAS-DE-CONCORRENCIA.md`)

1. **Lock primeiro:**
   ```bash
   # copiar .ai/locks/README.md e preencher
   # .ai/locks/OPS-WEBHOOK-ENV-001.lock.yaml
   ```
2. **Transicionar a task** para `IN_PROGRESS` em `.ai/TASKS.md`.
3. **`write_scope` é limite.** Nada fora dele, nem "só um ajuste".
4. **Backup antes de tocar** em qualquer árvore com trabalho alheio.
5. Ao terminar: lock `RELEASED`, `TASKS.md` atualizado, `.ai/LOG_INDEX.md` atualizado (é **derivado** do `TASKS.md` — divergência entre os dois já ocorreu 3 vezes), e entrada em `.ai/logs/OPENCODE.md`.

---

## 7. Furos de documentação que você pode tropeçar

Detectados na auditoria de 2026-09-30. **Não confie nestes documentos:**

| Furo | Onde | O que fazer |
|---|---|---|
| Catálogo cita teste inexistente | `quality/TEST_CATALOG.yaml` → `CHATWOOT-E2E-001` aponta para `test_chatwoot_sac_workflow.py`, que **não existe**; `quality/COVERAGE_MATRIX.md:83` idem | Não conclua que o teste existe. Se for implementar, ele é um dos 4 `planned` |
| **Validador não pega arquivo faltante em `planned`** | Regra 4 só checa `implemented_unverified`, `active`, `quarantined`, `disabled` | `validate_test_docs.py` **verde não garante** que todo teste citado existe |
| Incidente citado e ausente | `.ai/TASKS.md` referencia `.ai/incidents/INC-2026-09-15-sync-conflict-git-index.md`, que **não está no repo** (só na árvore local, não commitado) | Não procure esse arquivo no repo. Ele documenta que **`.git/` não pode ser sincronizado** |
| Nenhum teste `active` verificado na versão corrente | 20 testes `active`, todos com `last_verified_version` em `v3.55.0`/`v3.55.1`; código em **v3.56.3** | "20 testes ativos" **não** significa 20 testes válidos hoje. São ~3 releases atrás |
| GAP-002 / GAP-003 abertos | `quality/KNOWN_GAPS.md` — domínios fora da Fase 1; testes de IA com mock (`AI_REAL_TESTS=false`) | Não trate o catálogo como cobertura total da plataforma |

---

## 8. O que você deve reportar

Ao terminar, em `.ai/logs/OPENCODE.md` e na task:

1. **Provas:** quais endpoints negaram sem token e quais aceitaram com token (com o HTTP status, **sem** o valor).
2. **Stack:** os 9 serviços de pé, e a saída do guard de isolamento.
3. **Pendências:** quais dos 6 cadastros ficaram por conta do operador.
4. **Limite:** declarar explicitamente que a conciliação ponta a ponta **não** foi validada e por quê.
5. **Nada de segredo** no relatório.

---

## Checklist

- [ ] Lock criado, task em `IN_PROGRESS`, `write_scope` definido
- [ ] Stack de 9 serviços de pé
- [ ] Guard de isolamento confirmado (`APP_ENV=testing`, bancos `_test`)
- [ ] 4 endpoints **negando** sem token (prova de fail-closed)
- [ ] 4 endpoints **aceitando** com token (via alias, nunca valor literal)
- [ ] Nenhum segredo em log, arquivo versionado ou relatório
- [ ] Pendências do operador listadas
- [ ] Limite de "pronto para o piloto" declarado no relatório
- [ ] Lock `RELEASED`, `TASKS.md` + `LOG_INDEX.md` + log atualizados

---

*Tasks: `OPS-WEBHOOK-ENV-001` (você) · `OPS-WEBHOOK-SEC-001` (operador) · `OPS-WEBHOOK-VER-001` (Hermes)*
*Runbook: `quality/runbooks/webhook-secrets.md` · Preparado por Hermes, 2026-09-30*
