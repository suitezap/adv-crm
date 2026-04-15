<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$domain = 'adv-crm.test';

// Assume domain extraction logic
$tenantId = 'localhost'; // the .env default

$tenantIdfromEnv = config('lawfirm.tenant_id', env('TENANT_ID'));
echo "Resolved Tenant ID: " . $tenantIdfromEnv . "\n";

$config = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getEvolutionConfig();
if (!$config) {
    echo "Evolution config is NULL.\n";
} else {
    echo "Config: " . print_r($config, true) . "\n";
    $service = new \SuiteZap\LawFirm\Whatsapp\Services\EvolutionService();
    $connect = $service->connectInstance($config['instance']);
    echo "Connect Result: " . print_r($connect, true) . "\n";
}
