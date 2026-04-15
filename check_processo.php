<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Show the last 5 processos with their user_id and whatsapp columns
$rows = \Illuminate\Support\Facades\DB::table('processos')
    ->select('id','titulo','user_id','whatsapp_responsavel','updated_at')
    ->latest()
    ->limit(5)
    ->get();

foreach ($rows as $r) {
    echo "ID:{$r->id} | {$r->titulo} | user_id:" . ($r->user_id ?? 'NULL') 
        . " | whatsapp:" . ($r->whatsapp_responsavel ?? 'NULL')
        . " | updated:{$r->updated_at}\n";
}

// Check what DB column type whatsapp_responsavel is
$cols = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM processos WHERE Field IN ('user_id','whatsapp_responsavel')");
echo "\nColumn definitions:\n";
foreach ($cols as $c) {
    echo "{$c->Field}: {$c->Type} | Null:{$c->Null} | Default:" . ($c->Default ?? 'NULL') . "\n";
}
