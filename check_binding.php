<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🔍 VERIFICANDO BINDING DO DATAGRID:\n";

$target = \Webkul\Admin\DataGrids\Activity\ActivityDataGrid::class;
$resolved = app($target);

echo "Target Class: {$target}\n";
echo "Resolved Instance: " . get_class($resolved) . "\n";

if ($resolved instanceof \SuiteZap\LawFirm\DataGrids\SafeActivityDataGrid) {
    echo "✅ BINDING FUNCIONANDO! O Sistema está usando o SafeActivityDataGrid.\n";
} else {
    echo "❌ BINDING FALHOU! O Sistema ainda usa o original.\n";
    echo "Verifique a ordem dos ServiceProviders em config/app.php ou se o LawFirmServiceProvider está registrado.\n";
}
