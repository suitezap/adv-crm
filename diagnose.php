<?php
// Bootstrap Laravel
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$compiler = $app->make(Illuminate\View\Compilers\BladeCompiler::class);
$file = 'packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';

try {
    $compiled = $compiler->compileString(file_get_contents($file));
    echo "Blade compilation successful\n";
    // Try PHP parse
    $result = token_get_all($compiled, TOKEN_PARSE);
    echo "PHP syntax check passed!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "In file: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
