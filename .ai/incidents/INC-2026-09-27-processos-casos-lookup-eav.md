# INC-2026-09-27 — Falhas em Processos/Casos: RouteNotFound 500, SyntaxError Blade e Dessincronização EAV

**Severidade:** Alta (indisponibilidade na edição de processos/casos e busca de pessoas) · **Status:** Resolvido · **Owner:** Antigravity

---

## 1. Sintomas Observados

1. **HTTP 500 ao editar Processo ou Caso:**
   - URL: `/admin/juridico/processos/12/edit` e `/admin/juridico/casos/12/edit`.
   - Log: `Symfony\Component\Routing\Exception\RouteNotFoundException: Route [admin.casos.search_processo] not defined`.
2. **SyntaxError no Console do Navegador:**
   - `edit:1779 Uncaught SyntaxError: Unexpected token '<' (at edit:1779:5)`.
   - Quebrava a execução de todos os scripts JavaScript de frontend (dropdowns, selectors, modais).
3. **Divergência de Dados no Autocomplete de Pessoas:**
   - Ao buscar por "Maria" no campo de seleção de pessoa, a lista retornava "Nova Pessoa Teste" em vez de "Maria da Silva Bastos Veiria" (ID 12).
   - No banco de dados MySQL (`persons`), a pessoa ID 12 existia e possuía `name = 'Maria da Silva Bastos Veiria'`.

---

## 2. Causa Raiz

### A. Rota Inexistente (`RouteNotFoundException`)
As views Blade (`casos/edit.blade.php`, `casos/create.blade.php`, `processos/edit.blade.php`, `processos/create.blade.php`) invocavam `route('admin.casos.search_processo')`. No entanto, o pacote LawFirm define o prefixo e grupo de nomes como `admin.lawfirm.casos.search_processo` em `packages/Webkul/LawFirm/src/Routes/web.php`. A rota sem o namespace `.lawfirm.` não existia, abortando a compilação do Blade no servidor com HTTP 500.

### B. Tag de Script Aberta no Blade (`SyntaxError`)
Em `casos/edit.blade.php`, o bloco de script empurrado via `@push('scripts')` não fechava a tag `</script>` antes do `@endpush`. O Blade concatenou os elementos HTML seguintes da página diretamente dentro do script. O parser JavaScript encontrou tags HTML como `<div` e `<script` e disparou `Unexpected token '<'`.

### C. Sobrescrita de Atributos pelo EAV do Krayin CRM (`toArray()`)
O modelo `Person` utiliza a trait `Webkul\Attribute\Traits\CustomAttribute` (EAV). Quando o controller retornava `response()->json($results)`, o Laravel invocava `toArray()` / `jsonSerialize()`. A trait de EAV sobrepõe a coluna nativa `name` pelo valor registrado na tabela `person_attribute_values`. Como o registro EAV continha dados divergentes/legados ("Nova Pessoa Teste") enquanto a coluna física `persons.name` continha "Maria da Silva Bastos Veiria", o JSON enviado ao frontend continha o nome errado do EAV.

---

## 3. Correção Aplicada

1. **Correção de Rotas:** Atualizado o nome da rota no Blade para `admin.lawfirm.casos.search_processo`.
2. **Fechamento do Script:** Inserida a tag de fechamento `</script>` antes de `@endpush` em `casos/edit.blade.php`.
3. **Bypass da Sobrescrita EAV no Controller:**
   Em `ProcessoController::searchPerson()` e `searchOrganization()`, implementado mapeamento explícito que obtém o valor físico original da coluna via `$person->getRawOriginal('name') ?: $person->name`:
   ```php
   $data = collect($results->items())->map(function($person) {
       return [
           'id'   => $person->id,
           'name' => $person->getRawOriginal('name') ?: $person->name,
       ];
   });
   return response()->json(['data' => $data]);
   ```
4. **Resiliência do Frontend:** Atualizado o JavaScript dos templates Blade para tratar respostas tanto no formato `{ data: [...] }` quanto em array plano (`data.data || data`), com debounce de 300ms e `@keydown.enter.prevent` para evitar submissão acidental.

---

## 4. Arquivos Afetados

- `packages/SuiteZap/LawFirm/src/Legal/Http/Controllers/ProcessoController.php`
- `packages/SuiteZap/LawFirm/src/Resources/views/Legal/casos/edit.blade.php`
- `packages/SuiteZap/LawFirm/src/Resources/views/Legal/casos/create.blade.php`
- `packages/SuiteZap/LawFirm/src/Resources/views/admin/processos/edit.blade.php`
- `packages/SuiteZap/LawFirm/src/Resources/views/admin/processos/create.blade.php`
- `packages/Webkul/Admin/src/Resources/views/components/lookup/index.blade.php`
- `packages/Webkul/Admin/src/Resources/views/components/attributes/edit/lookup.blade.php`

---

## 5. Prevenção e Guardrails

- Adicionadas lições 12, 13 e 14 ao `.ai/LESSONS.md`.
- Registrado incidente no `GUARDRAILS.md`.
- Criada nota de referência no Obsidian (`D:\Z.Hermes\obsidian\LawFirm - Erros e Solucoes.md`).
