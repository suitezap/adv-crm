<?php
// Just use the Blade compiler directly — it uses the Filesystem
// but doesn't need a full app container for compileString()
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/vendor/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require $file;
});

require __DIR__ . '/vendor/autoload.php';

$blade = new class {
    public function compileString(string $source): string {
        // Minimal: just strip @php/@endphp for a raw PHP parse
        $s = preg_replace('/@php\b/', '<?php', $source);
        $s = preg_replace('/@endphp\b/', '?>', $s);
        // strip blade directives @foreach etc
        $s = preg_replace('/@foreach.*$/', '<?php foreach([] as $x): ?>', $s);
        $s = preg_replace('/@endforeach/', '<?php endforeach; ?>', $s);
        $s = preg_replace('/@if.*$/', '<?php if(true): ?>', $s);
        $s = preg_replace('/@endif/', '<?php endif; ?>', $s);
        // strip {{ }} interpolation
        $s = preg_replace('/\{\{.*?\}\}/', '', $s);
        $s = preg_replace('/\{\!\!.*?\!\!\}/', '', $s);
        return $s;
    }
};

$file = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$content = file_get_contents($file);
$compiled = $blade->compileString($content);

$tmpFile = sys_get_temp_dir() . '/blade_lint.php';
file_put_contents($tmpFile, $compiled);

$output = [];
$ret = 0;
exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $ret);
echo implode("\n", $output) . "\n";

if ($ret !== 0) {
    // Extract error line from the compiled file and map back to source
    foreach ($output as $line) {
        if (preg_match('/on line (\d+)/', $line, $m)) {
            $errorLine = (int)$m[1];
            $lines = file($tmpFile);
            echo "\n=== Context around compiled line $errorLine ===\n";
            for ($i = max(0, $errorLine - 4); $i <= min(count($lines)-1, $errorLine + 2); $i++) {
                echo ($i+1) . ": " . $lines[$i];
            }
        }
    }
}

@unlink($tmpFile);
