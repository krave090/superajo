<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(BASE_URL . 'index.php'); }

$name       = isset($_POST['ajo_name'])  ? trim($_POST['ajo_name'])  : '';
$amount     = isset($_POST['amount'])    ? (int)$_POST['amount']     : 0;
$frequency  = isset($_POST['frequency']) ? trim($_POST['frequency']) : 'monthly';
$start_date = isset($_POST['start_date'])? trim($_POST['start_date']): '';
$membersRaw = isset($_POST['members'])   ? trim($_POST['members'])   : '';
$admin_email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
$admin_phone = isset($_POST['admin_phone']) ? trim($_POST['admin_phone']) : '';

if ($name === '' || $amount <= 0 || $admin_email === '') { 
    sa_die('Missing Information', 'Please fill all required fields including your email.'); 
}

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
if (count($names) < 2) { sa_die('Add at least 2 members', 'Paste your member names in the textarea.'); }

$totalMembers = count($names);
if ($totalMembers > 100) { $totalMembers = 100; $names = array_slice($names, 0, 100); }

$base = strtolower($name);
$base = preg_replace('/[^a-z0-9]+/', '-', $base);
$base = trim($base, '-');
if ($base === '') { $base = 'ajo'; }
$base = substr($base, 0, 60);

$slug = $base . '-' . rand_str(6);
$adminToken = 'admin_' . rand_str(10);
$approval_code = 'SUPER-' . strtoupper(rand_str(4));

$sql = "INSERT INTO ajos (name, slug, admin_token, amount, total_members, frequency, start_date, admin_email, admin_phone, approval_code, status) 
        VALUES ('" . esc($name) . "','" . esc($slug) . "','" . esc($adminToken) . "'," . (int)$amount . "," . (int)$totalMembers . ",'" . esc($frequency) . "','" . esc($start_date) . "','" . esc($admin_email) . "','" . esc($admin_phone) . "','" . esc($approval_code) . "','pending')";
if (!db_exec($sql)) { sa_die('Could not create the Ajo', 'Database error: ' . mysqli_error($conn)); }

$ajoId = (int)mysqli_insert_id($conn);
$position = 1;
foreach ($names as $memberName) {
    db_exec("INSERT INTO members (ajo_id, name, payout_position, payout_received) VALUES (" . $ajoId . ",'" . esc($memberName) . "'," . (int)$position . ",0)");
    $position++;
}

redirect(BASE_URL . 'status.php?code=' . urlencode($approval_code));
