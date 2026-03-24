<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $node = DB::connection('mothership')->table('infrastructure_nodes')->where('type', 'asaas')->first();
    if ($node) {
        $meta = json_decode($node->meta_data, true) ?? [];
        $meta['pix_key'] = '13748479-eac5-4d7f-90aa-8fe43d4a9081';
        
        DB::connection('mothership')
            ->table('infrastructure_nodes')
            ->where('type', 'asaas')
            ->update(['meta_data' => json_encode($meta)]);
            
        echo "Chave PIX registrada no meta_data do MotherShip com sucesso!\n";
    } else {
        echo "Nó asaas não encontrado no MotherShip!\n";
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
