<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 DATA INTEGRITY CHECK:\n";

// 1. Leads with weird IDs
$weirdLeads = \Illuminate\Support\Facades\DB::table('leads')
    ->whereNull('id')
    ->orWhere('id', '=', 0)
    ->orWhere('id', '=', '')
    ->get();
echo "⚠️ Leads with ID=0/Null/Empty: " . $weirdLeads->count() . "\n";
if ($weirdLeads->count() > 0) {
    echo $weirdLeads->first()->id . " | " . $weirdLeads->first()->title . "\n";
}

// 2. Lead Activities with invalid lead_id
$badLinks = \Illuminate\Support\Facades\DB::table('lead_activities')
    ->whereNull('lead_id')
    ->orWhere('lead_id', '=', 0)
    ->get();
echo "⚠️ Lead_Activities with lead_id=0/Null: " . $badLinks->count() . "\n";

// 3. The Dangerous Combination Checks
// Find any result where Lead Title is PRESENT but ID is MISSING
$zombies = \Illuminate\Support\Facades\DB::select("
    SELECT 
        a.id as activity_id, 
        l.title as lead_title, 
        l.id as lead_id
    FROM activities a
    LEFT JOIN lead_activities la ON a.id = la.activity_id
    LEFT JOIN leads l ON la.lead_id = l.id
    WHERE l.title IS NOT NULL AND (l.id IS NULL OR l.id = 0 OR l.id = '')
");
echo "🧟 Zombies (Title=Yes, ID=Bad): " . count($zombies) . "\n";
if (count($zombies) > 0) {
    print_r($zombies[0]);
}

// 4. Test Route Generation specifically for 0
try {
    echo "🧪 Test Route with ID=0: " . route('admin.leads.view', 0) . "\n";
} catch (\Exception $e) {
    echo "❌ Route with 0 failed: " . $e->getMessage() . "\n";
}
try {
    echo "🧪 Test Route with ID=null: " . route('admin.leads.view', null) . "\n";
} catch (\Exception $e) {
    echo "❌ Route with null failed: " . $e->getMessage() . "\n";
}
