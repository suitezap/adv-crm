---
name: safe-code-change
description: Padrões para modificação segura de código no LawFirm CRM (DDD, multi-tenant e isolation).
---

# 🛡️ Skill: Safe Code Change

## Quando Usar
Ao realizar alterações no código-fonte em `packages/SuiteZap/LawFirm/src/` ou na camada da aplicação Laravel.

## Diretrizes Mandatórias

1. **Isolamento Multi-Tenant:**
   - Toda query deve estar escopada por `tenant_id` ou mecanismo correspondente.
   - Filas Redis devem incluir prefixo `REDIS_PREFIX: ${TENANT_ID}_`.

2. **Arquivos e Storage:**
   - Nunca usar `Storage::put/makeDirectory/url` diretamente. Usar sempre `SaasFileService`.

3. **Conexões de Banco:**
   - Conexão global Mothership exige declaração explícita: `connection('mothership')`.

4. **Preservação de Módulos Suspensos:**
   - Não reativar código em `Whatsapp/` sem aprovação formal conforme `AGENTS.md`.
