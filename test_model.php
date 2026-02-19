<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing Processo Model...\n";
    $p = \SuiteZap\LawFirm\Legal\Models\Processo::with('financeiros')->find(5);

    if ($p) {
        echo "Found Processo 5: " . $p->titulo . "\n";
        echo "Financeiros Count: " . $p->financeiros->count() . "\n";

        // Test Relationship
        foreach ($p->financeiros as $fin) {
            echo " - Financial: " . $fin->id . " (" . $fin->valor . ")\n";
        }
    } else {
        echo "Processo 5 not found.\n";
    }

    echo "Model test passed.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
