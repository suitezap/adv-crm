# Checklist de Testes de Regressão - LawFirm v1.4

**Data de Criação:** 2026-01-20  
**Versão:** v1.4  
**Baseado em:** `AUDIT_REPORT.md`

> **Objetivo:** Identificar se bugs corrigidos anteriormente voltaram e validar funcionalidades críticas.

---

## 🎯 Como Usar Este Checklist

1. **Marque com `[x]`** cada teste que passar com sucesso
2. **Marque com `[!]`** cada teste que falhar
3. **Anote observações** na seção "Notas" de cada área
4. **Prioridade:** Comece pelos testes de **Prioridade ALTA** (🔴)

---

## 🔴 PRIORIDADE ALTA - Testes Críticos

### 1. Config UI (Krayin Core Override)

**Arquivo Testado:** `packages/Webkul/Admin/src/Resources/views/configuration/field-type.blade.php`

**Bug Conhecido:** "Array to String conversion" ao salvar configurações

#### Passos de Teste:

- [ ] **1.1** Acessar: `Admin > Settings > General > Settings`
- [ ] **1.2** Clicar na aba "LawFirm" (ou qualquer aba de configuração)
- [ ] **1.3** Modificar QUALQUER campo (ex: Nome da Empresa, Logo, etc.)
- [ ] **1.4** Clicar em "Save Configuration"
- [ ] **1.5** Verificar se a mensagem "Configuration saved successfully" aparece
- [ ] **1.6** Verificar se NÃO há erro "Array to String conversion" nos logs
- [ ] **1.7** Atualizar a página e confirmar que o valor foi salvo

**Resultado Esperado:**
✅ Configurações salvas sem erros  
✅ Nenhum erro no console do navegador  
✅ Nenhum erro no `storage/logs/laravel.log`

**Notas:**
```
Data do Teste: ___________
Testado por: ______________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 2. Translation Keys (Chaves de Tradução)

**Arquivo Testado:** `packages/SuiteZap/LawFirm/src/Resources/lang/pt_BR/app.php`

**Bug Conhecido:** Exibição de chave bruta (`lawfirm::app.deadlines.status`) ao invés do texto traduzido

#### Passos de Teste:

- [ ] **2.1** Acessar: `Admin > Jurídico > Prazos`
- [ ] **2.2** Verificar o cabeçalho da coluna "Status Real"
  - ❌ **FALHA** se aparecer: `lawfirm::app.deadlines.status`
  - ✅ **SUCESSO** se aparecer: "Status Real" ou "Status Temporal"
- [ ] **2.3** Verificar badges de status nos prazos (Atrasado, Urgente, No Prazo)
  - Devem estar em português, não como chaves
- [ ] **2.4** Acessar: `Admin > Jurídico > Processos > [Qualquer Processo]`
- [ ] **2.5** Verificar se todos os labels estão traduzidos (não aparecem como `lawfirm::app.*`)

**Validação de Código:**
```bash
# Execute este comando para verificar a estrutura do arquivo de tradução
grep -A 5 "'deadlines'" packages/SuiteZap/LawFirm/src/Resources/lang/pt_BR/app.php
```

**Resultado Esperado:**
```php
'deadlines' => [
    'status' => 'Status Real',
    // ... outras traduções
],
```

**Notas:**
```
Data do Teste: ___________
Testado por: ______________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 3. File Upload Naming Convention

**Arquivo Testado:** `packages/SuiteZap/LawFirm/src/Http/Controllers/Admin/ProcessoController.php`

**Regra Crítica:** `{ProcessID}-{Random7}_{SlugCleanName}.{ext}`

**Bug Conhecido:** Reverter para nome original do arquivo pode quebrar segurança

#### Passos de Teste:

- [ ] **3.1** Acessar: `Admin > Jurídico > Processos > [Criar Novo Processo]`
- [ ] **3.2** Preencher o formulário básico
- [ ] **3.3** Na aba "Anexos", fazer upload de um arquivo com nome complexo:
  - **Nome de teste:** `Minha Procuração (Versão Final) - João da Silva.pdf`
- [ ] **3.4** Salvar o processo
- [ ] **3.5** Acessar o diretório de uploads via SSH/FTP ou comando:
  
```bash
# Windows (PowerShell)
Get-ChildItem -Path "storage\app\public\processos" -Recurse -Filter "*.pdf"

# Linux/Mac
find storage/app/public/processos -name "*.pdf"
```

- [ ] **3.6** Verificar que o arquivo foi salvo com o padrão correto

**Resultado Esperado:**
```
✅ Formato correto: 123-aB7cD9e_minha-procuracao-versao-final-joao-da-silva.pdf
❌ Formato errado:  Minha Procuração (Versão Final) - João da Silva.pdf
```

**Validação de Código:**
```bash
# Verificar se a lógica de nomenclatura está presente no controller
grep -A 10 "Str::random(7)" packages/SuiteZap/LawFirm/src/Http/Controllers/Admin/ProcessoController.php
```

**Notas:**
```
Data do Teste: ___________
Nome do arquivo gerado: _______________________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 4. PDF Generation (Procuração, Contrato, Recibo)

**Arquivos Testados:** 
- `LegalDocumentController.php`
- `FinancialController.php`
- Views: `documents/pdf/*.blade.php`

**Bug Conhecido:** Caminhos absolutos para logo quebram em S3/Cloud Storage

#### 4.A) Teste de Procuração

- [ ] **4.A.1** Acessar: `Admin > Jurídico > Processos > [Processo Existente]`
- [ ] **4.A.2** Clicar em "Imprimir Procuração" (ou rota: `/admin/lawfirm/documents/procuration/{id}`)
- [ ] **4.A.3** Verificar que o PDF é gerado sem erros
- [ ] **4.A.4** Verificar que o **logo da empresa** aparece no PDF
- [ ] **4.A.5** Verificar que **caracteres especiais** (ã, é, ç) são renderizados corretamente
- [ ] **4.A.6** Verificar que o **rodapé** contém cidade, telefone e endereço

**Resultado Esperado:**
✅ PDF gerado com logo em base64  
✅ Encoding UTF-8 correto  
✅ Sem erro "file not found" para imagem do logo

#### 4.B) Teste de Contrato

- [ ] **4.B.1** Acessar: `Admin > Jurídico > Processos > [Processo Existente]`
- [ ] **4.B.2** Clicar em "Imprimir Contrato" (rota: `/admin/lawfirm/documents/contract/{id}`)
- [ ] **4.B.3** Verificar geração do PDF
- [ ] **4.B.4** Verificar logo e caracteres especiais

#### 4.C) Teste de Recibo Financeiro

- [ ] **4.C.1** Acessar: `Admin > Jurídico > Financeiro`
- [ ] **4.C.2** Filtrar transações com status "Pago"
- [ ] **4.C.3** Clicar no botão de download de recibo (ícone PDF)
- [ ] **4.C.4** Verificar geração do PDF
- [ ] **4.C.5** Verificar que os valores financeiros estão formatados corretamente (R$ X.XXX,XX)

**Validação de Código (Logo em Base64):**
```bash
# Verificar se o logo está sendo convertido para base64
grep -n "base64_encode" packages/SuiteZap/LawFirm/src/Http/Controllers/LegalDocumentController.php
grep -n "base64_encode" packages/SuiteZap/LawFirm/src/Http/Controllers/FinancialController.php
```

**Notas:**
```
Data do Teste: ___________
Testado por: ______________
Status Procuração: [ ] PASSOU  [ ] FALHOU
Status Contrato:   [ ] PASSOU  [ ] FALHOU
Status Recibo:     [ ] PASSOU  [ ] FALHOU
Observações:


```

---

## 🟡 PRIORIDADE MÉDIA - Testes Importantes

### 5. CPF/CNPJ Backend Validation

**Arquivo Testado:** `packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php`

**Funcionalidade:** Validação server-side de CPF/CNPJ antes de salvar

#### Passos de Teste:

- [ ] **5.1** Acessar: `Admin > Contacts > Persons > Create`
- [ ] **5.2** Preencher o formulário com um **CPF INVÁLIDO**: `111.111.111-11`
- [ ] **5.3** Tentar salvar
- [ ] **5.4** Verificar que aparece erro de validação: "CPF inválido"
- [ ] **5.5** Corrigir para um **CPF VÁLIDO**: `123.456.789-09`
- [ ] **5.6** Verificar que salva com sucesso

#### Teste de Organização (CNPJ):

- [ ] **5.7** Acessar: `Admin > Contacts > Organizations > Create`
- [ ] **5.8** Preencher com **CNPJ INVÁLIDO**: `11.111.111/1111-11`
- [ ] **5.9** Verificar erro de validação
- [ ] **5.10** Corrigir para **CNPJ VÁLIDO** e salvar

**Validação de Código (Listeners):**
```bash
# Verificar se os listeners estão registrados
grep -A 5 "person.create.before" packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php
grep -A 5 "organization.create.before" packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php
```

**Notas:**
```
Data do Teste: ___________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 6. HTTPS Force Scheme (Production)

**Arquivo Testado:** `app/Providers/AppServiceProvider.php`

**Funcionalidade:** Forçar HTTPS em produção para evitar loops do Traefik

#### Passos de Teste (Ambiente de Produção):

- [ ] **6.1** Verificar que a variável `APP_URL` contém `https://` no `.env`
- [ ] **6.2** Acessar a aplicação via HTTP: `http://seudominio.com`
- [ ] **6.3** Verificar se é **automaticamente redirecionado** para HTTPS
- [ ] **6.4** Verificar que NÃO há loop infinito de redirecionamento
- [ ] **6.5** Inspecionar os headers da requisição (F12 > Network)
- [ ] **6.6** Confirmar que `X-Forwarded-Proto: https` está presente

**Validação de Código:**
```bash
# Verificar se a lógica de force HTTPS está ativa
grep -A 3 "forceScheme" app/Providers/AppServiceProvider.php
```

**Resultado Esperado:**
```php
if (str_contains(config('app.url'), 'https')) {
    URL::forceScheme('https');
    $this->app['request']->server->set('HTTPS', 'on');
}
```

**Notas:**
```
Data do Teste: ___________
Ambiente: [ ] Local  [ ] Staging  [ ] Production
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 7. DataGrid de Prazos (Urgency Dashboard)

**Arquivo Testado:** `packages/SuiteZap/LawFirm/src/DataGrids/PrazoDataGrid.php`

**Funcionalidade:** Semáforo de urgência com cores (Vermelho, Amarelo, Verde, Cinza)

#### Passos de Teste:

- [ ] **7.1** Acessar: `Admin > Jurídico > Prazos`
- [ ] **7.2** Verificar que existem prazos com diferentes status temporais
- [ ] **7.3** Verificar a coluna "Status Temporal" (Urgência):
  
  **Badges esperados:**
  - 🔴 **Vermelho** = "Atrasado" ou "Vence Hoje"
  - 🟡 **Amarelo** = "Urgente" (próximos 3 dias)
  - 🟢 **Verde/Azul** = "No Prazo"
  - ⚪ **Cinza** = "Concluído"

- [ ] **7.4** Verificar que a coluna "Processo Ref." é **clicável** e leva ao processo
- [ ] **7.5** Verificar que a ordenação prioriza prazos urgentes no topo
- [ ] **7.6** Verificar que "Concluídos" aparecem no final da lista

**Validação Visual:**
```
✅ Cores pastéis corretas (não muito vibrantes)
✅ Badges legíveis com bom contraste
✅ Links funcionando corretamente
```

**Notas:**
```
Data do Teste: ___________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

### 8. Financial Dashboard (KPIs)

**Arquivo Testado:** `packages/SuiteZap/LawFirm/src/Resources/views/financial/index.blade.php`

**Funcionalidade:** Dashboard com KPIs financeiros

#### Passos de Teste:

- [ ] **8.1** Acessar: `Admin > Jurídico > Financeiro`
- [ ] **8.2** Verificar que os **4 KPIs** são exibidos:
  - Total Faturado
  - Total Pendente
  - Total Recebido
  - Total em Atraso
- [ ] **8.3** Verificar que os valores estão formatados em R$
- [ ] **8.4** Verificar que o filtro de datas funciona
- [ ] **8.5** Clicar em "Filtrar" e confirmar que os KPIs são recalculados
- [ ] **8.6** Verificar que o layout **não quebra** (sem FOUC - Flash of Unstyled Content)

**Notas:**
```
Data do Teste: ___________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

## 🟢 PRIORIDADE BAIXA - Testes de Funcionalidade

### 9. Rotas Admin (Smoke Test)

#### Processos:

- [ ] **9.1** `GET /admin/juridico/processos` → Lista de processos
- [ ] **9.2** `GET /admin/juridico/processos/create` → Formulário de criação
- [ ] **9.3** `POST /admin/juridico/processos/create` → Criar processo (testar submit)
- [ ] **9.4** `GET /admin/juridico/processos/{id}` → Visualizar processo
- [ ] **9.5** `GET /admin/juridico/processos/{id}/edit` → Editar processo
- [ ] **9.6** `PUT /admin/juridico/processos/{id}` → Atualizar processo

#### Prazos:

- [ ] **9.7** `GET /admin/juridico/prazos` → Lista de prazos
- [ ] **9.8** `POST /admin/juridico/prazos/store` → Criar prazo
- [ ] **9.9** `GET /admin/juridico/prazos/{id}/edit` → Editar prazo
- [ ] **9.10** `PUT /admin/juridico/prazos/{id}/concluir` → Marcar como concluído

**Notas:**
```
Data do Teste: ___________
Status: [ ] PASSOU  [ ] FALHOU
Rotas com erro:


```

---

### 10. API RESTful (Sanctum Auth)

**Pré-requisito:** Gerar token Sanctum válido

#### Passos de Teste:

- [ ] **10.1** Gerar token de API: `php artisan tinker` → `User::first()->createToken('test')->plainTextToken`
- [ ] **10.2** Testar endpoint: `GET /api/lawfirm/processes`
  
```bash
# Teste com cURL
curl -H "Authorization: Bearer {SEU_TOKEN}" http://localhost/api/lawfirm/processes
```

- [ ] **10.3** Verificar resposta JSON com lista de processos
- [ ] **10.4** Testar endpoint: `GET /api/lawfirm/deadlines`
- [ ] **10.5** Verificar autenticação: chamar API **sem token** deve retornar `401 Unauthorized`

**Notas:**
```
Data do Teste: ___________
Status: [ ] PASSOU  [ ] FALHOU
Observações:


```

---

## 📊 Resumo de Testes

### Resultado Final

| Prioridade | Total | Passou | Falhou | Pendente |
|------------|-------|--------|--------|----------|
| 🔴 Alta    | 4     | ___    | ___    | ___      |
| 🟡 Média   | 4     | ___    | ___    | ___      |
| 🟢 Baixa   | 2     | ___    | ___    | ___      |
| **TOTAL**  | **10**| ___    | ___    | ___      |

### Bugs Identificados

```
1. _______________________________________________
   Prioridade: ___  Status: ___  Arquivo: ___

2. _______________________________________________
   Prioridade: ___  Status: ___  Arquivo: ___

3. _______________________________________________
   Prioridade: ___  Status: ___  Arquivo: ___
```

---

## 🔧 Troubleshooting Guide

### Config UI Não Salva ("Array to String")

**Solução:**
1. Comparar `packages/Webkul/Admin/src/Resources/views/configuration/field-type.blade.php` com versão original do Krayin
2. Verificar se `json_encode($child)` está presente na linha 6
3. Limpar cache: `php artisan config:clear && php artisan view:clear`

### Translation Keys Aparecem Crus

**Solução:**
1. Verificar estrutura do array em `pt_BR/app.php`
2. Executar: `php artisan config:clear`
3. Forçar locale: `config(['app.locale' => 'pt_BR'])`

### PDFs Sem Logo

**Solução:**
1. Verificar se logo está em base64: `grep "base64_encode" LegalDocumentController.php`
2. Testar caminho do logo: `Storage::exists($logoPath)`
3. Verificar permissões: `chmod -R 755 storage/app/public`

### HTTPS Loop Infinito

**Solução:**
1. Verificar `APP_URL` no `.env` (deve ser `https://`)
2. Configurar Traefik para enviar header `X-Forwarded-Proto`
3. Confirmar lógica em `AppServiceProvider.php`

---

**Fim do Checklist** - Use este documento em cada release para garantir estabilidade! 🚀
