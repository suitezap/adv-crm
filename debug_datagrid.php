<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🔍 DIAGNÓSTICO REFLECTIVO: Extraindo QueryBuilder Real\n";

try {
    auth()->loginUsingId(1); // Autentica

    $dataGrid = app(\Webkul\Admin\DataGrids\Activity\ActivityDataGrid::class);

    // 1. Prepare
    $reflection = new ReflectionClass($dataGrid);
    if ($reflection->hasMethod('prepare')) {
        $method = $reflection->getMethod('prepare');
        $method->setAccessible(true);
        $method->invoke($dataGrid);
    }

    // 2. Extrair Query Builder
    if ($reflection->hasProperty('queryBuilder')) {
        $prop = $reflection->getProperty('queryBuilder');
        $prop->setAccessible(true);
        $qb = $prop->getValue($dataGrid);

        echo "✅ QueryBuilder extraído com sucesso.\n";
        echo "📜 SQL Gerado: " . $qb->toSql() . "\n";

        $results = $qb->get();
        echo "📊 Total Registros (Query Real): " . $results->count() . "\n";

        foreach ($results as $index => $row) {
            echo "[$index] ID: {$row->id} | Title: '{$row->lead_title}' (" . gettype($row->lead_title) . ") | LeadID: " . var_export($row->lead_id, true) . "\n";

            // Check original logic match
            if ($row->lead_title == null) {
                echo "   -> Ignored by Check (== null)\n";
            } else {
                echo "   -> PROCESSED by Closure. Testing Route...\n";
                try {
                    route('admin.leads.view', $row->lead_id);
                    echo "   -> Route OK\n";
                } catch (\Throwable $e) {
                    echo "   -> ❌ CRASH: " . $e->getMessage() . "\n";
                }
            }
        }

        // Vamos checar se existe alguma diferença nas colunas selecionadas
        // print_r($results->first());

    } else {
        echo "❌ Propriedade queryBuilder não encontrada.\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage();
}
