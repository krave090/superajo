<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(BASE_URL . 'index.php'); }
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
if ($slug === '') { redirect(BASE_URL . 'index.php'); }
$ajo = db_one("SELECT * FROM ajos WHERE slug = '" . esc($slug) . "' LIMIT 1");
if (!$ajo) { sa_die('Ajo not found', 'The Ajo link is invalid.', BASE_URL . 'index.php', 404); }
$back = BASE_URL . 'ajo.php?slug=' . urlencode($slug);
$memberId = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$member = db_one("SELECT * FROM members WHERE id = $memberId AND ajo_id = " . (int)$ajo['id'] . " LIMIT 1");
if (!$member) { redirect($back . '&msg=err_member'); }
$existing = db_one("SELECT id FROM payments WHERE ajo_id = " . (int)$ajo['id'] . " AND member_id = $memberId AND period_label = '" . esc(CURRENT_PERIOD) . "' AND status = 'confirmed' LIMIT 1");
if ($existing) { redirect($back . '&msg=already'); }
$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
if ($amount <= 0) { $amount = (int)$ajo['amount']; }
$txn = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
if (mb_strlen($txn) > 100) { $txn = mb_substr($txn, 0, 100); }
if (!isset($_FILES['proof'])) { redirect($back . '&msg=err_file'); }
$file = $_FILES['proof'];
if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) { redirect($back . '&msg=err_file'); }
if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { redirect($back . '&msg=err_file'); }
if ($file['size'] > MAX_UPLOAD_BYTES) { redirect($back . '&msg=err_size'); }
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, array('jpg','jpeg','png'), true)) { redirect($back . '&msg=err_type'); }
$info = @getimagesize($file['tmp_name']);
if ($info === false) { redirect($back . '&msg=err_type'); }
if (!in_array($info['mime'], array('image/jpeg','image/png'), true)) { redirect($back . '&msg=err_type'); }
if (!ensure_upload_dir()) { redirect($back . '&msg=err_save'); }
$fileName = 'proof_' . date('Ymd_His') . '_' . rand_str(8) . '.' . $ext;
$target = UPLOAD_DIR . '/' . $fileName;
if (!@move_uploaded_file($file['tmp_name'], $target)) { redirect($back . '&msg=err_save'); }
@chmod($target, 0644);
$proofPath = 'uploads/' . $fileName;
$sql = "INSERT INTO payments (ajo_id, member_id, period_label, amount, proof_image, status, transaction_id) VALUES (" . (int)$ajo['id'] . ",$memberId,'" . esc(CURRENT_PERIOD) . "'," . (int)$amount . ",'" . esc($proofPath) . "','pending','" . esc($txn) . "')";
if (!db_exec($sql)) { @unlink($target); redirect($back . '&msg=err_db'); }
redirect($back . '&msg=uploaded');