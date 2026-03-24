<?php
// Minimal bootstrap
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';

$file = 'packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$content = file_get_contents($file);

// Use a minimal Blade compiler without full Laravel bootstrap
$compiler = new Illuminate\View\Compilers\BladeCompiler(
    new Illuminate\Filesystem\Filesystem(),
    sys_get_temp_dir()
);

try {
    $compiled = $compiler->compileString($content);
    $tmpFile = sys_get_temp_dir() . '/blade_check_' . md5($file) . '.php';
    file_put_contents($tmpFile, $compiled);

    // Use php -l to syntax check the compiled file
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($tmpFile) . " 2>&1", $output, $ret);
    echo implode("\n", $output) . "\n";
    echo "Return code: $ret\n";
    @unlink($tmpFile);
} catch (Throwable $e) {
    echo "Blade compile error: " . $e->getMessage() . "\n";
}
