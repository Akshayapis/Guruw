<?php
/**
 * Operator-only admin recovery. Run in a trusted CLI with the SAME Firebase
 * environment as the web service. No HTTP endpoint and no automatic reset.
 * Set CASHPE_ADMIN_ID and CASHPE_ADMIN_NEW_PASSWORD as temporary environment
 * variables; unset them immediately afterwards. This never creates accounts.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../system/function.php';
$id = trim((string) getenv('CASHPE_ADMIN_ID'));
$password = (string) getenv('CASHPE_ADMIN_NEW_PASSWORD');
if (!CashpeFirebase::enabled()) {
    fwrite(STDERR, "Firestore configuration is required; refusing local fallback.\n");
    exit(1);
}
if ($id === '' || strlen($password) < 12) {
    fwrite(STDERR, "Set CASHPE_ADMIN_ID and CASHPE_ADMIN_NEW_PASSWORD (at least 12 characters).\n");
    exit(1);
}
$escaped = cashpe_auth_escape($id);
$result = sql_query("SELECT * FROM tb_partner WHERE login_id='" . $escaped . "' AND type='1'");
if ($result === false) {
    fwrite(STDERR, "Database query failed. Check server logs and Firebase settings.\n");
    exit(1);
}
if (sql_num_rows($result) !== 1) {
    fwrite(STDERR, "Expected exactly one existing admin with that username; no changes made. Check Firestore and seed setup.\n");
    exit(1);
}
$user = sql_fetch_array($result);
if (!isset($user['id']) || !ctype_digit((string) $user['id'])) {
    fwrite(STDERR, "Admin ID is invalid; no changes made.\n");
    exit(1);
}
$hash = cashpe_auth_escape(pwd_hash($password));
$update = sql_query("UPDATE tb_partner SET login_pwd='" . $hash . "' WHERE id='" . $user['id'] . "'");
if (!$update || $update->affected_rows !== 1) {
    fwrite(STDERR, "Password update failed; check server logs.\n");
    exit(1);
}
echo "Password updated for the existing admin. Account status: " . (isset($user['status']) && $user['status'] === 'active' ? 'active' : 'not active') . ".\n";
echo "Remove temporary recovery environment variables now.\n";
