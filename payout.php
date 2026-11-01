<?php
require __DIR__ . '/config.php';
$token = '';
if (isset($_POST['token'])) { $token = trim($_POST['token']); }
elseif (isset($_GET['token'])) { $token = trim($_GET['token']); }
if ($token === '') { redirect(BASE_URL . 'index.php'); }
$ajo = db_one("SELECT * FROM ajos WHERE admin_token = '" . esc($token) . "' LIMIT 1");
if (!$ajo) { sa_die('Private dashboard not found', 'This admin link is invalid.', BASE_URL . 'index.php', 404); }
$ajoId = (int)$ajo['id'];
$back = BASE_URL . 'admin.php?token=' . urlencode($token);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect($back); }
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
switch ($action) {
    case 'confirm':
    case 'reject':
        $paymentId = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
        if ($paymentId <= 0) { redirect($back . '&msg=pay_rejected'); }
        $status = ($action === 'confirm') ? 'confirmed' : 'rejected';
        $payment = db_one("SELECT id FROM payments WHERE id = $paymentId AND ajo_id = $ajoId LIMIT 1");
        if (!$payment) { redirect($back . '&msg=pay_rejected'); }
        db_exec("UPDATE payments SET status = '" . esc($status) . "' WHERE id = $paymentId AND ajo_id = $ajoId");
        if ($action === 'confirm') { redirect($back . '&msg=pay_confirmed'); }
        redirect($back . '&msg=pay_rejected');
        break;
    case 'toggle_payout':
        $memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
        if ($memberId <= 0) { redirect($back . '&msg=payout_updated'); }
        $member = db_one("SELECT * FROM members WHERE id = $memberId AND ajo_id = $ajoId LIMIT 1");
        if (!$member) { redirect($back . '&msg=payout_updated'); }
        $newVal = ((int)$member['payout_received'] === 1) ? 0 : 1;
        db_exec("UPDATE members SET payout_received = $newVal WHERE id = $memberId AND ajo_id = $ajoId");
        redirect($back . '&msg=payout_updated');
        break;
    default:
        redirect($back);
}