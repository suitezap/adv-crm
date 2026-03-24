<?php
$f = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$c = file_get_contents($f);

// Fix 1: remove duplicate ]; ]; @endphp in PHP block
$c = preg_replace('/\];\r?\n\s*\];\r?\n(\s*@endphp)/m', "];\n$1", $c);

// Fix 2: remove duplicate }; }; (the SVC_INFO closing + old leftover)
$c = preg_replace('/\};\r?\n\s*\};\r?\n(\s*var currentType)/m', "};\n\n$1", $c);

file_put_contents($f, $c);
echo "All brackets fixed!\n";
