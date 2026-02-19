<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate auth
$user = \Webkul\User\Models\User::first();
if ($user) {
    auth()->guard('user')->login($user);
}

try {
    echo "=== Testing Financial View ===\n";
    $processo = \SuiteZap\LawFirm\Legal\Models\Processo::with([
        'person',
        'lead',
        'responsavel',
        'prazos',
        'anexos',
        'documents',
        'financeiros' => function ($query) {
            $query->orderByRaw("CASE WHEN status = 'pendente' THEN 1 ELSE 2 END")
                ->orderBy('data_vencimento', 'asc');
        }
    ])->findOrFail(5);

    $view = view('lawfirm::Financial.processos.tabs.financial', [
        'processo' => $processo,
        'startClosed' => true
    ]);
    $view->render();
    echo "Financial view: OK\n";
} catch (\Exception $e) {
    echo "Financial view ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "\n=== Testing GED View ===\n";
    $view2 = view('lawfirm::GED.processos.tabs.documents', [
        'processo' => $processo,
        'readOnly' => false
    ]);
    $view2->render();
    echo "GED view: OK\n";
} catch (\Exception $e) {
    echo "GED view ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "\n=== Testing Legal Prazos View ===\n";
    $view3 = view('lawfirm::Legal.processos.tabs.prazos', [
        'processo' => $processo,
    ]);
    $view3->render();
    echo "Prazos view: OK\n";
} catch (\Exception $e) {
    echo "Prazos view ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "\n=== Testing Legal Checklist View ===\n";
    $view4 = view('lawfirm::Legal.processos.tabs.checklist', [
        'processo' => $processo,
    ]);
    $view4->render();
    echo "Checklist view: OK\n";
} catch (\Exception $e) {
    echo "Checklist view ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "\n=== Testing Full Edit View ===\n";
    $persons = app(\Webkul\Contact\Repositories\PersonRepository::class)->all();
    $leads = app(\Webkul\Lead\Repositories\LeadRepository::class)->all();

    $fullView = view('lawfirm::admin.processos.edit', [
        'processo' => $processo,
        'persons' => $persons,
        'leads' => $leads
    ]);
    $fullView->render();
    echo "Full edit view: OK\n";
} catch (\Exception $e) {
    echo "Full edit view ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

    // Show previous exception if available
    $prev = $e->getPrevious();
    if ($prev) {
        echo "CAUSED BY: " . $prev->getMessage() . "\n";
        echo "File: " . $prev->getFile() . ":" . $prev->getLine() . "\n";
    }
}

echo "\nDone.\n";
