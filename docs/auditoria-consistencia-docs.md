# Relatório de Auditoria — Consistência de Documentação
**Data:** 2026-08-18 | **Escopo:** `ARCHITECTURE.md`, `ARCHITECTURE_dir.md`, `ARCHITECTURE_mothership_orient.md`, `AUDIT_REPORT.md`, `.agent/skills/krayin_lawfirm_dev/SKILL.md`  
**Versão de Referência do Sistema:** v3.54.1 (declarada em `LawFirmServiceProvider.php::VERSION` e cabeçalho de `ARCHITECTURE.md`)

> [!NOTE]
> Este relatório é **estritamente analítico e investigatório (somente leitura)** — nenhum arquivo de código ou documentação foi alterado nesta tarefa. Cada achado inclui a localização precisa (arquivo, seção e linha) e a comparação com o código-fonte real.

---

## 1. Informações Contraditórias Entre Documentos

### 1.1 Fórmula de Conversão de SuiteCoins (Valores Fixos vs. Multiplicador Dinâmico)

* **Gravidade:** MÉDIA (indução a erro arquitetural).
* **Documentos Envolvidos:** `ARCHITECTURE.md` (§4.69), `ARCHITECTURE_dir.md` (§7), `SKILL.md` (§5), `ARCHITECTURE_mothership_orient.md` (§12, §13).

#### O que cada documento afirma:
1. **`ARCHITECTURE.md §4.69` (linhas 337, 343-344, 349):**
   Apresenta a fórmula como constantes numéricas literais hardcoded:
   ```text
   Ƶ_exibido = preço_BRL_bruto × 10 × 1.25
   ```
   No front-end: `var pZ = p * 10 * 1.25;`
2. **`ARCHITECTURE_dir.md §7` (linhas 263 e 458):**
   Reafirma a fórmula com valores literais `× 10 × 1.25` sem explicitar a dependência dinâmica do MotherShip.
3. **`SKILL.md §5` (linha 139):**
   Descreve que a conversão é puramente em runtime para exibição na UI utilizando o multiplicador dinâmico vindo da tabela `app_config` do MotherShip:
   `"LawFirm converts it purely for UI display using the suitecoin_multiplier from app_config"`.
4. **`ARCHITECTURE_mothership_orient.md §12 e §13` (linhas 318 e 333-338):**
   Documenta que as chaves `suitecoin_rate` (default 10) e `suitecoin_markup` / `markup_factor` (default 1.25) residem no banco `mothership.app_config` e são consumidas dinamicamente.

#### Validação contra o Código-Fonte Real:
* **`packages/SuiteZap/LawFirm/src/SaaS/Services/SuiteCoinService.php`:**
  * Linha 42: `SuiteCoinService::getRate()` consulta dinamicamente `MotherShipService::getAppConfig('suitecoin_rate')` com fallback para `DEFAULT_RATE = 10.0`.
  * Linha 52: `SuiteCoinService::getMarkup()` consulta dinamicamente `MotherShipService::getAppConfig('suitecoin_markup')` com fallback para `DEFAULT_MARKUP = 1.25`.
  * Linha 75: `SuiteCoinService::toVirtual($brlAmount)` usa `self::getRate()`.
  * Linha 95: `SuiteCoinService::calculateServicePriceBrl($costBrl)` usa `self::getMarkup()`.
* **`packages/SuiteZap/LawFirm/src/SaaS/Services/MotherShipService.php`:**
  * Linha 149/178: `getEscavadorPrices()` busca `suitecoin_multiplier` em `app_config` com default `10`.

#### Conclusão e Diagnóstico:
* **Qual reflete o código atual:** `SKILL.md §5` e `ARCHITECTURE_mothership_orient.md §12/§13`. O sistema é **dinâmico e orientado a configuração global via MotherShip**.
* **O que está desatualizado/impreciso:** `ARCHITECTURE.md §4.69` e `ARCHITECTURE_dir.md §7`. Eles omitem que `10` e `1.25` são apenas fallbacks/defaults de `app_config`, dando a entender errôneamente que se trata de uma regra matemática fixa em código.

---

### 1.2 Nomes de Campo Retornados por `MotherShipService::getChatwootConfig()`

* **Gravidade:** ALTA (risco de `null pointer` / quebra de integração em novos desenvolvimentos).
* **Documentos Envolvidos:** `SKILL.md` (§3.3), `ARCHITECTURE_mothership_orient.md` (§20.1), `ARCHITECTURE.md` (§4.82, §4.85).

#### Tabela de Comparação de Contrato:

| Campo / Semântica | Código Real (`MotherShipService.php`) | `SKILL.md §3.3` | `orient.md §20.1` | `ARCHITECTURE.md §4.82/§4.85` | Situação |
|:---|:---|:---|:---|:---|:---|
| **URL Base da Instância** | `'base_url'` | `'url'` ❌ | `'url'` ❌ | `'base_url'` ✅ | **Divergência Crítica** |
| **Token do Bot (Mensagens)** | `'api_key'` | `'api_key'` ✅ | `'api_key'` ✅ | `'api_key'` ✅ | Alinhado |
| **ID da Conta Chatwoot** | `'account_id'` | `'account_id'` ✅ | `'account_id'` ✅ | `'account_id'` ✅ | Alinhado |
| **Inbox Atendimento Humano** | `'inbox_id'` | `'inbox_id'` ✅ | `'inbox_id'` ✅ | `'inbox_id'` ✅ | Alinhado |
| **Inbox Assistente IA** | `'assistant_inbox_id'` | `'assistant_inbox_id'` ✅ | `'assistant_inbox_id'` ✅ | *(não listado em §4.82)* | Alinhado |
| **User Access Token** | `'access_token'` | `'access_token'` ✅ | `'access_token'` ✅ | `'access_token'` / `'webhook_token'` | Alinhado |

#### Validação contra o Código-Fonte Real:
* **`packages/SuiteZap/LawFirm/src/SaaS/Services/MotherShipService.php` (linhas 535-542):**
  ```php
  return [
      'base_url'           => rtrim($node->base_url, '/'),
      'api_key'            => $node->api_key,
      'account_id'         => $meta['account_id'] ?? $tenantConfig->chatwoot_inbox_id ?? null,
      'inbox_id'           => $tenantConfig->chatwoot_channel_inbox_id ?? null,
      'assistant_inbox_id' => $tenantConfig->chatwoot_assistant_inbox_id ?? null,
      'access_token'       => $tenantConfig->chatwoot_webhook_token ?? null,
  ];
  ```
* **`packages/SuiteZap/LawFirm/src/Atendimento/Services/ChatwootService.php` (linha 31 e 49):**
  ```php
  $this->baseUrl = $config['base_url'] ?? '';
  ```

#### Conclusão e Diagnóstico:
* A chave canônica é **`base_url`**. Se um desenvolvedor ou IA seguir o exemplo de `SKILL.md §3.3` (`$n8nConfig['url']` / `$chatwootConfig['url']`) ou `orient.md §20.1`, o acesso à URL resultará em `null`, causando falha de conexão HTTP.
* **`ARCHITECTURE.md §4.85`** descreve com precisão a resolução histórica do bug de ambiguidade entre `account_id` (lido de `chatwoot_inbox_id`) e `inbox_id` (lido de `chatwoot_channel_inbox_id`), estando 100% em conformidade com o código atual.

---

### 1.3 `VERSION` Desatualizado em `ARCHITECTURE_mothership_orient.md` e `SKILL.md`

* **Gravidade:** MÉDIA.
* **Localização:**
  * `ARCHITECTURE_mothership_orient.md §16.6` (linhas 505-512): Documenta `VERSION = '3.53.0'` como referência canônica da plataforma e data de 22/06/2026, ignorando as versões v3.54.0 e v3.54.1 que foram adicionadas no final do arquivo (§20).
  * `SKILL.md` (linha 5): Declara `# LawFirm CRM - Architecture Standards (v3.53)`, enquanto o projeto está em `v3.54.1`.

---

## 2. Seções com Numeração Duplicada ou Referências Quebradas

### 2.1 Numeração Duplicada e Ordem Cronológica Invertida em `ARCHITECTURE.md`

* **Gravidade:** ALTA (compromete a rastreabilidade de ADRs e regras).

| Seção Duplicada | Ocorrência 1 (Linha / Título) | Ocorrência 2 (Linha / Título) | Ocorrência 3 | Diagnóstico |
|:---|:---|:---|:---|:---|
| **§4.32** | L265: *Security Hardening do ACL (v3.18)* | L401: *Idempotência do Mothership e Deploy (v3.17)* | — | Colisão de números de versão antiga e nova |
| **§4.33** | L275: *Consolidação Top-Level Financeiro (v3.45)* | L282: *Masking via Watchers Vue (v3.45)* | L410: *Krayin Localization (v3.17)* | **Triplicada** com versões discrepantes |
| **§4.34** | L289: *Migração do whatsapp_responsavel (v3.45)* | L419: *Integração Asaas Sincronização (v3.18)* | — | Colisão entre v3.45 e v3.18 |
| **§4.65** | L296: *Unificação de Status de Entidades (v3.46.1)* | L925: *Global JS State Injection (v3.46.0)* | — | Colisão de número na mesma release |
| **§4.66** | L303: *Organização do Menu Assistentes (v3.47)* | L935: *Kanban Operacional de Casos (v3.46.0)* | — | Colisão entre v3.47 e v3.46 |
| **§4.87 vs §4.85** | L1199: *§4.87 Sincronização Labels (v3.54.0)* | L1289: *§4.85 Correção Bug Chatwoot (v3.54.1)* | — | §4.87 aparece **antes** de §4.85 e §4.86 |

* **Inversão Cronológica de Bloco:**
  As linhas 265 a 324 (que tratam de v3.45 até v3.48 — Financeiro, Masking, Status, Assistentes, SuiteCoins) foram coladas no topo da seção 4, antes do bloco histórico legítimo de v3.17 a v3.44 (linhas 401 a 850).

---

### 2.2 Bloco Inteiro Duplicado em `ARCHITECTURE_dir.md`

* **Gravidade:** ALTA (duplicação maciça de ~315 linhas).
* **Localização:** `ARCHITECTURE_dir.md`.
  * **Bloco Original:** Linhas 194 a 388 (Seções 5, 6.6, 6.7, 7, 8, 9, 9.1, 10, 10.1, 11, 11.1, 12).
  * **Bloco Duplicado:** Linhas 389 a 580 (as mesmas seções repetidas na íntegra palavra por palavra).

---

## 3. Trechos com Problemas de Encoding (Caracteres Corrompidos)

### 3.1 `ARCHITECTURE_mothership_orient.md`
* **Localização:** Linha 464.
* **Texto Corrompido:** `// âŒ Scaffold do orient (orientação conceitual)`
* **Texto Pretendido:** `// ❌ Scaffold do orient (orientação conceitual)`
* **Causa:** O emoji `❌` (`\xE2\x9D\x8C`) foi gravado com perda de byte/mojibake.

*(Nota: Os arquivos `ARCHITECTURE.md`, `ARCHITECTURE_dir.md`, `AUDIT_REPORT.md` e `SKILL.md` passaram com zero caracteres de controle corrompidos e zero mojibake).*

---

## 4. Decisões Arquiteturais Obsoletas ou Superadas

> [!NOTE]
> **Esclarecimento sobre `sendAssistantMessage()`:** A suspeita inicial de que o método `ChatwootService::sendAssistantMessage()` estaria ausente do código era **falsa**. O método está implementado em `packages/SuiteZap/LawFirm/src/Atendimento/Services/ChatwootService.php` (linhas 132 a 170) e validado com 27 testes automatizados em `tests/Feature/ChatwootConfigTest.php` (100% aprovados). A documentação em `SKILL.md §3.3` e `ARCHITECTURE_mothership_orient.md §20.1` está correta.

### 4.1 Descomissionamento Total do Whaticket Messenger
* **Status:** ✅ RESOLVIDO (Ago/2026).
* **Diagnóstico Anterior:** O documento `ARCHITECTURE_whats.md` e o `AGENTS.md` definiam o Whaticket como suspenso, mas `ARCHITECTURE.md §4.72` mantinha menção ativa.
* **Resolução Aplicada:** O submódulo Whaticket, suas rotas, controllers e o documento `ARCHITECTURE_whats.md` foram completamente removidos do repositório, e as decisões consolidadas no ADR §4.88 do `ARCHITECTURE.md` e em `ARCHITECTURE.br`.

### 4.2 Substituição de `ExchangeRateService` por `SuiteCoinService`
* **Gravidade:** BAIXA.
* **Localização:** `ARCHITECTURE.md §4.68` (linhas 315-323).
* **Diagnóstico:** A seção 4.68 descreve a criação do `ExchangeRateService` na v3.47. Na v3.48 ele foi consolidado e substituído pelo `SuiteCoinService`, mas a seção 4.68 não possui uma nota de "Superado por §4.69 / SuiteCoinService".

### 4.3 Status de Migrations Pendentes no `orient.md`
* **Gravidade:** BAIXA.
* **Localização:** `ARCHITECTURE_mothership_orient.md §16.5` e `§20.1` (linhas 497-503 e 739-745).
* **Diagnóstico:** Tabelas continuam rotulando tarefas como `⏳ Pendente` (ex: migração de `chatwoot_channel_inbox_id`, ajuste de endpoints e rotas), quando na verdade já foram totalmente implementadas e validadas no CRM na v3.54.1.

---

## Tabela Resumo dos Achados

| ID | Tipo | Severidade | Arquivo(s) | Localização | Ação Recomendada Futura |
|:---|:---|:---:|:---|:---|:---|
| **1.1** | Contradição: SuiteCoins fixo vs dinâmico | MÉDIA | `ARCHITECTURE.md`, `ARCHITECTURE_dir.md` | §4.69 (ln 337), §7 (ln 263) | Esclarecer que `10` e `1.25` são defaults de `app_config` |
| **1.2** | Contradição: chave `url` vs `base_url` no Chatwoot | **ALTA** | `SKILL.md`, `orient.md` | `SKILL.md §3.3` (ln 95), `orient.md §20.1` (ln 646) | Corrigir `'url'` para `'base_url'` |
| **1.3** | Versão desatualizada no cabeçalho | BAIXA | `orient.md`, `SKILL.md` | `orient.md §16.6` (ln 509), `SKILL.md` (ln 5) | Atualizar referências para `v3.54.1` |
| **2.1** | Seções duplicadas / fora de ordem | **ALTA** | `ARCHITECTURE.md` | §4.32, §4.33, §4.34, §4.65, §4.66, §4.87 | Reorganizar e renumerar sequencialmente |
| **2.2** | Bloco massivo duplicado (~315 linhas) | **ALTA** | `ARCHITECTURE_dir.md` | Linhas 389 a 580 | Remover a segunda cópia idêntica |
| **3.1** | Mojibake / caractere quebrado | MÉDIA | `orient.md` | Linha 464 (`âŒ`) | Substituir por `❌` limpo em UTF-8 |
| **4.1** | Descomissionamento Whaticket | RESOLVIDO | `ARCHITECTURE.md`, `SKILL.md` | ADR §4.88 | ✅ Removido definitivamente do repositório |
| **4.2** | ADR superado sem marcação de histórico | BAIXA | `ARCHITECTURE.md` | §4.68 (ln 315) | Anotar substituição pelo `SuiteCoinService` |
| **4.3** | Checklists marcados pendentes já concluídos | BAIXA | `orient.md` | §16.5 (ln 497), §20.1 (ln 739) | Atualizar checklist para `✅ Concluído` |