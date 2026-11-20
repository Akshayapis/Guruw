<?php
// Isolated mock-only Firestore REST regression; never run against production.
putenv('FIREBASE_PROJECT_ID=cashpe-test');
putenv('FIRESTORE_EMULATOR_HOST=127.0.0.1:8788');
$_SERVER['SERVER_NAME'] = 'localhost';
$argv = array('seed_firebase.php');
require __DIR__ . '/../tools/seed_firebase.php';
require __DIR__ . '/../system/function.php';
function expect_ok($value, $message) {
    if (!$value) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    echo "PASS: $message\n";
}
$admin = sql_fetch_array(sql_query("SELECT * FROM tb_partner WHERE login_id='ADMIN' AND type='1' AND status='active'"));
expect_ok($admin && pwd_verify('admin123', $admin['login_pwd']), 'seeded admin read and password');
expect_ok(sql_query("INSERT INTO tb_partner (login_id,login_pwd,status,type) VALUES ('http-test','" . pwd_hash('example-password') . "','active','0')"), 'write over REST');
$user = sql_fetch_array(sql_query("SELECT * FROM tb_partner WHERE login_id='http-test'"));
expect_ok($user && isset($user['id']) && pwd_verify('example-password', $user['login_pwd']), 'read after write and document id');
expect_ok(sql_query("UPDATE tb_partner SET status='inactive' WHERE id='" . $user['id'] . "'"), 'update over REST');
expect_ok(sql_num_rows(sql_query("SELECT * FROM tb_partner WHERE login_id='http-test' AND status='inactive'")) === 1, 'read after update');
