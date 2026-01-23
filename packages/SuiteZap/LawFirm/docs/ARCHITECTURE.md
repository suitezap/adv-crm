# Arquitetura do Projeto LawFirm

## 1. Padrões de Código
- **Repository Pattern:** Toda lógica de banco deve passar pelos Repositories em `src/Repositories`.
- **Service Layer:** Integrações externas (ex: Evolution API) ficam em `src/Services`.
- **Uploads:** NUNCA usar nome original puro. Regra: `{ProcessID}-{Random7}_{SlugCleanName}.{ext}`.

## 2. Estrutura de Pastas
- `src/Resources/views/juridico`: Views do módulo.
- `src/Http/Controllers/Admin`: Controllers do Painel.

## 3. Configurações (Config)
- `menu.php`: Define itens do menu lateral
- `acl.php`: Define permissões disponíveis
- `system.php`: Configurações do painel admin

## 4. Segurança e ACL (Access Control List)

### A. Estrutura de Menus e Permissões
Mantenha a estrutura de chaves **Plana (Flat)** sempre que possível para evitar bugs de aninhamento no Krayin.

**✅ Preferir (Estrutura Plana):**
```php
'key' => 'lawfirm.processos'
'key' => 'lawfirm.prazos'
'key' => 'lawfirm.financial'
```

**❌ Evitar (Aninhamento Profundo):**
```php
'key' => 'lawfirm.juridico.modulo.processos'  // Causa problemas de visibilidade
```

### B. Middleware ACL nos Controllers
**IMPORTANTE:** Não adicione middleware ACL manualmente nos Controllers!

**❌ ERRADO:**
```php
public function __construct() {
    $this->middleware('acl:lawfirm.processos');  // Causa erro 500
}
```

**✅ CORRETO:**
```php
// Krayin gerencia ACL automaticamente via menu.php
public function __construct(DependenciesHere $dep) {
    $this->dependency = $dep;
}
```

O Krayin aplica ACL automaticamente baseado em:
- `menu.php`: Chave `'permission' => 'lawfirm.processos'`
- `acl.php`: Define permissões disponíveis

### C. Escopo de Dados (DataGrids)
Todo DataGrid **DEVE** implementar proteção de escopo no método `prepareQueryBuilder()`:

```php
public function prepareQueryBuilder()
{
    $queryBuilder = DB::table('sua_tabela')
        ->select('sua_tabela.id', ...);
    
    // Security / ACL Logic
    $user = auth()->guard('user')->user();
    
    // Admins (role_id = 1) veem tudo
    if ($user && $user->role_id != 1 && $user->view_permission !== 'global') {
        if ($user->view_permission == 'group') {
            // Grupo: flatMap para pegar IDs de todos usuários do grupo
            $userIds = $user->groups->flatMap(function ($group) {
                return $group->users->pluck('id');
            })->unique()->toArray();
            
            // SEMPRE qualificar tabela para evitar ambiguidade em JOINs
            $queryBuilder->whereIn('sua_tabela.user_id', $userIds);
        } else {
            // Individual: apenas registros do próprio usuário
            $queryBuilder->where('sua_tabela.user_id', $user->id);
        }
    }
    
    $this->setQueryBuilder($queryBuilder);
}
```

### D. Escopo de Dados (Services)
Services que retornam métricas ou agregações também devem aplicar escopo:

```php
private function getBaseQuery()
{
    $query = DB::table('law_financials')
        ->join('processos', 'law_financials.processo_id', '=', 'processos.id');
    
    $user = auth()->guard('user')->user();
    
    if ($user && $user->role_id != 1 && $user->view_permission !== 'global') {
        // Aplicar mesmo filtro de escopo
        // ...
    }
    
    return $query;
}
```

### E. Checklist de Segurança
Ao criar novos módulos/DataGrids:
- [ ] ACL configurado em `acl.php`
- [ ] Menu configurado em `menu.php` com chave de permissão
- [ ] **NÃO** adicionar middleware manual no Controller
- [ ] Escopo de usuário implementado em `prepareQueryBuilder()`
- [ ] Nomes de tabela **sempre qualificados** em queries com JOIN
- [ ] Usar `flatMap()` ao invés de `mapMany()`
- [ ] Verificar role com `role_id != 1` ao invés de `hasRole()`

## 5. Módulo SaaS (Tenant Control)
O controle de limites é híbrido:
- **Storage:** Calculado via `SaasStorageService` (incrementa no upload, decrementa no delete).
- **Bloqueio:** Middleware `SaasEnforcer` verifica data de validade e status a cada requisição.
- **Atualização:** Via Webhook protegido por `X-SAAS-TOKEN`.

## 6. Integração WhatsApp (Evolution API)
- **Conexão:** O Krayin atua como cliente. O QR Code é gerado no backend e exibido via Polling no frontend.
- **Service:** `EvolutionService` centraliza todas as chamadas HTTP.
