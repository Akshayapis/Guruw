<?php
/**
 * Cashpe — seed Firebase Firestore from the bundled local seed database.
 *
 * Usage (from the cashpe folder):
 *   FIREBASE_PROJECT_ID=... FIREBASE_CLIENT_EMAIL=... FIREBASE_PRIVATE_KEY=... \
 *   php tools/seed_firebase.php
 *
 * Add --reset to delete existing documents in these collections first.
 * Existing documents are left untouched by default (seed is idempotent).
 */
require_once(__DIR__ . '/../system/firebase.php');

if (!CashpeFirebase::enabled()) {
    fwrite(STDERR, "Firebase is not configured. Set FIREBASE_PROJECT_ID, FIREBASE_CLIENT_EMAIL and FIREBASE_PRIVATE_KEY.\n");
    exit(1);
}
$seedFile = __DIR__ . '/../storage/db.default.json';
$data = json_decode(file_get_contents($seedFile), true);
if (!is_array($data)) {
    fwrite(STDERR, "Could not read seed file: $seedFile\n");
    exit(1);
}
$reset = in_array('--reset', $argv, true);
$batchSize = 400;

foreach ($data as $collection => $rows) {
    if ($reset) {
        $existing = CashpeFirebase::listDocuments($collection);
        $deletes = array();
        foreach (array_keys($existing) as $id) {
            $deletes[] = array('op' => 'delete', 'collection' => $collection, 'id' => $id);
        }
        foreach (array_chunk($deletes, $batchSize) as $chunk) {
            CashpeFirebase::commit($chunk);
        }
    }
    $existing = CashpeFirebase::listDocuments($collection);
    $writes = array();
    $seeded = 0;
    foreach ($rows as $row) {
        $id = (string) (isset($row['id']) ? $row['id'] : '');
        if (!$reset && isset($existing[$id])) { continue; }
        $seeded++;
        $writes[] = array('op' => 'set', 'collection' => $collection, 'id' => $id, 'fields' => $row);
        if (count($writes) >= $batchSize) {
            CashpeFirebase::commit($writes);
            $writes = array();
        }
    }
    if ($writes) {
        CashpeFirebase::commit($writes);
    }
    echo str_pad($collection, 20) . ' ' . $seeded . " documents seeded\n";
}
echo "Done. Your Firebase project is ready for Cashpe.\n";
