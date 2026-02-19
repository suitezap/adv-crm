<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Build a proper request that will go through middleware
$request = Illuminate\Http\Request::create('/admin/juridico/processos/5/edit', 'GET');

// Simulate session + auth
$request->setLaravelSession($app['session.store']);

try {
    $response = $kernel->handle($request);
    echo "HTTP Status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() >= 400) {
        // Extract error from Ignition response
        $content = $response->getContent();

        // Try to find exception message in the response
        if (preg_match('/"message":"([^"]+)"/', $content, $matches)) {
            echo "Error Message: " . $matches[1] . "\n";
        }
        if (preg_match('/"class":"([^"]+)"/', $content, $matches)) {
            echo "Exception Class: " . $matches[1] . "\n";
        }
        if (preg_match('/"file":"([^"]+)"/', $content, $matches)) {
            echo "Error File: " . str_replace('\\\\', '\\', $matches[1]) . "\n";
        }
        if (preg_match('/"line":(\d+)/', $content, $matches)) {
            echo "Error Line: " . $matches[1] . "\n";
        }
    } else {
        echo "SUCCESS! Page rendered without errors.\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$kernel->terminate($request, $response ?? null);
