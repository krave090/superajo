<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(BASE_URL . 'index.php'); }
$name       = isset($_POST['ajo_name'])  ? trim($_POST['ajo_name'])  : '';
$amount     = isset($_POST['amount'])    ? (int)$_POST['amount']     : 0;
$frequency  = isset($_POST['frequency']) ? trim($_POST['frequency']) : 'monthly';
$start_date = isset($_POST['start_date'])? trim($_POST['start_date']): '';
$membersRaw = isset($_POST['members'])   ? trim($_POST['members'])   : '';
if ($name === '') { sa_die('Ajo name is required', 'Please go back and give your Ajo a name, e.g. "Dec 20k Ajo".'); }
if ($amount <= 0) { sa_die('Invalid amount', 'The contribution amount must be greater than zero.'); }
if (!in_array($frequency, array('daily','weekly','monthly'), true)) { $frequency = 'monthly'; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) { $start_date = date('Y-m-d'); }
$lines = preg_split('/\r\n|\r|\n/', $membersRaw);
$names = array();
foreach ($lines as $line) {
    $n = trim(preg_replace('/\s+/', ' ', $line));
    if ($n === '') { continue; }
    if (mb_strlen($n) > 255) { $n = mb_substr($n, 0, 255); }
    $key = mb_strtolower($n);
    if (!isset($names[$key])) { $names[$key] = $n; }
}
$names = array_values($names);
if (count($names) < 2) { sa_die('Add at least 2 members', 'Paste your member names in the textarea - one name per line.'); }
$totalMembers = count($names);
if ($totalMembers > 100) { $totalMembers = 100; $names = array_slice($names, 0, 100); }
$base = strtolower($name);
$base = preg_replace('/[^a-z0-9]+/', '-', $base);
$base = trim($base, '-');
if ($base === '') { $base = 'ajo'; }
$base = substr($base, 0, 60);
$slug = ''; $tries = 0;
do { $slug = $base . '-' . rand_str(6); $exists = db_one("SELECT id FROM ajos WHERE slug = '" . esc($slug) . "' LIMIT 1"); $tries++; } while ($exists && $tries < 20);
if ($exists) { $slug = $base . '-' . rand_str(10); }
$adminToken = ''; $tries = 0;
do { $adminToken = 'admin_' . rand_str(10); $existsTok = db_one("SELECT id FROM ajos WHERE admin_token = '" . esc($adminToken) . "' LIMIT 1"); $tries++; } while ($existsTok && $tries < 20);
if ($existsTok) { $adminToken = 'admin_' . rand_str(16); }
$sql = "INSERT INTO ajos (name, slug, admin_token, amount, total_members, frequency, start_date) VALUES ('" . esc($name) . "','" . esc($slug) . "','" . esc($adminToken) . "'," . (int)$amount . "," . (int)$totalMembers . ",'" . esc($frequency) . "','" . esc($start_date) . "')";
if (!db_exec($sql)) { sa_die('Could not create the Ajo', 'Database error: ' . mysqli_error($conn)); }
$ajoId = (int)mysqli_insert_id($conn);
$position = 1;
foreach ($names as $memberName) {
    db_exec("INSERT INTO members (ajo_id, name, payout_position, payout_received) VALUES (" . $ajoId . ",'" . esc($memberName) . "'," . (int)$position . ",0)");
    $position++;
}
redirect(BASE_URL . 'admin.php?token=' . urlencode($adminToken) . '&msg=created');