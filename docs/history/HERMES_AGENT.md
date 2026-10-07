# 🧭 HERMES_AGENT.md — LawFirm CRM

> Arquivo de contexto para o assistente Hermes (Quiron).
> Leitura obrigatoria antes de qualquer analise, validacao ou documentacao deste projeto.
> O Antigravity deve consultar este arquivo para entender o contexto de supervisao.

---

## Proposito do Hermes neste Projeto

O Hermes atua como **Supervisor de Arquitetura e Organizacao**. Suas funcoes:

1. Monitorar a estrutura DDD (Domain-Driven Design) estabelecida na migracao v3.1
2. Validar se a estrutura fisica do codigo condiz com as regras documentadas nos `ARCHITECTURE_*.md`
3. Rastrear integridade referencial no banco (ex: `ai_executions`, checklists, `core_config`)
4. Verificar dependencias de modulos que consomem dados do MotherShip
5. Manter `.agents/architecture_history.md` com linha do tempo
6. Documentar no Obsidian vault com wikilinks

**O que o Hermes NAO faz:** gerar codigo, executar scripts, ou formular prompts para o Antigravity.

---

## Documentos de Arquitetura

| Arquivo | Localizacao | Escopo |
|---------|-------------|--------|
| `ARCHITECTURE_LawFirm_orient.md` | `../MotherShip/` | Guia de integracao CRM ↔ MotherShip (variaveis .env, precificacao, propagacao de cache) |

> Nota: Os arquivos `ARCHITECTURE_*` do LawFirm residem no repositorio MotherShip porque documentam a integracao entre os dois projetos. O LawFirm nao possui `ARCHITECTURE_*` proprio na raiz — utiliza os bundles Antigravity (`.agents/skills/plugins/`) para arquitetura DDD.

---

## Estrutura de Diretorios (validada em 2026-07-19)

```
LawFirm/
├── .agents/             # Agentes de IA (Claude, Gemini, Hermes)
│   └── skills/
│       ├── plugins/     # Bundles Antigravity (DDD, arquitetura, design)
│       └── skills/      # Skills individuais
├── my-skills/           # 121 skills DDD organizadas por dominio
├── packages/            # Pacotes Laravel customizados
├── resources/           # Views, assets
├── storage/             # Logs, cache, uploads
├── vendor/              # Dependencias Composer
└── docker/              # Configuracao Docker
```

## Conexoes com MotherShip

```
LawFirm CRM (Laravel)
  ├── MotherShipService → mothership_db (MySQL)
  │   ├── tenants, subscriptions, billing
  │   ├── infrastructure_nodes (tokens: Asaas, Evolution, MinIO, N8N)
  │   ├── app_config (precificacao, tarifas)
  │   └── connector_configs (catalogo n8n)
  ├── SuiteCoins (Ƶ) → saas_transactions
  └── Escavador API → precificacao externa
```

---

## Estado Atual

- **Versao CRM**: v3.53.0 (Jul/2026)
- **Versao DDD**: v3.1
- **MotherShip Panel**: v1.22
- **Cache CRM**: Escavador 60min, DataJud sem cache, pricing sem cache
- **Pendencias n8n**: Refatorar conectores para usar banco `zerniwoot`

---

*Arquivo criado por Hermes (Quiron) em 2026-07-19. Atualizar a cada nova sessao de supervisao.*