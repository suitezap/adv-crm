# 📋 INSTRUÇÕES PARA O ANTIGRAVITY — Fechar o que está pendente

> **Data:** 2026-10-01 · **Autorizado pelo DSK7** · **Por:** Hermes (QA Architect)
> **Repositório:** LawFirm · **Base:** `2.1` @ `5256ffcf`
> **Você é o dono de 3 tasks + 1 desbloqueio.** Tudo o mais está travado atrás delas.

---

## 📖 Contexto

O Syncthing voltou a funcionar (estava parado desde 14/09 por watchdog). Com a sincronização, todo o trabalho que estava apenas no DSK7 chegou ao servidor — e a verificação revelou **2 furos de segurança** que eu não tinha visto antes.

O Hermes **não tem Docker**. Só o DSK7 tem. Por isso a `OPS-WEBHOOK-ENV-001` está com você — atribuição que eu corrigi hoje (estava com o OpenCode, que também não tem o ambiente).

---

## 🎯 Prioridades — esta é a ordem

```
1. WEBHOOK-SEC-002  🔴 furo de segurança   — rota pública que credita saldo
2. WEBHOOK-SEC-003  🟡 token opcional      — falha aberto
3. OPS-WEBHOOK-ENV-001 🟢 ambiente         — só você tem Docker
4. Desbloquear KAN-001 🟡 dependência de janela
```

**O passo 1 vem antes do 3** porque o `ENV-001` vai exercitar justamente os endpoints com furo. Testar um endpoint que credita saldo sem autenticação valida um comportamento que em produção seria vulnerabilidade.

---

## 🔴 1. `WEBHOOK-SEC-002` — Escavador sem autenticação

### O problema

`POST /api/webhooks/escavador` é **rota pública** — declarada em `app/Http/Middleware/VerifyCsrfToken.php:18` como isenta de CSRF.

E `packages/SuiteZap/LawFirm/src/Escavador/Http/Controllers/WebhookController.php` **não tem autenticação nenhuma**. Busquei `token`, `signature`, `hmac`, `secret`, `apikey`, `hash_` no arquivo inteiro: o único match é um comentário (`linha 19: "Isenta de CSRF"`).

A única guarda é a linha 71:
```php
if (! $escavadorRequest->isPending()) {
    return response()->json(['status' => 'already_processed'], 200);
}
```

Isso é **idempotência**, não autenticação. Impede processar o mesmo request duas vezes — não impede o primeiro processamento por qualquer um.

### O dano

```php
// linha 82
$this->refundBalance($escavadorRequest->tenant_id, $escavadorRequest->cost);
// linha 171
$subscription->increment('suitecoin_balance', $cost);
```

Um POST forjado com `{"id":"<external_id>","status":"erro"}`:
1. Localiza o `EscavadorRequest` pelo `external_id` (linhas 36-39)
2. `markFailed()` (linha 81)
3. **`refundBalance()` → credita `suitecoin_balance`**

Além disso sobrescreve `status_atualizacao` (93) e `resumo_ia` (110, 112).

**É o único dos 5 webhooks sem guarda:**

| Webhook | Autenticação |
|---|---|
| Chatwoot | HMAC + `X-Chatwoot-Signature` |
| WhatsApp | `webhook-token` |
| tenant-Asaas | `access-token` + `webhook-token` |
| Asaas (SaaS) | `access-token` (mas opcional — ver task 2) |
| **Escavador** | ❌ **nenhuma** |

### A correção

**1 — Secret no MotherShip**, mesmo padrão dos outros: `meta_data.webhook_token` do nó Escavador.

**2 — Middleware** em `src/Escavador/Http/Middleware/VerifyEscavadorWebhook.php`, reaproveitando o padrão que já existe no projeto:

```php
public function handle(Request $request, Closure $next)
{
    $secret   = config('services.escavador.webhook_secret');
    $received = $request->header('X-Escavador-Token', '');

    // FAIL-CLOSED: secret ausente rejeita TUDO.
    // Fail-open (aceitar sem secret) seria o oposto — errado.
    if (empty($secret)) {
        Log::error('EscavadorWebhook: secret não configurado — rejeitado.', [
            'ip' => $request->ip(),
        ]);
        return response()->json(['status' => 'unauthorized'], 401);
    }

    // hash_equals, não == (evita timing attack)
    if (! hash_equals($secret, $received)) {
        Log::warning('EscavadorWebhook: token inválido.', ['ip' => $request->ip()]);
        return response()->json(['status' => 'unauthorized'], 401);
    }

    return $next($request);
}
```

**3 — Registrar o middleware na rota**, antes do controller.

**4 — Fail-closed também no `refundBalance`.** Verifique que não há outro caminho que credite saldo sem passar pelo middleware.

### Testes obrigatórios

```bash
# 1. sem header → 401
curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8000/api/webhooks/escavador \
  -H 'Content-Type: application/json' -d '{"id":"<id_real>","status":"erro"}'
# esperado: 401

# 2. token errado → 401
# 3. secret NÃO configurado + qualquer token → 401  (fail-closed)

# 4. token correto → 200

# 5. CONFERIR NO BANCO: nenhum saldo foi estornado nos casos de 401
```

**O teste 5 é o que prova a correção.** Os 401 sozinhos não bastam — confirme que `suitecoin_balance` não mudou.

---

## 🟡 2. `WEBHOOK-SEC-003` — `asaas-access-token` opcional

### O problema

`packages/SuiteZap/LawFirm/src/SaaS/Http/Controllers/AsaasWebhookController.php`, docblock linha 28:

> *"Este token é opcional mas altamente recomendado."*

O `isAuthorized()` falha **aberto**: sem token configurado, qualquer POST é aceito.

**O que já está bom:** o controller retorna **200** mesmo na falha de auth (linha 46-47), deliberadamente, para não revelar a rota ao atacante. Não mexa nisso sem decisão do DSK7 — mudar para 401 implica o Asaas poder retententar.

### A correção

1. **Warning explícito** no log quando o token não estiver configurado — para não passar despercebido
2. **Documentar** em `quality/runbooks/webhook-secrets.md` que sem o token o webhook fica aberto
3. **Levantar a questão do 200-vs-401** e deixar registrado — decisão do DSK7

---

## 🟢 3. `OPS-WEBHOOK-ENV-001` — o ambiente (você tem Docker)

### Passo 1 — Lock

```bash
# .ai/locks/OPS-WEBHOOK-ENV-001.lock.yaml
# owner: ANTIGRAVITY
# write_scope: apenas o que você realmente for alterar
```
Transicionar para `IN_PROGRESS` no `.ai/TASKS.md`.

### Passo 2 — Subir o stack

```bash
git checkout 2.1 && git pull --ff-only origin 2.1
cp .env.example .env
docker compose -f docker-compose.test.yml --profile e2e up -d
```

O compose tem **12 serviços**: `mysql-test`, `mothership-db-test`, `redis-test`, `mock-server`, `php-tests`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b`, `playwright-test`, e 2 de dados.

### Passo 3 — Guard de isolamento (OBRIGATÓRIO antes de qualquer requisição)

```bash
docker compose -f docker-compose.test.yml exec app-tenant-a env \
  | grep -E "APP_ENV|TEST_ENVIRONMENT_ACK|DB_DATABASE"
```

Esperado: `APP_ENV=testing`, `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`, bancos com sufixo `_test`.

🛑 **Qualquer banco sem sufixo `_test` → pare e reporte.** É violação de segurança.

### Passo 4 — Fail-closed nos 4+1 endpoints

Depois que o `WEBHOOK-SEC-002` for corrigido:

| Endpoint | Sem token | Com token errado | Com token correto |
|---|---|---|---|
| `api/webhooks/chatwoot` | 401 | 401 | 200 |
| `api/webhooks/asaas` | 200* | 200* | 200 |
| `api/webhooks/tenant-asaas` | 401 | 401 | 200 |
| whatsapp | 401 | 401 | 200 |
| `api/webhooks/escavador` | 401 | 401 | 200 |

\* o Asaas (SaaS) retorna 200 por design — está no `WEBHOOK-SEC-003`.

### Passo 5 — O que me entregar

Relatório em `.ai/handoffs/RESULT-OPS-WEBHOOK-ENV-001.md` com:

1. `docker compose ps` (todos os 12 serviços)
2. Saída do guard de isolamento
3. **Tabela de resultados do fail-closed** com os códigos HTTP reais
4. `docker compose logs --tail=50` de qualquer serviço que tenha dado erro
5. Confirmação explícita de que nenhum banco sem `_test` foi usado

**Não passe valor de segredo por mim** (`ADR-GOV-005`). Use aliases e reporte só o status.

---

## 🟡 4. Desbloquear `KAN-001`

Está `BLOCKED` por `tag-maintenance` desde **2026-09-12** — quase 3 semanas.

**Verifique no DSK7 se essa janela já passou.** Se passou, atualize `.ai/TASKS.md` e retome. Se ainda não, registre a nova data estimada em vez de deixar a entrada sem previsão.

Causa registrada: *"workspace fechado p/ atualização de tags (operador, 2026-09-12)"*.

---

## 🔍 5. Inconsistência que encontrei nas tasks de QA

Não é sua task, mas precisa da sua ajuda:

```
QA-ENV-001      DONE     (provisionou o ambiente)
QA-DATA-001     BLOCKED  bloqueada por QA-ENV-001  ← que já está DONE
QA-HARNESS-001  BLOCKED  bloqueada por QA-DATA-001
QA-JUR-001      BLOCKED  bloqueada por QA-HARNESS-001
```

**As 3 estão travadas por uma dependência que já foi cumprida.** `QA-ENV-001` está `DONE` com resultado em `.ai/handoffs/RESULT-QA-ENV-001.md`.

Isso é exatamente a classe de bug que o `QA-JUR-001` existe para resolver — a pergunta original do DSK7 era sobre *scripts que percorrem todos os menus procurando erros estruturais*.

**O que precisa acontecer:** confirmar se `QA-ENV-001` entregou de fato o que `QA-DATA-001` precisa. Se entregou, liberar `QA-DATA-001`. Se faltou algo, registrar o que faltou em vez de manter o `BLOCKED` sem explicação.

---

## 🚫 Regras

1. **Lock antes de escrever.** `.ai/locks/{TASK_ID}.lock.yaml` com `owner`, `write_scope`, `base_commit`
2. **Nunca `git add -A` na pasta sincronizada** — versiona `.stversions/` e `.agents/skills/`. Use `git add <arquivo>` explícito
3. **Nunca segredo por agente** (`ADR-GOV-005`) — aliases, e `pytest.skip` onde não houver
4. **Não apagar segredo do banco** ao mascará-lo
5. **`php -l` em todo PHP alterado**
6. **Não commitar WIP alheio** — a branch `feature/ajustes-no-leads-view` tem 97 arquivos modificados que não são seus
7. **Comentário de segurança não é correção** (lição da `SEC-MS-001`)

---

## 📋 Checklist

**WEBHOOK-SEC-002:**
- [ ] Lock criado, task em `IN_PROGRESS`
- [ ] `VerifyEscavadorWebhook` com fail-closed + `hash_equals`
- [ ] Middleware registrado na rota
- [ ] Nenhum outro caminho credita saldo sem passar pelo middleware
- [ ] 4 testes curl (401/401/401/200)
- [ ] **Saldo conferido no banco: nenhum estorno nos 401**
- [ ] `CHANGELOG.md` + `.ai/TASKS.md` atualizados

**WEBHOOK-SEC-003:**
- [ ] Warning quando token ausente
- [ ] `webhook-secrets.md` documenta o risco
- [ ] Questão do 200-vs-401 registrada

**OPS-WEBHOOK-ENV-001:**
- [ ] Lock criado, task em `IN_PROGRESS`
- [ ] Stack de 12 serviços no ar
- [ ] Guard de isolamento confirmado
- [ ] Tabela fail-closed dos 5 endpoints
- [ ] `RESULT-OPS-WEBHOOK-ENV-001.md` entregue

**Desbloqueios:**
- [ ] `KAN-001` — janela verificada
- [ ] `QA-DATA-001` — dependência satisfied ou lacuna registrada

---

## ⚠️ Depois

Com o `ENV-001` entregue, a sequência continua:

```
OPS-WEBHOOK-SEC-001  DSK7     popula os 6 segredos (inclui o novo do Escavador)
OPS-WEBHOOK-VER-001  Hermes   veredito de QA — eu valido
```

**Você não faz essas duas.** A `VER-001` é minha por definição: o veredito é QA.

---

*3 tasks + 1 desbloqueio. O item 1 é o mais grave: rota pública que credita saldo financeiro sem autenticação. Enviado pelo Hermes em 2026-10-01, base `2.1` @ `5256ffcf`.*

---

## ⚠️ PONTO CRÍTICO — leia antes de implementar o `WEBHOOK-SEC-002`

O HEADER NÃO É ESCOLHIDO POR NÓS. O Escavador é uma **API externa de terceiros**. Não há como
inventar `X-Escavador-Token` e esperar que ele chegue.

O que o código envia para o Escavador (e portanto o que ele espera de volta):

```php
// src/Escavador/Services/EscavadorService.php:199, 623
$http = Http::withToken($apiKey)
         ->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
```

E o callback é configurado **na API do Escavador** (`callbacks/marcar-recebidos`, `monitoramentos/testcallback`),
não no nosso painel.

### Passo 0 obrigatório — descobrir o que o Escavador pode enviar

**Antes de escrever o middleware, verifique a documentação da API do Escavador** (endpoint `callbacks`)
e o painel deles, procurando:

1. O Escavador envia algum header de autenticação/assinatura no callback?
2. Se sim, **qual é o nome exato**? (`X-...`, `Authorization`, assinatura HMAC?)
3. Existe campo no payload para validar (ex.: `signature`, `hash`)?
4. O painel tem campo para cadastrar um **token/secret de callback**?

### Três cenários de desfecho

**Cenário A — o Escavador suporta token de callback (provável)**
Use o header que a documentação indicar. Segue o middleware fail-closed como especificado.

**Cenário B — o Escavador NÃO suporta token (impossível confirmar)**
Não invente um header. Opções, em ordem de preferência:

- **B1 — HMAC do payload.** Se o callback incluir algum campo de assinatura, valide-o.
- **B2 — allowlist por origem.** Se a documentação disser o IP/faixa de origem do callback,
  use middleware que só aceita requisição vindo dela. É mais fraco que token (spoofing de
  IP é difícil mas não impossível), e **deve ser documentado como risco residual**.
- **B3 — HMAC ourselves.** Se o Escavador permitir enviar um segredo no payload ou na URL de callback
  (ex.: `?token=`), valide-o com `hash_equals`.

**Cenário C — nenhuma autenticação for possível**
**PARE e reporte ao DSK7.** Não entregue o endpoint sem guarda. Documente o risco e proponha
mitigação (ex.: só aceitar callback quando existir `EscavadorRequest` pendente **e** o payload
conferir com o que foi enviado na requisição original — correlação por `external_id` + campos
controlados por nós).

### Sobre a correlação (aplica a B2/B3/C)

O `EscavadorRequest` guarda o que foi enviado. Uma defesa útil, **complementar** à auth:
validar que o `external_id` do callback corresponde a uma requisição realmente feita e ainda
`pending`, e que os campos de controle (ex.: `versao_api`, `tipo_consulta`) batem. Isso não substitui
autenticação — mitiga o abuso de payloads forjados.

**Não invente o nome do header.** Um header que o Escavador não envia deixa o webhook permanentemente
em 401 e quebra a funcionalidade em produção. **Verificar a documentação primeiro é parte da task.**

---
