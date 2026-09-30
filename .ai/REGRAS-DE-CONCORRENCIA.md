# 🔒 REGRAS DE CONCORRÊNCIA E WORKTREE — LawFirm CRM

> **Obrigatório para qualquer agente ou humano** que edite código neste repositório.
> Complementa o `AGENTS.md` §3 (locks) e §4 (Syncthing) com o procedimento que faltava.
> Status: **ATIVA** desde 2026-09-30

---

## Por que esta regra existe

Em 2026-09-30 a árvore local tinha trabalho não commitado de uma sessão anterior, em `feature/ajustes-no-leads-view`: 4 arquivos modificados e 2 untracked, **sem lock, sem task no `TASKS.md` e sem entrada no log**. E `lead-tools-panel.blade.php` — um deles — era o mesmo arquivo alterado pelo commit remoto `cef01638` (modal Chatwoot). Rebase nessa árvore teria conflitado sobre trabalho alheio.

A task `DOC-002` só pôde ser reaplicada porque foi para um **worktree isolado**.

---

## 1. Toda edição começa com lock

**Antes de editar qualquer arquivo**, crie `.ai/locks/{TASK_ID}.lock.yaml`:

```yaml
task_id: DOC-004
owner: OPENCODE              # ANTIGRAVITY | OPENCODE | HERMES | humano
write_scope:                  # caminhos EXATOS que você vai tocar
  - packages/SuiteZap/LawFirm/src/...
  - tests/e2e/...
started_at: "2026-09-30T22:00:00Z"
last_checkpoint_at: "2026-09-30T22:00:00Z"   # atualizar a cada etapa
base_commit: "d3b6223f"
workspace: "vps-hermes"
status: ACTIVE                 # ACTIVE | RELEASED
```

Ao terminar: `status: RELEASED` e a task no `TASKS.md` como `DONE`.

**Sem lock, o trabalho é invisível para os outros agentes e se perde ou conflita.** Isso já aconteceu.

## 2. `write_scope` é o limite, não uma sugestão

Tudo que você altera deve estar no escopo. Antes do commit:

```bash
git status --porcelain | awk '{print $2}' | sort > /tmp/ch.txt
# compare com o write_scope do seu lock
```

Se algo fora do escopo mudou, **não commite junto** — reverta com `git checkout --` ou peça o lock correspondente.

## 3. Nunca troque de branch numa árvore com WIP

`git checkout` numa árvore sujo **falha** — e está certo. Se quiser rebasear, atualizar a `2.1` ou trocar de branch, use worktree:

```bash
git fetch origin
git worktree add -b <branch> /home/rootz/lawfirm-<slug> origin/2.1
cd /home/rootz/lawfirm-<slug>

# vendor/ e .env não são versionados — symlink, nunca cópia
ln -sfn /home/rootz/Sync/Lawfirm/vendor vendor
ln -sfn /home/rootz/Sync/Lawfirm/.env .env
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
```

O worktree principal (`/home/rootz/Sync/Lawfirm`) pode ter WIP alheio a qualquer momento. **Trabalhe em worktree; só volte ao principal quando ele estiver limpo.**

## 4. Antes de assumir que o local reflete o remoto

```bash
GIT_SSH_COMMAND="ssh -i ~/.ssh/id_ed25519_account -o IdentitiesOnly=yes" git fetch origin
git log --oneline HEAD..origin/2.1 | wc -l
```

Em 2026-09-30 o local estava **30 commits atrás**. Avaliar antes de rebasing evita descobrir a divergência no meio da operação.

## 5. Lock órfão: nunca apague

Se encontrar lock `ACTIVE` de outro agente com `last_checkpoint_at` antigo: **sinalize `STALE_LOCK_SUSPECTED` e reporte ao Orchestrator (Antigravity) ou ao DSK7.** Nunca `rm` o arquivo — `ADR-GOV-002`.

## 6. Syncthing é transporte, não versionamento

- **`.git/` nunca é sincronizado** (`ADR-GOV-003`). O incidente `INC-2026-09-15` registrou sync-conflict no índice exatamente por isso, e a ação corretiva (excluir `.git/` do Syncthing) **ainda não foi executada**.
- Qualquer arquivo `*sync-conflict*` → `STOP WRITE` mandatório (`AGENTS.md` §4).
- Cada ambiente tem clone próprio. Antes de `pull`, `checkout` ou `rebase`, rode o scan.

```bash
find . -name "*sync-conflict*" -not -path "./vendor/*" 2>/dev/null
```

## 7. Backup antes de tocar em trabalho alheio

Trabalho não commitado de outro agente não tem histórico — perdê-lo é irreversível. Antes de qualquer operação na árvore alheia:

```bash
BK=~/.hermes/cache/wip-backup-<branch>-$(date +%Y%m%d)
mkdir -p "$BK" && cp --parents <arquivos> "$BK"
find "$BK" -type f -exec md5sum {} \;    # registre os hashes
```

## 8. O remote usa SSH

O `origin` do `LawFirm` usa **HTTPS com token sem escrita** — o push falha com `Permission denied`. Troque:

```bash
git remote set-url origin git@github.com:suitezap/adv-crm.git
```

E use `GIT_SSH_COMMAND="ssh -i ~/.ssh/id_ed25519_account -o IdentitiesOnly=yes"`.

---

## Checklist antes de commitar

- [ ] Lock criado, `status: ACTIVE`, `write_scope` cobre tudo que vou alterar
- [ ] `git status --porcelain` mostra **apenas** arquivos do meu escopo
- [ ] Scan `*sync-conflict*` limpo
- [ ] Nenhum arquivo de outro agente alterado por mim
- [ ] Worktree limpo de `.phpunit.cache/` e artefatos de teste (rodar Pest altera arquivo versionado)
- [ ] Mensagem de commit no padrão do repositório

## Checklist antes de dar task por concluída

- [ ] Lock `RELEASED`
- [ ] `.ai/TASKS.md` atualizado
- [ ] `.ai/LOG_INDEX.md` atualizado (é derivado do `TASKS.md` — divergência entre os dois já ocorreu três vezes)
- [ ] `.ai/logs/{AGENTE}.md` com o que foi feito
- [ ] Se alterou versão: `REGRAS-DE-RELEASE.md` conferida

---

## Relacionamentos
- [[AGENTS.md]] §3 (locks) e §4 (Syncthing) — regras originais
- [[REGRAS-DE-RELEASE.md]] — o outro par de regras obrigatórias
- [[.ai/locks/README.md]] — formato canônico do lock
- `incidents/INC-2026-09-15-sync-conflict-git-index.md` — a origem da regra 6
