<?php
require __DIR__ . '/config.php';
$code = $_GET['code'] ?? '';
$ajo = db_one("SELECT * FROM ajos WHERE approval_code = '" . esc($code) . "' LIMIT 1");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajo Status - SuperAjo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
</head>
<body>
<div class="container" style="max-width:600px; padding-top:40px;">
    <div class="card">
        <?php if (!$ajo): ?>
            <h2 class="card-title">Invalid Tracking Code</h2>
            <p class="card-sub">We couldn't find an Ajo with that code.</p>
            <a href="index.php" class="btn btn-primary mt-16">Go Home</a>
        <?php else: ?>
            <h2 class="card-title"><?= h($ajo['name']) ?></h2>
            <p class="card-sub">Tracking Code: <b><?= h($ajo['approval_code']) ?></b></p>
            
            <?php if ($ajo['status'] === 'pending'): ?>
                <div class="alert alert-info mt-16">
                    <b>Status: PENDING APPROVAL</b><br><br>
                    To activate your Ajo, please make a payment of <b><?= money(get_setting('subscription_price')) ?></b> to:
                    <ul style="margin-top:10px; margin-bottom:10px;">
                        <li><b>Bank:</b> <?= h(get_setting('bank_name')) ?></li>
                        <li><b>Account No:</b> <?= h(get_setting('account_number')) ?></li>
                        <li><b>Account Name:</b> <?= h(get_setting('account_name')) ?></li>
                    </ul>
                    After payment, click the WhatsApp button below to send proof to the Super Admin.
                </div>
                
                <?php 
                    $wa_text = urlencode("Hello Admin, I want to activate my SuperAjo subscription. My Tracking Code is: " . $ajo['approval_code'] . ". I have made the payment.");
                    $wa_link = "https://wa.me/" . get_setting('whatsapp_number') . "?text=" . $wa_text;
                ?>
                <a href="<?= $wa_link ?>" target="_blank" class="btn btn-wa btn-block mt-16">Send Payment Proof on WhatsApp</a>
                
            <?php elseif ($ajo['status'] === 'active'): ?>
                <div class="alert alert-success mt-16">
                    <b>Status: ACTIVE</b><br>
                    Your Ajo is live and ready to use!
                </div>
                <p><b>Admin Dashboard Link:</b><br><a href="<?= BASE_URL ?>admin.php?token=<?= h($ajo['admin_token']) ?>">Click here to manage Ajo</a></p>
                <p><b>Public Member Link:</b><br><a href="<?= BASE_URL ?>ajo.php?slug=<?= h($ajo['slug']) ?>">Click here to share with members</a></p>
            <?php else: ?>
                <div class="alert alert-error mt-16">
                    <b>Status: SUSPENDED</b><br>
                    Your subscription has expired. Please contact support.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
