<?php
$f = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$c = file_get_contents($f);
$c = preg_replace('/\];\s*\];\s*@endphp/s', "\n];\n                    @endphp", $c);
file_put_contents($f, $c);
echo "Fixed array bracket\n";
