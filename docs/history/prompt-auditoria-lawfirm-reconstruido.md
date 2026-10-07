**Use @laravel-expert, @laravel-security-audit, @architecture-decision-records, 
@architect-review, @domain-driven-design, @clean-code, @code-refactoring-tech-debt,
@database-optimizer, @frontend-mobile-security-xss-scan para atuar como um 
Software Sênior especialista em Laravel, DDD e SaaS Multi-tenant:**

**Contexto:** O CRM Jurídico (LawFirm/SuiteZap) adota **Domain-Driven Design (DDD)** 
desde a "Great Migration" v3.11, atualmente em **v3.53.1** (Krayin v2.1.6). A estrutura 
separa responsabilidades em **10 domínios**: **Legal, Financial, GED, SaaS, AI, Escavador, 
DataJud, Whatsapp, TenantFinance, Atendimento**.

Desde a v3.36, o diretório `src/Http/Controllers/` deve estar **completamente vazio** 
(Zero Root Controllers): 0 arquivos PHP **e** 0 subdiretórios. Qualquer subdiretório 
vestigial (ex: `Admin/`, `Api/`) é uma violação semântica desta regra.
Preciso validar se essa arquitetura está íntegra e se não surgiram novos "bolsões" 
de código legado.

**Fontes de Verdade (obrigatório ler antes de auditar):**
- [.agent/skills/krayin_lawfirm_dev/SKILL.md](file:///c:/laragon/www/adv-crm/.agent/skills/krayin_lawfirm_dev/SKILL.md) — 
  Regras formais do pacote DDD (o "como escrever" — convenções e regras de ouro)
- [ARCHITECTURE.md](file:///c:/laragon/www/adv-crm/ARCHITECTURE.md) — ADRs, regras de ouro, 
  diagrama de domínios (v3.53.1 — inclui ADR §4.83 e fechamento de riscos residuais)
- [ARCHITECTURE_dir.md](file:///c:/laragon/www/adv-crm/ARCHITECTURE_dir.md) — Mapa de 
  diretórios e tabela de rotas (v3.53.0)
- [ARCHITECTURE_mothership_orient.md](file:///c:/laragon/www/adv-crm/ARCHITECTURE_mothership_orient.md) — 
  Schema e orientações do banco central (Mothership)

**Ordem de precedência em caso de conflito entre documentos:** SKILL.md > 
ARCHITECTURE.md > ARCHITECTURE_dir.md > ARCHITECTURE_mothership_orient.md. 
Se encontrar conflito entre eles durante a auditoria, reporte como um achado 
de dívida técnica na Seção 4 — não escolha um lado silenciosamente.

**Tarefa:** Auditoria de conformidade arquitetural e qualidade de código no pacote 
`packages/SuiteZap/LawFirm/`.

> [!IMPORTANT]
> **Este é um prompt de SOMENTE DIAGNÓSTICO.** Não aplique nenhuma correção,
> refatoração ou remoção de código nesta tarefa, mesmo que a correção pareça
> trivial. Todos os achados devem ir para o Checklist de Ação (item 4) e
> aguardar um prompt de execução separado, um item por vez, seguindo o fluxo
> de plano→aprovação já estabelecido no projeto.

**Instruções Específicas:**

1. **Validação de Fronteiras (Bounded Contexts):**    
   - Mapeie o diretório `packages/SuiteZap/LawFirm/src/`.
   - Confirme que **todos** os Controllers estão dentro de `src/{Domínio}/Http/Controllers`.
   - **Alerta Vermelho:** Identifique qualquer arquivo PHP em `src/Http/Controllers/`, 
     `src/Models/`, `src/Services/`, `src/Listeners/` ou `src/Observers/` 
     (resíduos de migração).
   - **Alerta Vermelho:** Identifique qualquer **subdiretório** em `src/Http/Controllers/` 
     (ex: `Admin/`, `Api/`) — violação semântica do Zero Root Controllers mesmo quando vazio.
   - Verifique se cada domínio possui a estrutura mínima:  
     `Models/ + Http/Controllers/ + Services/`  
     Justificativas documentadas para ausência de `Models/`:
     - **DataJud**: integração stateless com API pública do CNJ (sem persistência própria)
     - **Atendimento**: integração stateless com Chatwoot via MotherShipService (sem persistência própria)
   - Verifique que o domínio **TenantFinance** (v3.40) está isolado do **SaaS** 
     (SKILL.md §6: "TenantFinance vs SaaS Asaas — Critical Distinction"):
     - `Financial/*` e `TenantFinance/*` devem usar `TenantAsaasService` (escritório→cliente)
     - `SaaS/*` deve usar `AsaasService` (plataforma→tenant)
     - Nenhum cross-use permitido.
   - Verifique que o domínio **Atendimento** (v3.52.5) segue o padrão Chatwoot:
     - Config injetada exclusivamente via `MotherShipService::getChatwootConfig()` (Zero-.env)
     - Webhook `POST /api/webhooks/chatwoot` com validação HMAC-SHA1 via `X-Chatwoot-Signature`
     - Retorno HTTP 200 imediato + dispatch para Jobs (sem processamento síncrono no controller)
   - **Isolamento Multi-Tenant Genérico:** para cada domínio (Legal, Financial,
     GED, Whatsapp, Atendimento), amostre os principais Services/Repositories
     e confirme que toda query a tabelas de tenant inclui escopo por
     `tenant_id` (via global scope, where explícito, ou equivalente). Marque
     como 🔴 qualquer query que acesse dados potencialmente sensíveis sem
     esse escopo, mesmo que pareça improvável de ser explorada.

2. **Verificação das Regras de Ouro (Compliance):**    
   - **Storage (Regra 2.2):** Busque `Storage::put`, `Storage::get`, `Storage::download`, 
     `Storage::disk`, `Storage::exists`, `Storage::mimeType`, `Storage::makeDirectory`, 
     `Storage::temporaryUrl`, `file_put_contents` fora do `SaasFileService`.
     ⚠️ Comentários de docblock que mencionam `Storage::` mas não representam chamadas 
     ativas **não** são violações — verificar se a linha é código executável.
   - **Zero .env (Regra 3.x):** Busque chamadas `env('EVOLUTION_*')`, `env('N8N_*')`, 
     `env('ESCAVADOR_*')`, `env('CHATWOOT_*')` sem guard `MotherShipService` como 
     fonte primária. Aceitar `env()` SOMENTE como fallback dev/local (Regra 3.4).
   - **Skinny Controllers (Regra 2.3):** Analise o `ProcessoController` (Legal) — 
     linhas totais, delegações a Services, presença de lógica de negócio inline.
   - **Debug Logs (Regra 2.4):** Busque `Log::debug` **ativos** (não comentados) em 
     Controllers e Services — proibido em código de produção.
   - **Graceful Degradation (Regra 4):**
     - Controllers: retornam HTTP 503 (não 500 ou exception não tratada) quando 
       serviços externos não estão configurados.
     - **Jobs/Listeners:** devem usar `Log::error() + history->update(['status' => 'failed']) + return`.
       **Proibido usar `throw` em Jobs** exceto como safety-net no bloco `catch` externo.
       Verificar especialmente `AI/Jobs/ProcessAiAssistant.php`.
   - **WhatsApp Templates (Regra 6):** Verifique se os templates de mensagem 
     estão sob o grupo `lawfirm.whatsapp_templates.messages` (não grupos separados).
   - **TenantFinance Module Gate:** Verifique se o módulo requer `TENANT_FINANCE` 
     em `active_modules` e retorna 403 quando ausente.
   - **SuiteCoins (Ƶ) Rule — CONFLITO DE FONTES A RESOLVER PRIMEIRO:** 
     ARCHITECTURE.md (Regra 4.69) descreve fórmula fixa (`BRL × 10 × 1.25`, 
     exceção `× 10`); SKILL.md (§5) descreve multiplicador dinâmico via 
     `suitecoin_multiplier` em `app_config`, com MotherShip guardando saldo 
     em BRL 1:1. Antes de validar conformidade, confirme no código-fonte 
     atual (não nos documentos) qual das duas descrições reflete a 
     implementação real, e reporte a divergência entre os documentos como 
     achado 🟡 — desatualizar um dos dois é a ação corretiva, não escolher 
     qual "parece mais certo".
   - **Isolamento de Filas Redis (via SKILL.md §6):** Em `docker-compose.yml`, 
     confirme que `REDIS_PREFIX: {tenant_id}_` está declarado para toda 
     configuração de fila em ambientes Swarm com Redis compartilhado. 
     Ausência disso é 🔴 crítico — tenants podem consumir Jobs de fila uns 
     dos outros.
   - **Markdown AI formatting (v3.50.0):** Confirme se as respostas das IAs são 
     renderizadas client-side via `marked.js` + `DOMPurify` (XSS safe).
   - **Document Templates (v3.52.0):** Valide se migrations de `law_document_templates` 
     usam `unsignedInteger` (não `unsignedBigInteger`) para FKs do Krayin Core (`user_id`) 
     — evita MySQL Constraint Error 3780.
   - **Global Templates 403 Guard (v3.53.0):** Verifique se `DocumentTemplateController` 
     bloqueia com HTTP 403 qualquer tentativa de `edit()`, `update()` ou `destroy()` em 
     templates com `is_global = true` (templates do Mothership são somente leitura para tenants).
   - **Docblocks limpos:** Verifique se docblocks/comentários não mencionam APIs proibidas 
     (ex: `Storage::disk()`, `env('...')`) de forma que possa confundir novos desenvolvedores 
     sobre o código atual — apenas afirmações positivas do que o código **faz**, não do que 
     foi removido.

3. **Visualização (Mermaid Class Diagram):**    
   - Represente os **10 domínios**: Legal, Financial, GED, SaaS, AI, Escavador, 
     DataJud, Whatsapp, TenantFinance, Atendimento.
   - Cores:        
     - 🟢 Verde escuro: Domínios Core Sólidos (Model + Controller + Service completos)
     - 🟡 Amarelo: Domínios stateless sem Models (DataJud, Atendimento) — justificado
     - 🔴 Vermelho: Código Legado fora de domínio (ex: arquivo PHP em `src/Models/`)
   - Inclua setas de dependência entre domínios:
     - Legal → GED (DocumentService — upload/download)
     - Legal → Financial (FinancialService — honorários)
     - Legal → AI (LegalOrchestrator — LeadTriagem)
     - Legal → Whatsapp (ProcessoWhatsappService — alertas prazos)
     - Legal → Escavador (EscavadorService — monitoramento CNJ)
     - Legal → DataJud (DataJudService — consulta pública)
     - AI → SaaS (MotherShipService — templates + saldo Ƶ)
     - Escavador → SaaS (MotherShipService — config + preços)
     - Financial → TenantFinance (TenantAsaasService — cobranças cliente)
     - GED → SaaS (SaasFileService — S3/MinIO)
     - Atendimento → SaaS (MotherShipService — Chatwoot config)
     - SaaS → TenantFinance: **ISOLAMENTO CRÍTICO** (Asaas separados)
   - Diferencie SaaS (Plataforma→Tenant) de TenantFinance (Tenant→Cliente).

4. **Relatório de Dívida Técnica Residual:**    
   - Liste classes que não se encaixam na estrutura DDD e sugira o domínio destino.
   - Calcule um **Score de Conformidade** (0-10) com breakdown por critério.
   - Gere um **Checklist de Ação** priorizando: 🔴 > 🟡 > 🟢.
   - Para cada achado 🔴 classificado como bug ativo (não apenas dívida
     técnica estrutural), sinalize explicitamente "candidato a
     GUARDRAILS.md" — isso indica que, quando corrigido, a correção deve
     seguir o fluxo obrigatório completo (plano → execução → validação →
     registro em GUARDRAILS.md → commit), não uma correção direta.

5. **Checagem de Regressão de Incidentes Conhecidos:**
   - Releia os incidentes documentados em ARCHITECTURE.md (ex: separação
     `account_id`/`inbox_id` do Chatwoot em §4.85) e no histórico de bugs já
     corrigidos.
   - Para cada um, confirme que a causa raiz continua corrigida no código
     atual — não assuma que "já foi corrigido uma vez" significa que
     está corrigido agora.
   - Se encontrar sinais de regressão, marque como 🔴 crítico e reporte
     separadamente, com prioridade máxima no Checklist de Ação.

6. **Sincronismo de Versão:**
   - Confirme que a versão declarada no cabeçalho de ARCHITECTURE.md
     coincide exatamente com a constante `VERSION` em
     `LawFirmServiceProvider.php`.
   - Se divergirem, reporte como achado 🟡 (não crítico, mas indica
     processo de release não seguido à risca).

7. **Riscos de Upgrade (Krayin Core):**
   - Valide se as atuais configurações correm risco de quebrar com atualizações do 
     Krayin (https://github.com/krayin/laravel-crm.git).
   - Avalie o impacto de:
     - Vue.js SPA interceptors e CSRF manual em `onclick` events (padrão: ler 
       `X-CSRF-TOKEN` no momento do evento, nunca no `script init`)
     - REPLACE_ID Pattern em rotas JS (`route()` com parâmetro vazio substituído via JS)
     - Flatpickr injection via `setTimeout` ou hook `load`
     - Portal Dialog Pattern (`window.open + ?clean=true`)
     - DataGrid `method` (obrigatório `method=GET` sem `confirm_text` para evitar 
       interceptação Vue)
     - **`vee-validate.js` modificado** (regex de telefone afrouxada) — risco de 
       sobrescrita em merge do Krayin upstream
     - **`window.Flatpickr` via `setTimeout`** — dependência de timing de boot Vue
     - **CSRF Header hardcoded** — risco se Krayin renomear o header (quebraria Kanban)
     - **Messenger Inbox (Whaticket):** submódulo suspenso em 29/05/2026 (já ocorreu). 
       Verificar se rotas ainda estão comentadas, autoload mantido para backward 
       compatibility de DB. Planejar remoção do package `SuiteZap/Whaticket` do 
       `composer.json` após confirmação de estabilidade — mas não remover nesta
       tarefa: apenas registrar como item do Checklist de Ação, aguardando
       aprovação explícita separada.

**Objetivo Final:** Confirmar que a arquitetura está sólida (target: score ≥ 9.5/10) 
e listar ações concretas para atingir dívida técnica zero. Lembre-se: esta tarefa 
entrega um relatório e um checklist — nenhuma correção é aplicada aqui.
