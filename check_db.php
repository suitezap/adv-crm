<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Tenant 8 from previous logs
    \SuiteZap\LawFirm\SaaS\Services\MotherShipService::setTenantId(8);
} catch (\Exception $e) {}

// But to bypass setTenantId which failed, let's just make direct Eloquent queries
// using the MotherShip models.
try {
    $tenant = \SuiteZap\LawFirm\SaaS\Models\Tenant::on('mothership')->find(8);
    if (!$tenant) {
        die("Tenant 8 not found\n");
    }

    echo "Tenant 8:\n";
    echo "Evolution Node ID: " . $tenant->evolution_node_id . "\n";
    echo "Evolution Instance Name: " . $tenant->evolution_instance_name . "\n";
    echo "Evolution API Key (specific): " . $tenant->evolution_api_key . "\n";

    if ($tenant->evolution_node_id) {
        $node = \SuiteZap\LawFirm\SaaS\Models\InfrastructureNode::on('mothership')
            ->find($tenant->evolution_node_id);
    
        echo "\nNode Info:\n";
        echo "Base URL: " . $node->base_url . "\n";
        echo "API Key (global): " . $node->api_key . "\n";
    }

} catch (\Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
