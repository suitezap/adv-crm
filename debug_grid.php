<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🔍 INICIANDO DIAGNÓSTICO DE ATIVIDADES...\n";

try {
    // 1. Teste de Count (Geralmente funciona pois é simples)
    $repo = app(\Webkul\Activity\Repositories\ActivityRepository::class);
    $count = $repo->count();
    echo "✅ Count Query OK. Total: {$count}\n";

    // 2. Teste de Fetch (Geralmente falha se houver erro de Join/Select)
    // Simulando o request do Grid
    echo "⏳ Tentando buscar os primeiros 5 registros...\n";
    $items = $repo->orderBy('id', 'desc')->paginate(5);

    echo "✅ Fetch Query OK.\n";
    echo "📊 Dados retornados: " . $items->count() . "\n";

    if ($items->count() > 0) {
        echo "📝 Exemplo de Item: " . json_encode($items->first()->toArray()) . "\n";
    } else {
        echo "⚠️ Nenhum item retornado na lista (mas o count era {$count}).\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO FATAL NO REPOSITÓRIO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    // Se for erro de SQL, mostre a query se possível
    if (method_exists($e, 'getSql')) {
        echo "SQL: " . $e->getSql() . "\n";
    }
}
echo "\n🏁 FIM DO DIAGNÓSTICO.\n";
