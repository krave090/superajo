<?php
require __DIR__ . '/config.php';

// Password Protection
$pass = $_POST['pass'] ?? $_GET['pass'] ?? $_SESSION['superadmin_pass'] ?? '';
if ($pass !== 'super123') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Super Admin Login</title>';
    echo '<link rel="stylesheet" href="' . BASE_URL . 'assets/style.css">';
    echo '<div style="max-width:400px;margin:100px auto;text-align:center;padding:20px;background:#fff;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">';
    echo '<h2 style="color:#0b7d45;margin-bottom:20px;">Super Admin Login</h2>';
    echo '<form method="POST"><input class="input" type="password" name="pass" placeholder="Password" style="margin-bottom:15px;">';
    echo '<button type="submit" class="btn btn-primary" style="width:100%;">Login</button></form></div></html>';
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
    <style>
        body { background: #f4f7f6; padding: 16px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { font-size: 22px; margin: 0; color: #0f172a; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
        @media (min-width: 768px) { .grid { grid-template-columns: 1fr 1fr; } }
        .box { background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        .box h2 { font-size: 18px; margin-top: 0; margin-bottom: 16px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .input-group input:focus { border-color: #0b7d45; outline: none; }
        
        .ajo-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 12px; }
        @media (min-width: 480px) { .ajo-card { flex-direction: row; justify-content: space-between; align-items: center; } }
        .ajo-card .info { flex: 1; }
        .ajo-card .title { font-weight: 800; font-size: 16px; color: #0f172a; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .ajo-card .meta { font-size: 13px; color: #64748b; word-break: break-all; margin-bottom: 4px; }
        .code-badge { background: #f1f5f9; color: #0f172a; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700; font-size: 13px; border: 1px solid #cbd5e1; }
        .ajo-card .actions { display: flex; gap: 10px; width: 100%; margin-top: 10px; }
        @media (min-width: 480px) { .ajo-card .actions { width: auto; margin-top: 0; } }
        .btn-sm { padding: 10px 16px; font-size: 14px; border-radius: 8px; text-align: center; flex: 1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Super Admin</h1>
        <a href="index.php" style="color:#0b7d45; font-weight:bold; text-decoration:none; font-size:14px;">&larr; Back to Site</a>
    </div>

    <div class="grid">
        <!-- SETTINGS PANEL -->
        <div class="box">
            <h2>Platform Settings</h2>
            <form method="POST">
                <input type="hidden" name="update_settings" value="1">
                <div class="input-group">
                    <label>Subscription Price (N)</label>
                    <input type="number" name="settings[subscription_price]" value="<?= h(get_setting('subscription_price')) ?>">
                </div>
                <div class="input-group">
                    <label>Bank Name</label>
                    <input type="text" name="settings[bank_name]" value="<?= h(get_setting('bank_name')) ?>">
                </div>
                <div class="input-group">
                    <label>Account Number</label>
                    <input type="text" name="settings[account_number]" value="<?= h(get_setting('account_number')) ?>">
                </div>
                <div class="input-group">
                    <label>Account Name</label>
                    <input type="text" name="settings[account_name]" value="<?= h(get_setting('account_name')) ?>">
                </div>
                <div class="input-group">
                    <label>WhatsApp Number (e.g. 2348012345678)</label>
                    <input type="text" name="settings[whatsapp_number]" value="<?= h(get_setting('whatsapp_number')) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">Save Settings</button>
            </form>
        </div>

        <!-- PENDING APPROVALS -->
        <div class="box">
            <h2>Pending Approvals (<?= count($pending) ?>)</h2>
            <?php if(!$pending): ?>
                <p style="color:#64748b; text-align:center; padding:20px 0;">No pending requests.</p>
            <?php endif; ?>
            
            <?php foreach ($pending as $p): ?>
                <div class="ajo-card" style="border-left: 4px solid #f59e0b;">
                    <div class="info">
                        <div class="title"><?= h($p['name']) ?></div>
                        <div class="meta">Code: <span class="code-badge"><?= h($p['approval_code']) ?></span></div>
                        <div class="meta">Email: <?= h($p['admin_email']) ?></div>
                    </div>
                    <div class="actions">
                        <form method="POST" style="width:100%; display:flex;">
                            <input type="hidden" name="ajo_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm" style="width:100%;">Approve</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ACTIVE / SUSPENDED AJOS -->
    <div class="box">
        <h2>Active & Suspended Ajos</h2>
        <?php if(!$active): ?>
            <p style="color:#64748b; text-align:center; padding:20px 0;">No active Ajos yet.</p>
        <?php endif; ?>

        <?php foreach ($active as $a): ?>
            <div class="ajo-card">
                <div class="info">
                    <div class="title">
                        <?= h($a['name']) ?> 
                        <span class="badge" style="background:<?= $a['status']=='active'?'#10b981':'#ef4444' ?>; color:#fff; font-size:10px;"><?= strtoupper($a['status']) ?></span>
                    </div>
                    <!-- ADDED TRACKING CODE HERE -->
                    <div class="meta">Tracking Code: <span class="code-badge"><?= h($a['approval_code']) ?></span></div>
                    <div class="meta">Email: <?= h($a['admin_email']) ?></div>
                    <div class="meta">Expires: <?= $a['expires_at'] ? date('d M Y', strtotime($a['expires_at'])) : 'Never' ?></div>
                    <div class="meta"><a href="admin.php?token=<?= $a['admin_token'] ?>" target="_blank" style="color:#0b7d45; font-weight:bold;">Open Dashboard &rarr;</a></div>
                </div>
                <div class="actions">
                    <form method="POST" style="width:100%; display:flex;">
                        <input type="hidden" name="ajo_id" value="<?= $a['id'] ?>">
                        <?php if($a['status'] == 'active'): ?>
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">Suspend</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm" style="width:100%;">Reactivate 30 Days</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
