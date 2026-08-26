# 💬 Módulo: Atendimento Centralizado / Chatwoot (chatwoot)

## 1. Objetivo
Centralizar o atendimento multicanal, live chat e distribuição de tickets no Chatwoot através do menu SAC da plataforma e do padrão Dual Inbox (humano + assistente IA), preservando o controle de acesso por add-on e as invariantes de mapeamento de canais. O Whaticket foi completamente descontinuado e removido do projeto, sendo o Chatwoot a solução exclusiva de atendimento.

## 2. Escopo
- **Menu da Plataforma**: Menu top-level "SAC" no sidebar (`packages/SuiteZap/LawFirm/src/Config/menu.php`, key: `sac`, route: `lawfirm.assistants.chatwoot`).
- **Controle de Acesso / ACL**: Permissão `lawfirm.assistants.chatwoot` (`packages/SuiteZap/LawFirm/src/Config/acl.php`).
- **Middleware de Add-on**: `CheckChatwootModule` (`packages/SuiteZap/LawFirm/src/Atendimento/Http/Middleware/CheckChatwootModule.php`) que verifica se o módulo `CHATWOOT` está ativo em `active_modules` na assinatura do tenant (`MotherShipService::getCurrentSubscription()`), retornando HTTP 403 se ausente.
- **Página do Hub SAC**: `AssistantController::chatwoot()` (`packages/SuiteZap/LawFirm/src/AI/Http/Controllers/Admin/AssistantController.php`) que renderiza a view `lawfirm::admin.assistants.chatwoot` com credenciais e URL para acesso ao portal Chatwoot.
- **Configuração de Nós e Invariantes**: Resolução via `MotherShipService::getChatwootConfig` e operações via `ChatwootService`.

## 3. Fonte Arquitetural
- `packages/SuiteZap/LawFirm/src/Atendimento/Services/ChatwootService.php`
- `packages/SuiteZap/LawFirm/src/Atendimento/Http/Middleware/CheckChatwootModule.php`
- `packages/SuiteZap/LawFirm/src/AI/Http/Controllers/Admin/AssistantController.php`
- `packages/SuiteZap/LawFirm/src/Config/menu.php` e `acl.php`
- `ARCHITECTURE.md §4.88`
- `GUARDRAILS.md` (Incidente de 2026-07-01)

## 4. Comportamentos Conhecidos
- **Invariantes Críticas de Mapeamento**:
  - `account_id` $\leftrightarrow$ `chatwoot_inbox_id` (legado)
  - `inbox_id` $\leftrightarrow$ `chatwoot_channel_inbox_id` (canal de atendimento humano)
  - `assistant_inbox_id` $\leftrightarrow$ `chatwoot_assistant_inbox_id` (canal de IA com fallback e `Log::warning`)
  - `access_token` $\leftrightarrow$ `chatwoot_webhook_token` (User Access Token para `/labels`, `/contacts` e HMAC)
  - `api_key` $\leftrightarrow$ `node.api_key` (Bot Token para `POST /messages`)
- **Acesso ao Menu SAC**: Exige usuário com permissão ACL `lawfirm.assistants.chatwoot` e tenant com add-on `CHATWOOT` ativo.
- **Zero Dependência de Whaticket**: Nenhuma rota, migration ou biblioteca de Whaticket é necessária ou existe no projeto.

## 5. Testes Associados
- `CHATWOOT-FEATURE-001`: Preservação das invariantes de configuração e credenciais do Chatwoot em `tests/Feature/ChatwootConfigTest.php` (Status: `implemented_unverified` $\rightarrow$ `active` condicional na Etapa 2).

## 6. Lacunas Conhecidas
- Testes E2E de navegação no painel SAC mapeados para automação via mock local nas fases subsequentes.

## 7. Última Revisão
- Data: 2026-08-21
- Versão: v3.55.0
