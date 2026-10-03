# INC-2026-09-15 — Sync-conflict em `.git/` durante DOCKER-002

- **Detectado em:** 2026-09-15 (scan `*sync-conflict*` pré-escrita, skill `sync-safety`)
- **Arquivo:** `.git/index.sync-conflict-20260912-175416-Z572YPM` (261 KB, 12/09 17:53)
- **Evento:** `SYNC_CONFLICT_DETECTED` → `STOP WRITE` mandatório (`AGENTS.md §4`)
- **Causa provável:** `.git/` sincronizado pelo Syncthing (violação de `AGENTS.md §4.3` — cada ambiente deve ter clone próprio; `.git/` nunca sincroniza)
- **Decisão:** operador autorizou explicitamente prosseguir com bump/build em 2026-09-15. Arquivo de conflito **não tocado nem removido**.
- **Ação pendente:** excluir `.git/` do `.stignore`/Syncthing em todos os ambientes e validar integridade do índice (`git status` funcional em 2026-09-15; working tree consistente no commit `826a4b63`).
- **Task relacionada:** `DOCKER-002` (lock `.ai/locks/DOCKER-002.lock.yaml`)

## Resolução (DOC-006, 2026-09-30)

**Causa confirmada:** o Syncthing sincronizava a pasta `Lawfirm` inteira, incluindo `.git/`, violando o `ADR-GOV-003`. Pior: **não existia arquivo `.stignore` algum** — nenhuma exclusão estava configurada em nenhum dos ambientes.

**Ação executada:** criado `.stignore` na raiz do repositório, versionado no git (para que os dois ambientes recebam a mesma regra ao sincronizar). Exclui:
- `.git/`, `**/.git/`, `*.pack`, `*.idx` — o alvo do incidente
- Segredos (`.env*`, `*.pem`, `*.key`, `*.p12`, `*.pfx`, `*.id_rsa`, `storage/oauth-*.key`) — `ADR-GOV-005`
- Artefatos de runtime/teste (`storage/logs/`, caches, `node_modules/`, `vendor/`, `.phpunit.cache/`, `test-results/`, `playwright-report/`, `coverage/`, `*.sql`)
- `*sync-conflict*` — impede propagar conflitos existentes

**Pendências reais desta resolução:**

1. **O serviço Syncthing está `inactive` no servidor Hermes.** O `.stignore` só tem efeito quando o serviço voltar a rodar (ver `.ai/CURRENT.md`, pendência de reinício). Enquanto isso, nada sincroniza — o que é seguro, mas significa que o trabalho entre DSK7 e VPS está parado.
2. **O `.stignore` precisa existir também na DSK7/Windows.** Como foi versionado, ele chega por git — mas a máquina Windows tem pasta própria configurada no Syncthing, e a regra só vale se o serviço local também a ler. **Ação do operador: reiniciar o Syncthing na DSK7 e confirmar que a pasta Lawfirm mostra `.stignore` aplicado.**
3. **Verificar se o conflict file ainda existe** — `.git/index.sync-conflict-20260912-175416-Z572YPM` (261 KB). O operador decidiu em 2026-09-15 **não** removê-lo. Agora, com `.git/` fora da sincronização, ele é inofensivo, mas segue ocupando espaço e deve ser inspecionado antes de qualquer remoção manual.
4. **Integridade do índice:** `git status` funcional e working tree consistente — verificado OK em 2026-09-30, `fsck` recomendado para environments com histórico longo.
