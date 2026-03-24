<?php
$f = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$c = file_get_contents($f);

// Build $prices from the current $allCards — prices are in $card[7] in the new format
// But the template references $prices[$card[0]], so we need a mapping array.
// We use the CSV data to build the prices array.
$csvFile = __DIR__ . '/ai_studio_code (atlz).csv';
$rows = array_map('str_getcsv', file($csvFile));
array_shift($rows); // Remove header

$pricesPhp = "\$prices = [\n";
foreach ($rows as $row) {
    $versao = strtolower($row[0]);
    $titulo = $row[3];
    $custo  = $row[6];
    $key = strtoupper('API_'.$versao.'_'.preg_replace('/[^a-zA-Z0-9]/', '', $titulo));
    // Parse price to float
    $floatPrice = 0.0;
    $cleaned = str_replace(['R$', ' ', '.'], '', $custo);
    $cleaned = str_replace(',', '.', $cleaned);
    if (is_numeric($cleaned)) {
        $floatPrice = (float)$cleaned;
    }
    $pricesPhp .= "    '$key' => {$floatPrice},\n";
}
$pricesPhp .= "];\n";

// Inject $prices just after $allCards = [...];
$c = str_replace(
    "\n];\n                    @endphp",
    "\n];\n\n                        {$pricesPhp}\n                    @endphp",
    $c
);

file_put_contents($f, $c);
echo "Prices injected!\n";
