<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));
    echo "Current Tenant ID: " . $tenantId . "\n\n";

    $tenant = \Illuminate\Support\Facades\DB::connection('mothership')
        ->table('tenants')
        ->where('id', $tenantId)
        ->first();
        
    if (!$tenant) {
        die("Tenant $tenantId não encontrado no DB mothership.\n");
    }
    
    echo "Found Tenant: " . $tenant->id . "\n";
    echo "Evolution Node ID: " . $tenant->evolution_node_id . "\n";
    echo "Evolution Instance Name: " . $tenant->evolution_instance_name . "\n";
    
    if ($tenant->evolution_node_id) {
        $node = \Illuminate\Support\Facades\DB::connection('mothership')
            ->table('infrastructure_nodes')
            ->where('id', $tenant->evolution_node_id)
            ->first();
            
        if ($node) {
            echo "\nFound Node: \n";
            echo "URL: " . $node->base_url . "\n";
            echo "Key (length): " . strlen($node->api_key) . "\n";
        } else {
            echo "\nNode ID {$tenant->evolution_node_id} NOT FOUND in infrastructure_nodes!\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
