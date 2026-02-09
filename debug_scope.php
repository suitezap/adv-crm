<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🔍 DIAGNÓSTICO DE PERMISSÕES (SCOPE):\n";

try {
    // Autentica User 1
    auth()->loginUsingId(1);
    $user = auth()->user();
    echo "👤 Usuário: {$user->name} (ID: {$user->id})\n";
    echo "🔑 Role: " . ($user->role ? $user->role->name : 'N/A') . "\n";
    echo "👁️ View Permission: {$user->view_permission}\n";

    // Checa o Bouncer
    $ids = bouncer()->getAuthorizedUserIds();

    echo "\n🛡️ BOUNCER STATUS:\n";
    if (is_null($ids)) {
        echo "✅ getAuthorizedUserIds retornou NULL (Acesso Global - Vê tudo)\n";
    } else {
        echo "⚠️ getAuthorizedUserIds retornou ARRAY (Filtrando resultados):\n";
        echo "Contagem de IDs permitidos: " . count($ids) . "\n";
        echo "Amostra: " . implode(', ', array_slice($ids, 0, 10)) . "\n";

        // Verifica se o próprio usuário está na lista
        if (in_array($user->id, $ids)) {
            echo "✅ O próprio usuário está na lista.\n";
        } else {
            echo "❌ PERIGO: O próprio usuário NÃO está na lista (Ele não vê os próprios registros?)\n";
        }
    }

    // Compara Contagens
    echo "\n📊 COMPARAÇÃO DE QUERY:\n";

    // 1. Repository Count (O que o Calendário/Contador vê)
    $repoCount = app(\Webkul\Activity\Repositories\ActivityRepository::class)->count();
    echo "📅 Repository Count: {$repoCount}\n";

    // 2. DataGrid Count (O que a Grid vê com o filtro)
    $dataGrid = app(\SuiteZap\LawFirm\DataGrids\SafeActivityDataGrid::class);
    // Force prepare to apply scopes
    $reflection = new ReflectionClass($dataGrid);
    if ($reflection->hasMethod('prepare')) {
        $method = $reflection->getMethod('prepare');
        $method->setAccessible(true);
        $method->invoke($dataGrid);
    }

    // Extrai a query final
    $prop = $reflection->getProperty('queryBuilder');
    $prop->setAccessible(true);
    $qb = $prop->getValue($dataGrid);
    $gridCount = $qb->get()->count(); // Executa get() pq o DataGrid tem group by que engana o count simples

    echo "📋 DataGrid Count: {$gridCount}\n";

    if ($repoCount > 0 && $gridCount == 0) {
        echo "\n🚨 CULPADO ENCONTRADO: O filtro do Bouncer/DataGrid está escondendo todos os registros!\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage();
}
