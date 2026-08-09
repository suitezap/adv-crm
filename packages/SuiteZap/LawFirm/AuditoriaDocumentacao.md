# Documentação Arquitetural — SuiteZap/LawFirm

> Complemento ao `AUDITORIA.md`. Registra **decisões de design** e **isenções documentadas** identificadas nos ciclos de auditoria.

---

## 1. Ausência Intencional de `Repositories/` em Domínios

### Situação
Os domínios **Financial**, **GED**, **AI** e **Escavador** não possuem o subdiretório `Repositories/`.

### Decisão
A ausência é **intencional e aceitável** pelos seguintes motivos:

| Domínio | Justificativa |
|---------|---------------|
| **Financial** | Operações financeiras são simples (1 model `Lance`). Qeries diretas via Eloquent no `FinancialService`. Repositório adicionaria indireção sem benefício. |
| **GED** | Documentos são gerenciados pelo `DocumentService` que encapsula toda a lógica de acesso. O Model `ProcessDocument` é acessado apenas pelo Service — equivalente funcional de um Repository. |
| **AI** | `AssistantTemplate` e `AssistantHistory` são lidos via `MotherShipService::getAvailableAssistants()` (banco MotherShip). Repositório local não faz sentido para dados do MotherShip. |
| **Escavador** | `EscavadorService` e `EscavadorV2Service` encapsulam toda a lógica de persistência. Dados de movimentações são cacheados via `EscavadorProcesso`/`EscavadorMovimentacao` com queries simples. |

### Regra
Repositórios são obrigatórios **apenas quando** o domínio tem queries complexas com múltiplas condições reutilizadas. Services que encapsulam o acesso a dados equivalem a um Repository Pattern implícito e são aceitos.

**Domínios com Repositories obrigatórios:** Legal (✅), SaaS (✅).

---

## 2. Isenção do `SaasFileService` para Console Commands

### Situação
A Regra 2.2 do SKILL.md proíbe `Storage::` fora do `SaasFileService`. `CalculateStorageUsage` (Console Command) usava `Storage::disk('s3')` diretamente.

### Decisão (v3.49)
Console Commands **não** têm isenção da Regra 2.2. A regra se aplica igualmente.

**Correção aplicada:** `CalculateStorageUsage` refatorado para usar `SaasFileService::listAll()` e `SaasFileService::size()`. O `SaasFileService` recebeu os dois novos métodos públicos.

### Padrão para futuras Commands
```php
// ✅ CORRETO — injeta via app() para respeitar isolamento multi-tenant
$fileService = app(SaasFileService::class);
$files = $fileService->listAll('/');
$size  = $fileService->size($file);

// ⛔ ERRADO — Storage:: direto ignora o bucket resolvido pelo MotherShipService
$files = Storage::disk('s3')->allFiles();
```

---

## 3. Rota de Diagnóstico `/admin/juridico/debug/test-s3`

### Situação
A rota era implementada como closure inline na rota com `Storage::` direto.

### Decisão (v3.49)
- Lógica movida para `SaaSController::testS3Connection()` que delega ao `SaasFileService::testConnection()`.
- Rota protegida por guard `APP_DEBUG=true` no controller — retorna 403 em produção.
- Método `testConnection()` adicionado ao `SaasFileService` (cria e apaga arquivo temporário).

### Aviso de Segurança
> **⛔ Em produção (APP_DEBUG=false):** A rota retorna 403 automaticamente.
> A URL `/admin/juridico/debug/test-s3` não deve ser exposta publicamente mesmo em debug.
> Adicionar restrição de IP (ex: Nginx `allow 10.0.0.0/8;`) em configurações de servidor para proteção adicional.

---

## 4. DataJud e Whatsapp sem `Models/` próprios

### Situação
Os domínios **DataJud** e **Whatsapp** não possuem `Models/`. A auditoria acusou como potencial ausência.

### Decisão
A ausência é **intencional**:

| Domínio | Modelos utilizados |
|---------|-------------------|
| **DataJud** | Usa `Legal\Models\Processo` para enriquecer dados de processos. Não persiste dados próprios — é um domínio de consulta pura (read-only API wrapper). |
| **Whatsapp** | Templates são configurações (`core()->getConfigData()`), não entidades. Logs de mensagens, se necessários, iriam para tabela `saas_transactions`. Não há estado local a persistir. |

---

## 5. Namespace `PrazoCreated` — Histórico

### Situação
O import em `LawFirmServiceProvider.php` referenciava `SuiteZap\LawFirm\Events\PrazoCreated` (namespace da raiz) mas o arquivo fisicamente reside em `Legal/Events/PrazoCreated.php`.

### Causa raiz
Durante a "Great Migration" v3.11, `PrazoCreated` foi movido para o domínio Legal mas o import no `LawFirmServiceProvider` não foi atualizado. O PHP não gerou erro fatal porque o autoload não era invocado na inicialização — apenas em tempo de despacho do evento.

### Correção (v3.49)
Import corrigido para `SuiteZap\LawFirm\Legal\Events\PrazoCreated`.

---

## 6. Resumo de Isenções Documentadas

| Item | Isenção | Motivo |
|------|---------|--------|
| Financial sem Repository | ✅ Aceita | 1 model simples, FinancialService encapsula |
| GED sem Repository | ✅ Aceita | DocumentService == Repository implícito |
| AI sem Repository | ✅ Aceita | Dados do MotherShip, não local |
| Escavador sem Repository | ✅ Aceita | EscavadorService encapsula |
| DataJud sem Models | ✅ Aceita | Consulta pura (sem persistência local) |
| Whatsapp sem Models | ✅ Aceita | Templates = configurações Krayin core |
| CalculateStorageUsage Storage:: | 🔴 **NÃO aceita** | Corrigido em v3.49 |
| Debug route Storage:: inline | 🔴 **NÃO aceita** | Corrigido em v3.49 |

---

*Gerado em 2026-05-15 — Ciclo de auditoria v3.49.*
