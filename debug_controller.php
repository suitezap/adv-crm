<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n🔍 DIAGNÓSTICO DO CONTROLLER (Tentativa 3):\n";

try {
    auth()->loginUsingId(1);

    // Simula Request com Arrays para passar na validação 'array'
    $request = \Illuminate\Http\Request::create('/admin/activities', 'GET', [
        'sort' => [
            'column' => 'id',
            'order' => 'desc'
        ],
        'pagination' => [
            'page' => 1,
            'per_page' => 10
        ]
    ]);

    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $app->instance('request', $request);

    $controller = app(\Webkul\Admin\Http\Controllers\Activity\ActivityController::class);
    $response = $controller->get();

    echo "Status Code: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();

    $json = json_decode($content, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON Válido recebido.\n";

        $records = $json['records'] ?? $json['data'] ?? [];
        echo "📊 Records Count: " . count($records) . "\n";

        if (count($records) > 0) {
            echo "📝 Primeiro Registro:\n";
            print_r($records[0]);
        } else {
            echo "⚠️ JSON retornado mas sem registros (data/records vazio).\n";
        }
    } else {
        echo "❌ JSON INVÁLIDO ou CORROMPIDO!\n";
        echo "Conteúdo start: " . substr($content, 0, 500) . "\n";
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage();
    // echo "\n" . $e->getTraceAsString();
}
