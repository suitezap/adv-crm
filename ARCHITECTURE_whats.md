# 📲 WhatsApp Messenger (Whaticket) — Arquitetura e Evolução

> [!CAUTION]
> **FUNCIONALIDADE SUSPENSA DESDE 29/05/2026:**
> Este sub-sistema (Whaticket Messenger Inbox) está suspenso e não fará parte das versões posteriores. As rotas e controladores do Chat/Inbox foram desabilitados.
> As demais funcionalidades de WhatsApp (Envio de faturas, alertas de monitoramento, importação de histórico por processo e agendador de prazos) permanecem ativas, testadas e 100% funcionais.

> [!NOTE]
> Este documento detalha o histórico do sub-sistema de **Mensageria e Atendimento via WhatsApp** do LawFirm CRM, inspirado na arquitetura do projeto open-source Whaticket. Documentação principal em `ARCHITECTURE.md — Seção 4.72`.

---

## 1. Avaliação de Integração: Isolado ou Integrado?

### Veredicto: **HÍBRIDO** — Implementado dentro do LawFirm, com múltiplos pontos de integração.

O módulo **NÃO é** o pacote `packages/SuiteZap/Whaticket/` (que é um esqueleto vazio de migrations não registradas). A implementação completa **vive dentro do domínio `Whatsapp` do pacote LawFirm** (`packages/SuiteZap/LawFirm/src/Whatsapp/`).

### Mapa de Integrações

| Domínio Integrado | Ponto de Contato | Tipo |
|:---|:---|:---|
| **SaaS** | `MotherShipService::getEvolutionConfig()` | Credenciais da Evolution API (Zero .env) |
| **SaaS** | `MotherShipService::getTenantId()` | Isolamento multi-tenant em todas as queries |
| **Legal** | `DB::table('persons')` | Auto-vinculação contato WhatsApp ↔ Person CRM |
| **Whatsapp (core_config)** | `lawfirm.whatsapp_templates.messages.farewell_message` | Mensagem de despedida configurável |
| **Whatsapp (Jobs)** | `SendWhatsappMessageJob` | Envio assíncrono via fila (Redis/DB) |

### O que o pacote `SuiteZap/Whaticket` contém?

```
packages/SuiteZap/Whaticket/src/
└── Database/
    └── Migrations/
        └── 2026_05_06_000001_create_whaticket_tickets_tables.php
```

> [!WARNING]
> Este pacote **NÃO está registrado** em nenhum `ServiceProvider`. Suas migrations **nunca foram executadas**. As tabelas `whaticket_*` originais foram **substituídas** por um schema interno ao LawFirm via migrations dedicadas em `packages/SuiteZap/LawFirm/src/Database/Migrations/`.

---

## 2. Estrutura do Módulo (Implementação Real)

```text
LawFirm/src/Whatsapp/
├── Commands/
│   └── ImportWhatsappHistory.php          # Comando Artisan manual para importação
├── Http/
│   └── Controllers/
│       ├── ConnectionController.php        # QR Code, Status, Disconnect da instância
│       ├── WhatsappWebhookController.php   # Receptor público de eventos da Evolution API
│       └── Admin/
│           ├── WhatsappChatController.php  # ← MESSENGER INBOX (Whaticket)
│           ├── WhatsappImportController.php # Importação de histórico por processo
│           └── WhatsappTemplatesController.php # Editor de templates de mensagens
├── Jobs/
│   ├── SendWhatsappMessageJob.php          # Job para envio de texto (queued)
│   ├── SendMediaJob.php                    # Job para envio de mídia (queued)
│   └── ImportProcessoWhatsappMessages.php  # Job de importação em background
├── Listeners/
│   └── SendScheduledPrazoNotifications.php # Listener de prazos → WhatsApp
├── Models/
│   ├── WhatsappContact.php                 # Contato vinculado (phone → person_id)
│   ├── WhatsappTicket.php                  # Conversa (status: pending/open/closed)
│   └── WhatsappMessage.php                 # Mensagem (com ACK tracking)
└── Services/
    ├── MessengerService.php                # Serviço central de mensageria
    └── EvolutionService.php                # Cliente HTTP da Evolution API
```

---

## 3. Schema de Banco de Dados

As tabelas são criadas pelas migrations do **LawFirm** (não do pacote Whaticket), com `tenant_id` em todas para isolamento multi-tenant:

```sql
-- Contacts: sincronizados automaticamente via Webhook
whaticket_contacts (id, tenant_id, phone, name, person_id [FK → persons.id], ...)

-- Tickets: conversas por contato
whaticket_tickets (id, tenant_id, contact_id, status('pending'|'open'|'closed'), user_id, last_message_id, ...)

-- Messages: mensagens individuais
whaticket_messages (id [string — Evolution ID], tenant_id, ticket_id, from_me, type, body [JSON], ack [0-4], ...)

-- Tags e Filas (scaffold, não totalmente implementadas)
whaticket_queues, whaticket_tags, whaticket_ticket_tags
```

### ACK (Confirmação de Leitura)

| Valor | Significado |
|:---|:---|
| `0` | ⏳ Aguardando envio |
| `1` | ✓ Enviado |
| `2` | ✓✓ Entregue |
| `3` | ✓✓ Lido (azul) |
| `4` | ▶ Reproduzido (áudio) |

---

## 4. Rotas da UI (Messenger Inbox)

Todas sob o grupo `prefix('admin/juridico/whatsapp')`, middlewares `['web', 'admin_locale', 'user']`:

| Método | URL | Nome da Rota | Ação |
|:---|:---|:---|:---|
| `GET` | `/whatsapp/messenger` | `messenger` | View principal do Inbox |
| `GET` | `/whatsapp/messenger/tickets` | `messenger.tickets` | JSON: lista de tickets (AJAX polling) |
| `GET` | `/whatsapp/messenger/tickets/{id}/messages` | `messenger.messages` | JSON: mensagens do ticket |
| `POST` | `/whatsapp/messenger/tickets/{id}/accept` | `messenger.accept` | Aceitar ticket pendente |
| `POST` | `/whatsapp/messenger/tickets/{id}/close` | `messenger.close` | Encerrar ticket |
| `POST` | `/whatsapp/messenger/tickets/{id}/send` | `messenger.send` | Enviar mensagem de texto |
| `POST` | `/whatsapp/messenger/tickets/{id}/send-media` | `messenger.send_media` | Enviar mídia |
| `POST` | `/whatsapp/messenger/upload-media` | `messenger.upload` | Upload de arquivo para URL pública |
| `POST` | `/whatsapp/messenger/start-conversation` | `messenger.start` | Iniciar nova conversa por telefone |

---

## 5. Fluxo de Mensagens

### 5.1 Recebimento (Incoming — Evolution → CRM)

```
Evolution API (Webhook POST /api/webhooks/whatsapp-evolution)
    ↓
WhatsappWebhookController::handle()
    ↓ identifica tenantId via header X-Evolution-Instance ou paylaod
MessengerService::processIncoming($tenantId, $rawMessage)
    ├── findOrCreate WhatsappContact (com auto-link para persons via findKrayinPersonId)
    ├── findOrCreate WhatsappTicket (reaproveita ticket pending/open existente)
    └── updateOrCreate WhatsappMessage (idempotente via evolution_message_id)
```

**Idempotência:** Chamadas repetidas com o mesmo `evolution_message_id` são seguras — `updateOrCreate` garante que a mensagem não seja duplicada.

### 5.2 Envio (Outgoing — CRM → Evolution)

```
WhatsappChatController::sendMessage()
    ↓ valida status open/pending, lê tenantId
SendWhatsappMessageJob::dispatch($tenantId, $ticketId, $text)
    ↓ (Job na fila Redis/DB)
MessengerService::sendText($tenantId, $ticket, $text)
    ↓ MotherShipService::getEvolutionConfig() → credenciais sem .env
GuzzleHttp Client → Evolution API /message/sendText/{instance}
    └── Salva WhatsappMessage com from_me=true e ack=1
```

**Envio Otimista:** A UI exibe a bolha da mensagem imediatamente antes da confirmação da API (UI Optimistic Update Pattern).

---

## 6. Interface (Messenger UI)

View: `LawFirm/src/Resources/views/Whatsapp/messenger.blade.php`

- **Padrão de Implementação:** Portal Dialog + Vanilla JS (§6.1 do ARCHITECTURE.md) — sem Vue/Alpine
- **Layout:** Dois painéis (sidebar de conversas + área de chat), estilo WhatsApp Web
- **Polling:** Atualização automática via `setInterval` a cada **10 segundos**
- **Mídia:** Upload de arquivo → URL pública → envio via Evolution API
- **ACK visual:** ⏳ / ✓ / ✓✓ / ✓✓ azul baseado no campo `ack`

### Global JS Object

```javascript
window.lfM = {
    loadTickets(),      // Carrega/filtra lista de tickets
    openTicket(),       // Abre conversa e inicia polling
    accept(),           // Aceita ticket pendente
    close(),            // Encerra ticket (com mensagem de despedida)
    sendMessage(),      // Envio de texto com otimismo de UI
    promptNewChat(),    // Inicia nova conversa via prompt(phone)
    searchTickets(),    // Filtro local por nome/telefone
    handleFile()        // Upload + envio de mídia em 2 fases
}
```

---

## 7. Histórico de Evolução do Módulo

| Versão | Decisão | Detalhes |
|:---|:---|:---|
| **v3.10** | Integração WhatsApp Financeiro | `FinancialController::sendWhatsappBilling()` — cobrança via WhatsApp |
| **v3.13** | Monitoramentos e Alertas Escavador | Notificações automáticas de publicações jurídicas via WhatsApp |
| **v3.18** | Herança de Token de Nó | `MotherShipService::getEvolutionConfig()` usa token master herdado se tenant não tiver chave própria |
| **v3.19** | QR Code Auto-Refresh | Daemon client-side atualiza QR a cada 40s sem reload da página |
| **v3.27** | Robô Agendador de Prazos | Job `SendScheduledPrazoNotifications` — WhatsApp automático diário (dailyAt 07:00) |
| **v3.29** | Importação de Histórico | Job `ImportProcessoWhatsappMessages` — ingere histórico da Evolution por processo |
| **v3.31** | Multi-Import por Sessão | Tabela `law_whatsapp_imports` — agrupa importações com status por sessão |
| **v3.37** | Consolidação de Templates | Todos os templates de mensagens unificados em `lawfirm.whatsapp_templates.messages.*` |
| **v3.56 (atual)** | **Messenger Inbox** | Interface completa de atendimento estilo Whaticket: tickets, mensagens bidirecionais, mídia, polling |

---

## 8. Pontos de Atenção e Próximos Passos

> [!WARNING]
> **Pacote Fantasma:** O diretório `packages/SuiteZap/Whaticket/` contém migrations nunca executadas. Ele deve ser ou **removido** (se não houver plano de uso) ou **migrado** oficialmente com um `LawFirmServiceProvider` dedicado.

> [!IMPORTANT]
> **Webhook público:** A rota `/api/webhooks/whatsapp-evolution` (ou equivalente) deve estar na lista de exceções do `VerifyCsrfToken`. Verificar se está documentada e protegida por validação de assinatura HMAC.

> [!NOTE]
> **Filas obrigatórias:** O envio de mensagens (`SendWhatsappMessageJob`) é assíncrono. Em ambientes sem worker de fila ativo, as mensagens ficam presas em `PENDING`. O Docker Swarm deve ter um container `worker` dedicado.
