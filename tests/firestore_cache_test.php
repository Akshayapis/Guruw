<?php
// Mock Firestore's document API to verify read-after-write in one PHP request.
class CashpeFirebase {
    public static $docs = array('tb_slab' => array('1' => array('id' => '1', 'name' => 'Old')));
    public static function enabled() { return true; }
    public static function listDocuments($table) { return isset(self::$docs[$table]) ? self::$docs[$table] : array(); }
    public static function commit($writes) {
        foreach ($writes as $write) {
            if ($write['op'] === 'delete') unset(self::$docs[$write['collection']][$write['id']]);
            else self::$docs[$write['collection']][$write['id']] = $write['fields'];
        }
    }
}
require_once __DIR__ . '/../system/dbfire.php';
function check($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
check(sql_num_rows(sql_query("SELECT * FROM tb_slab WHERE name='Old'")) === 1, 'initial read');
check((bool)sql_query("UPDATE tb_slab SET name='New' WHERE id='1'"), 'update');
check(sql_num_rows(sql_query("SELECT * FROM tb_slab WHERE name='New'")) === 1, 'read after update');
check((bool)sql_query("INSERT INTO tb_slab (name) VALUES ('Extra')"), 'insert');
check(sql_num_rows(sql_query("SELECT * FROM tb_slab WHERE name='Extra'")) === 1, 'read after insert');
check((bool)sql_query("DELETE FROM tb_slab WHERE name='Extra'"), 'delete');
check(sql_num_rows(sql_query("SELECT * FROM tb_slab WHERE name='Extra'")) === 0, 'read after delete');
echo "PASS: Firestore read-after-write cache\n";
