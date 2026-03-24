<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use SuiteZap\LawFirm\SaaS\Services\AsaasService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

try {
    $config = AsaasService::getConfig();
    $ownerData = AsaasService::getOwnerCustomerData();
    $tenantId = MotherShipService::getTenantId();

    $payload = [
        'billingTypes'    => ['PIX', 'CREDIT_CARD'],
        'chargeTypes'     => ['DETACHED'],
        'minutesToExpire' => 60,
        'callback'        => [
            'successUrl' => 'https://adv-crm.test/admin/saas?payment=success',
            'cancelUrl'  => 'https://adv-crm.test/admin/saas?payment=cancelled',
            'expiredUrl' => 'https://adv-crm.test/admin/saas?payment=expired',
        ],
        'items' => [
            [
                'name'        => "Pacote de 500 Créditos de IA",
                'description' => "Recarga de créditos para os Assistentes de IA do LawFirm CRM. Tenant: {$tenantId}",
                'quantity'    => 1,
                'value'       => 50.00,
            ],
        ],
        'externalReference' => "{$tenantId}|credit|500",
        'customerData'     => $ownerData,
    ];

    echo "Payload sent:\n";
    print_r($payload);

    $http = \Illuminate\Support\Facades\Http::withHeaders([
        'access_token' => $config['api_key'],
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ]);
    
    $url = $config['api_url'] . '/v3/checkouts';
    $res = $http->post($url, $payload);
    
    echo "\n\n----- RESPONSE -----\n";
    echo "Status: " . $res->status() . "\n";
    echo "Body: " . $res->body() . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

