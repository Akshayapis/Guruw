<?php
/**
 * Cashpe — database bootstrap.
 * The original application used mysqli here. It now boots the Firebase
 * (Firestore) compatibility layer instead; every business-logic file stays
 * untouched and keeps calling sql_query(), sql_fetch_array(), etc.
 */
error_reporting(0);
require_once(__DIR__ . '/dbconfig.php');
require_once(__DIR__ . '/firebase.php');
require_once(__DIR__ . '/localstore.php');
require_once(__DIR__ . '/dbfire.php');

// Backward-compatible globals used by the payment status helpers.
$hypto_url = getenv('HYPTO_URL') ?: "https://partners.hypto.in";
$smsapi_url = getenv('SMS_API_URL') ?: "http://88.99.209.80";
