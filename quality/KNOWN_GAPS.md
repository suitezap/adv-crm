# 📋 Registro de Lacunas Conhecidas e Débitos Mapeados (KNOWN_GAPS.md)

Este documento registra explicitamente as lacunas de cobertura, comportamentos aceitos e débitos técnicos conhecidos da plataforma LawFirm CRM.

---

## 1. Débitos Financeiros e Transacionais de IA

### GAP-001: Ausência de Estorno Automático em Falha do Job de IA
* **Descrição**: No fluxo de disparo de Assistentes de IA (`AssistantController::execute`), o débito de SuiteCoins na assinatura Mothership é executado antes do dispatch assíncrono do Job `ProcessAiAssistant`. Se o Job falhar posteriormente (ex: queda do N8N ou erro HTTP da LLM), o status do histórico em `lawfirm_assistant_history` é atualizado para `failed`, mas **atualmente não ocorre o estorno automático** das moedas debitadas.
* **Impacto**: O cliente tem moedas deduzidas mesmo em requisições que não retornaram resultado útil por falha externa do webhook.
* **Decisão Atual**: Comportamento documentado e aceito temporariamente na versão v3.55.0. O teste `LEAD-AI-007` valida o débito prévio e a integridade da transação conforme a implementação existente, sem inventar estorno até aprovação arquitetural de feature de reembolso.

---

## 2. Lacunas de Cobertura de Domínios (Fase 1)

### GAP-002: Domínios Fora do Escopo Inicial da Fase 1
* **Descrição**: A Fase 1 da Infraestrutura de Qualidade foca estritamente nos domínios **Legal** (LegalOrchestrator), **AI** (Assistentes e Triagem), **SaaS** (Isolamento Multi-Tenant e SuiteCoins) e **Atendimento** (Chatwoot).
* **Domínios Pendentes para Fases Subsequentes**:
  - `Financial/` (Honorários, Custas e Faturas)
  - `GED/` (Upload S3, Anexos e Versionamento de Documentos)
  - `Escavador/` (Monitoramento processual e webhooks v1/v2)
  - `DataJud/` (Consulta Pública CNJ)
  - `TenantFinance/` (Cobranças Asaas do Escritório para Clientes)
  - `Whatsapp/` (Notificações transacionais Evolution API)
* **Ação Programada**: Implementação de testes dedicados em fases futuras conforme priorização de roadmap.

---

## 3. Ambientes e Testes

### GAP-003: Execução de Testes com LLM Real
* **Descrição**: Por padrão, todos os testes de IA rodam com mocks locais (`AI_REAL_TESTS=false`), garantindo custo R$ 0,00 e determinismo. Testes com LLM real exigem credenciais dedicadas em workflow manual isolado com orçamento controlado.
