<?php
// Regression: older seed scripts wrote document IDs without an id field.
// Run: php tests/firestore_id_test.php
class CashpeFirebase {
    public static function enabled() { return true; }
    public static function listDocuments($collection) {
        return $collection === 'tb_partner' ? array('268' => array('login_id' => 'ADMIN', 'status' => 'active')) : array();
    }
    public static function commit($writes) { return true; }
}
require_once __DIR__ . '/../system/dbfire.php';
$user = sql_fetch_array(sql_query("SELECT * FROM tb_partner WHERE login_id='ADMIN'"));
if (!$user || $user['id'] !== '268') {
    fwrite(STDERR, "FAIL: missing document ID in row\n");
    exit(1);
}
echo "PASS: Firestore document ID is available as row id\n";
