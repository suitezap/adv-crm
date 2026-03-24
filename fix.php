<?php
$f = 'packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$c = file_get_contents($f);
$c = str_replace("];\n                    ];\n                    @endphp", "];\n                    @endphp", $c);
file_put_contents($f, $c);
echo "Fixed";
