# Changelog

## [v1.6] - 2026-01-25 - Assistentes Jurídicos IA & WhatsApp Improvements
### Added
- **Módulo Assistentes Jurídicos IA**: Sistema de templates para geração de prompts jurídicos com variáveis dinâmicas.
  - `AssistantController`: Index, Show, Generate e Execute actions.
  - `AssistantTemplate` model: Templates com prompts estruturados e variáveis.
  - `AssistantHistory` model: Histórico de execuções por usuário.
  - Views: `admin/assistants/index.blade.php` e `show.blade.php`.
- **N8nService**: Integração com webhooks N8n para execução remota de workflows de IA.
  - Suporte a URLs completas ou rotas relativas via `N8N_WEBHOOK_BASE_URL`.
  - Timeout de 60s para chamadas de IA.
- **SaasQuotaService**: Novo serviço para verificação de cotas de uso.
- **Migration `create_lawfirm_assistants_tables`**: Tabelas `law_assistant_templates` e `law_assistant_histories`.
- **Migration `alter_tamanho_to_bigint_on_anexos`**: Suporte a arquivos maiores (bigint).

### Changed
- **WhatsApp ConnectionController**: Refatoração para suportar Evolution API v2.
  - Parsing dinâmico de campos `name`/`instanceName` e `connectionStatus`/`status`.
  - Logging detalhado para debug de conexão.
  - Limpeza local forçada em caso de falha de API remota.
- **EvolutionService**: Priorização de `.env` sobre System Config para credenciais.
- **Menu**: Adicionado item "Assistentes IA" com permissão `lawfirm.assistants`.

### Technical
- Total de Migrations: 19 (anteriormente 17)
- Total de Controllers Admin: 6 (anteriormente 3)
- Total de Models: 11 (anteriormente 8)
- Total de Services: 5 (anteriormente 3)

---

## [v1.5] - SaaS Module & WhatsApp Init
### Added
- **SaaS Core:** Configuração oculta via Seeder (`lawfirm.saas.*`), Service de Controle de Storage e Lógica de Bloqueio (Middleware).
- **SaaS Dashboard:** Interface visual para o cliente acompanhar Status, Armazenamento (Barra de Progresso) e Créditos de IA.
- **SaaS Webhook:** API Segura (`/api/lawfirm/saas/webhook`) para receber comandos externos de recarga e atualização de plano.
- **WhatsApp Integration:** Service Layer para Evolution API (Guzzle), Controller de Conexão e View com geração de QR Code via AJAX.

### Changed
- **ACL:** Reestruturação "Achatada" (Flat) das permissões (`lawfirm.processos`, `lawfirm.saas_dashboard`, etc.) para corrigir bugs de menu.
- **Uploads:** Inclusão de verificação de cota de disco antes de salvar arquivos.

## [v1.4.1] - 2026-01-20 - Security & Access Control Fixes
### Fixed
- **Erro 500 no Financeiro:** Corrigido erro "Object of type Webkul\Core\Acl is not callable" causado por middleware ACL manual nos Controllers. Krayin gerencia ACL automaticamente via `menu.php` e `acl.php`.
- **Loops de Redirecionamento:** Corrigido método inexistente `hasRole()` nos DataGrids - substituído por verificação nativa do Krayin (`role_id != 1` para admin).
- **Erro mapMany():** Substituído `mapMany()` por `flatMap()` em todos os DataGrids e Services para compatibilidade com Laravel.

### Changed
- **Estrutura de Menu/ACL:** Hierarquia "achatada" removendo nível intermediário `juridico`. Estrutura agora: `lawfirm.processos`, `lawfirm.prazos`, `lawfirm.financial`, `lawfirm.settings`.
- **Nomenclatura:** Menu "Configurações" renomeado para "Dados do Escritório" para evitar conflito com menu Core do Krayin.
- **Controllers Middleware:** Removido middleware ACL manual de `ProcessoController`, `PrazoController` e `FinancialController` - Krayin gerencia via config.

### Security
- **User Scoping:** Implementada lógica de escopo de usuário em `prepareQueryBuilder()` nos DataGrids:
  - `ProcessoDataGrid`: Filtra por `processos.user_id`
  - `PrazoDataGrid`: Filtra por `processos.user_id` (via join)
  - `FinancialDataGrid`: Filtra por `processos.user_id` (via join)
  - `FinancialDashboardService`: Filtra por `processos.user_id` nas métricas
  - Administradores (`role_id = 1`) veem todos os registros
  - Usuários com `view_permission = 'group'` veem registros do grupo
  - Usuários com `view_permission = 'individual'` veem apenas seus registros

---

## [v1.4] - 2024-XX-XX (Atual)
### Added
- Configuração de Upload de Arquivos com Nomenclatura Estrita (`ID-Hash_Slug.ext`).
- Service Provider e Configurações iniciais do pacote LawFirm.
- Views de PDF (Procuração, Contrato) com suporte a UTF-8 e Logo.

### Changed
- Refatoração do DataGrid de Prazos (Cores Pastel).
- Ajuste de Traduções (pt_BR) forçadas para evitar fallback.
- Correção de caminhos absolutos para imagens no DomPDF.
