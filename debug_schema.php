<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DESCRIBE user_activities (ou activities):\n";
$cols = \Illuminate\Support\Facades\DB::select('DESCRIBE activities');
foreach ($cols as $c) {
    echo $c->Field . " | ";
} {
    echo "\n";
}

echo "\nDESCRIBE leads:\n";
$cols = \Illuminate\Support\Facades\DB::select('DESCRIBE leads');
foreach ($cols as $c) {
    echo $c->Field . " | ";
} {
    echo "\n";
}
