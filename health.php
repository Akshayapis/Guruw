<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/system/function.php');
$result = sql_query("SELECT * FROM `tb_settings` ORDER BY `id` ASC LIMIT 1");
$configured = CashpeFirebase::enabled();
$settings = sql_num_rows($result);
$status = $result === false ? 'database_error' : ($settings ? 'ok' : 'not_seeded');
echo json_encode(array(
    'status' => $status,
    'app' => 'Cashpe',
    'version' => '2026-11-20-r3',
    'database' => $configured ? 'firestore' : ($result === false ? 'incomplete_configuration' : 'local'),
    'settings' => $settings,
    'hint' => $status === 'ok' ? '' : 'Check Render logs and FIREBASE settings. If Firestore is empty, seed it once as described in FIREBASE_RULES_SETUP.txt.'
));
