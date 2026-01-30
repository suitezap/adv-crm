<?php
$controller = app(\SuiteZap\LawFirm\Http\Controllers\Admin\Whatsapp\ConnectionController::class);
try {
    $response = $controller->status();
    $data = $response->getData(true);

    // Simplifica output para evitar travamento com base64
    if (isset($data['base64'])) {
        $data['base64'] = '... (BASE64 DATA PRESENT) ...';
    }

    echo "STATUS RESULT:\n";
    print_r($data);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
exit;
