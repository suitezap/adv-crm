# Modificações no Core do Krayin (Webkul)

> ATENÇÃO: Estes arquivos foram alterados diretamente no core ou sobrescritos. Cuidado ao atualizar o Krayin.

1. **Configuração de UI (Bug Fix Vue.js):**
   - Arquivo: `packages/Webkul/Admin/src/Resources/views/configuration/field-type.blade.php`
   - Motivo: Correção de "Array to String conversion" e remoção de dependência conflitante de Vue.js.

2. **Middleware/Providers:**
   - Verifique `AppServiceProvider.php`: Força HTTPS em produção (`URL::forceScheme('https')`).

3. **Docker/Infra:**
   - Entrypoint customizado para permissões de storage.
