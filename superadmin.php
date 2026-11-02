<?php
require __DIR__ . '/config.php';

// Password Protection
$pass = $_POST['pass'] ?? $_GET['pass'] ?? $_SESSION['superadmin_pass'] ?? '';
if ($pass !== 'super123') {
    echo '<div style="max-width:400px;margin:100px auto;text-align:center;font-family:sans-serif;">';
    echo '<h2>Super Admin Login</h2>';
    echo '<form method="POST"><input type="password" name="pass" placeholder="Password" style="padding:10px;width:100%;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;">';
    echo '<button type="submit" style="padding:10px 20px;background:#0b7d45;color:#fff;border:none;border-radius:5px;width:100%;">Login</button></form></div>';
    exit;
}
$_SESSION['superadmin_pass'] = $pass;

if (isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $k => $v) {
        db_exec("UPDATE settings SET setting_value = '" . esc($v) . "' WHERE setting_key = '" . esc($k) . "'");
    }
    redirect(BASE_URL . 'superadmin.php?msg=saved');
}

if (isset($_POST['action']) && isset($_POST['ajo_id'])) {
    $id = (int)$_POST['ajo_id'];
    if ($_POST['action'] === 'approve') {
        $expires = date('Y-m-d', strtotime('+30 days'));
        db_exec("UPDATE ajos SET status = 'active', expires_at = '$expires' WHERE id = $id");
    } elseif ($_POST['action'] === 'suspend') {
        db_exec("UPDATE ajos SET status = 'suspended' WHERE id = $id");
    }
    redirect(BASE_URL . 'superadmin.php');
}

$pending = db_all("SELECT * FROM ajos WHERE status = 'pending' ORDER BY id DESC");
$active = db_all("SELECT * FROM ajos WHERE status != 'pending' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Super Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
    <style>
        body { background: #f4f7f6; padding: 20px; font-family: sans-serif; }
        .box { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .ajo-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
    <h1>Super Admin Dashboard</h1>
    <a href="index.php" style="color:#0b7d45; font-weight:bold; text-decoration:none;">&larr; Back to Site</a>

    <div class="grid" style="margin-top:20px;">
        <div class="box">
            <h2>Platform Settings</h2>
            <form method="POST">
                <input type="hidden" name="update_settings" value="1">
                <p><b>Subscription Price (N):</b><br><input class="input" type="number" name="settings[subscription_price]" value="<?= h(get_setting('subscription_price')) ?>" style="width:100%;padding:8px;"></p>
                <p><b>Bank Name:</b><br><input class="input" type="text" name="settings[bank_name]" value="<?= h(get_setting('bank_name')) ?>" style="width:100%;padding:8px;"></p>
                <p><b>Account Number:</b><br><input class="input" type="text" name="settings[account_number]" value="<?= h(get_setting('account_number')) ?>" style="width:100%;padding:8px;"></p>
                <p><b>Account Name:</b><br><input class="input" type="text" name="settings[account_name]" value="<?= h(get_setting('account_name')) ?>" style="width:100%;padding:8px;"></p>
                <p><b>WhatsApp Number (e.g. 2348012345678):</b><br><input class="input" type="text" name="settings[whatsapp_number]" value="<?= h(get_setting('whatsapp_number')) ?>" style="width:100%;padding:8px;"></p>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save Settings</button>
            </form>
        </div>

        <div class="box">
            <h2>Pending Approvals (<?= count($pending) ?>)</h2>
            <?php if(!$pending) echo "<p>No pending requests.</p>"; ?>
            <?php foreach ($pending as $p): ?>
                <div class="ajo-card" style="border-left: 4px solid #f59e0b;">
                    <div>
                        <b><?= h($p['name']) ?></b><br>
                        <span style="font-size:12px; color:#64748b;">Code: <?= h($p['approval_code']) ?> | Email: <?= h($p['admin_email']) ?></span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="ajo_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="box" style="margin-top:20px;">
        <h2>Active & Suspended Ajos</h2>
        <?php foreach ($active as $a): ?>
            <div class="ajo-card">
                <div>
                    <b><?= h($a['name']) ?></b> 
                    <span class="badge" style="background:<?= $a['status']=='active'?'#10b981':'#ef4444' ?>; color:#fff;"><?= strtoupper($a['status']) ?></span><br>
                    <span style="font-size:12px; color:#64748b;">Expires: <?= $a['expires_at'] ?> | <a href="admin.php?token=<?= $a['admin_token'] ?>" target="_blank">View Dashboard</a></span>
                </div>
                <form method="POST">
                    <input type="hidden" name="ajo_id" value="<?= $a['id'] ?>">
                    <?php if($a['status'] == 'active'): ?>
                        <input type="hidden" name="action" value="suspend">
                        <button type="submit" class="btn btn-danger btn-sm">Suspend</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-sm">Reactivate</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
