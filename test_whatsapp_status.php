$controller = app(\SuiteZap\LawFirm\Http\Controllers\Admin\Whatsapp\ConnectionController::class);
$status = $controller->status();
echo "STATUS RESULT:\n";
echo json_encode($status->getData(), JSON_PRETTY_PRINT);
echo "\n";
exit;