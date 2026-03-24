<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \Webkul\User\Models\User::first();
auth()->guard('user')->login($user);

$lead = \Webkul\Lead\Models\Lead::find(4);
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\MessageBag());
$html = view('admin::leads.view', ['lead' => $lead])->render();
file_put_contents('lead_dump.html', $html);
echo "HTML salvo em lead_dump.html\n";
