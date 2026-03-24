<?php
// Restore base from backup to ensure we have the exact right macro HTML
$backup = file_get_contents('c:/laragon/www/adv-crm/fix_create4_p1.txt');
file_put_contents('c:/laragon/www/adv-crm/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/create.blade.php', $backup);

// Run the replacement logic on the restored file
include('c:/laragon/www/adv-crm/update_modals.php');
include('c:/laragon/www/adv-crm/fix_modals_3.php');
echo "Modals regenerated properly";
