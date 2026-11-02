<?php
require __DIR__ . '/config.php';
$token = '';
if (isset($_GET['token'])) { $token = trim($_GET['token']); }
elseif (isset($_POST['token'])) { $token = trim($_POST['token']); }
if ($token === '') { redirect(BASE_URL . 'index.php'); }

$ajo = db_one("SELECT * FROM ajos WHERE admin_token = '" . esc($token) . "' LIMIT 1");
if (!$ajo) { sa_die('Private dashboard not found', 'This admin link is wrong. Check the link your Ajo was created with.', BASE_URL . 'index.php', 404); }

// --- SUBSCRIPTION GATE ---
if ($ajo['status'] === 'pending') {
    sa_die('Pending Approval', 'This Ajo has not yet been approved by the platform admin. Please check your tracking code or contact support.', BASE_URL . 'status.php?code=' . urlencode($ajo['approval_code']));
}
if ($ajo['status'] === 'suspended') {
    sa_die('Ajo Suspended', 'This Ajo subscription has expired. Please contact the platform admin to renew.', BASE_URL . 'index.php');
}
if ($ajo['status'] === 'active' && !empty($ajo['expires_at']) && strtotime($ajo['expires_at']) < time()) {
    db_exec("UPDATE ajos SET status = 'suspended' WHERE id = " . (int)$ajo['id']);
    sa_die('Subscription Expired', 'Your 30-day subscription has expired. Please contact the platform admin to renew.', BASE_URL . 'index.php');
}
// --------------------------

$ajoId   = (int)$ajo['id'];
$members = db_all("SELECT * FROM members WHERE ajo_id = $ajoId ORDER BY payout_position ASC, id ASC");
$map     = payment_map($ajoId, CURRENT_PERIOD);

$totalMembers = count($members);
$paidCount = 0; $unpaidCount = 0; $received = 0;
foreach ($members as $m) {
    if (member_status($map, $m['id']) === 'paid') { $paidCount++; } else { $unpaidCount++; }
}
foreach ($map as $p) { if ($p['status'] === 'confirmed') { $received += (int)$p['amount']; } }
$expected = $totalMembers * (int)$ajo['amount'];
$percent  = $totalMembers > 0 ? round(($paidCount / $totalMembers) * 100) : 0;

$pending = db_all("SELECT p.*, m.name AS member_name FROM payments p JOIN members m ON m.id = p.member_id WHERE p.ajo_id = $ajoId AND p.period_label = '" . esc(CURRENT_PERIOD) . "' AND p.status = 'pending' ORDER BY p.id DESC");
$nextCollector = null;
foreach ($members as $m) { if ((int)$m['payout_received'] === 0) { $nextCollector = $m; break; } }

$publicLink = root_url() . 'ajo.php?slug=' . urlencode($ajo['slug']);
$adminLink = root_url() . 'admin.php?token=' . urlencode($ajo['admin_token']);
$shareText = "Hello! Here is our Ajo \"" . $ajo['name'] . "\" (" . money($ajo['amount']) . " per " . $ajo['frequency'] . ").\n\nUpload your payment proof here: " . $publicLink;

$defaulterParts = array();
foreach ($members as $m) { if (member_status($map, $m['id']) !== 'paid') { $defaulterParts[] = $m['name'] . ' - ' . money($ajo['amount']); } }
$defaulterText = 'Defaulters ' . CURRENT_PERIOD . ': ' . (count($defaulterParts) ? implode(', ', $defaulterParts) : 'None - everybody has paid');

$msgs = array(
    'created' => array('success', 'Ajo created! Copy the public link below and share it in your WhatsApp group.'),
    'pay_confirmed' => array('success', 'Payment confirmed. Member is now marked PAID.'),
    'pay_rejected' => array('error', 'Payment rejected. Member will show as NOT PAID.'),
    'payout_updated' => array('success', 'Payout status updated.'),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= h($ajo['name']) ?> - Admin Dashboard</title>
<?php pwa_head(); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
</head>
<body>
<header class="sa-nav"><div class="container nav-inner">
  <a class="brand" href="<?= BASE_URL ?>index.php"><span class="brand-dot"></span> SuperAjo</a>
  <span class="nav-tag">Private admin dashboard</span>
</div></header>

<div class="container" style="padding-top:24px;">
  <?php alert_from($msgs); ?>

  <div class="card">
    <div class="row" style="justify-content:space-between;align-items:flex-start;">
      <div>
        <h1 class="card-title" style="font-size:24px;"><?= h($ajo['name']) ?></h1>
        <p class="card-sub"><?= money($ajo['amount']) ?> &bull; <?= h(ucfirst($ajo['frequency'])) ?> &bull; <?= $totalMembers ?> members &bull; <?= h(CURRENT_PERIOD) ?></p>
      </div>
      <span class="badge badge-gray"><?= h(date('d M Y', strtotime($ajo['start_date']))) ?></span>
    </div>
    <div class="mt-16">
      <div class="linkbox">
        <div class="linkbox-label">Public member link - share in WhatsApp</div>
        <div class="linkbox-row">
          <input class="input mono" id="publicLink" type="text" value="<?= h($publicLink) ?>" readonly onclick="this.select()">
          <button type="button" class="btn btn-ghost btn-sm" onclick="copyInput('publicLink', this)">Copy</button>
        </div>
      </div>
      <div class="linkbox">
        <div class="linkbox-label">Private admin link - keep this secret</div>
        <div class="linkbox-row">
          <input class="input mono" id="adminLink" type="text" value="<?= h($adminLink) ?>" readonly onclick="this.select()">
          <button type="button" class="btn btn-ghost btn-sm" onclick="copyInput('adminLink', this)">Copy</button>
        </div>
      </div>
      <div class="row">
        <a class="btn btn-wa btn-sm" href="https://wa.me/?text=<?= rawurlencode($shareText) ?>" target="_blank" rel="noopener">Share on WhatsApp</a>
        <a class="btn btn-ghost btn-sm" href="<?= h($publicLink) ?>" target="_blank" rel="noopener">Preview public page</a>
      </div>
    </div>
  </div>

  <div class="stats mt-16">
    <div class="stat"><div class="stat-num"><?= $totalMembers ?></div><div class="stat-label">Members</div></div>
    <div class="stat"><div class="stat-num green"><?= $paidCount ?></div><div class="stat-label">Paid</div></div>
    <div class="stat"><div class="stat-num red"><?= $unpaidCount ?></div><div class="stat-label">Unpaid</div></div>
    <div class="stat"><div class="stat-num"><?= money($expected) ?></div><div class="stat-label">Expected</div></div>
    <div class="stat"><div class="stat-num green"><?= money($received) ?></div><div class="stat-label">Received</div></div>
  </div>

  <div class="card mt-16">
    <div class="row" style="justify-content:space-between;margin-bottom:8px;">
      <span class="badge badge-green"><?= $paidCount ?>/<?= $totalMembers ?> PAID</span>
      <span class="member-meta"><?= $percent ?>% collected</span>
    </div>
    <div class="progress"><span style="width:<?= $percent ?>%"></span></div>
  </div>

  <div class="tabs">
    <button type="button" class="tab-btn active" data-tab="tab1">Payments to Confirm (<?= count($pending) ?>)</button>
    <button type="button" class="tab-btn" data-tab="tab2">Members &amp; Defaulters</button>
    <button type="button" class="tab-btn" data-tab="tab3">Payout Order</button>
  </div>

  <div id="tab1" class="tab-panel active">
    <div class="card">
      <div class="card-head"><h2 class="card-title">Proofs Waiting For You</h2><p class="card-sub">Click the image to view the full screenshot, then confirm or reject.</p></div>
      <?php if (count($pending) === 0): ?>
        <div class="empty"><div class="big">&#9989;</div>No pending proofs. All caught up!</div>
      <?php else: ?>
        <?php foreach ($pending as $p): ?>
          <div class="member">
            <div class="member-left">
              <?php if ($p['proof_image'] && proof_exists($p['proof_image'])): ?>
                <a href="<?= h(proof_url($p['proof_image'])) ?>" target="_blank" rel="noopener"><img class="thumb-lg" src="<?= h(proof_url($p['proof_image'])) ?>" alt="proof" loading="lazy"></a>
              <?php else: ?><div class="avatar">?</div><?php endif; ?>
              <div>
                <div class="member-name"><?= h($p['member_name']) ?></div>
                <div class="member-meta"><?= money($p['amount']) ?><?php if (!empty($p['transaction_id'])): ?> &bull; Txn: <?= h($p['transaction_id']) ?><?php endif; ?> &bull; <?= h(date('d M Y, g:ia', strtotime($p['created_at']))) ?></div>
              </div>
            </div>
            <div class="member-right">
              <form method="post" action="<?= BASE_URL ?>payout.php" style="display:inline;">
                <input type="hidden" name="token" value="<?= h($ajo['admin_token']) ?>">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm">Confirm</button>
              </form>
              <form method="post" action="<?= BASE_URL ?>payout.php" style="display:inline;">
                <input type="hidden" name="token" value="<?= h($ajo['admin_token']) ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab2" class="tab-panel">
    <div class="card">
      <div class="card-head"><h2 class="card-title">Members &amp; Defaulter List</h2><p class="card-sub">Mark who has collected their payout, and export the defaulter list.</p></div>
      <?php if ($totalMembers === 0): ?>
        <div class="empty"><div class="big">&#128101;</div>No members yet.</div>
      <?php else: ?>
        <?php foreach ($members as $m): $st = member_status($map, $m['id']); ?>
          <div class="member">
            <div class="member-left">
              <div class="avatar"><?= h(strtoupper(mb_substr($m['name'], 0, 1))) ?></div>
              <div><div class="member-name"><?= h($m['name']) ?></div><div class="member-meta">Position #<?= (int)$m['payout_position'] ?></div></div>
            </div>
            <div class="member-right">
              <?= status_badge($st) ?>
              <?php if ((int)$m['payout_received'] === 1): ?><span class="badge badge-gray">Payout collected</span><?php endif; ?>
              <form method="post" action="<?= BASE_URL ?>payout.php" style="display:inline;">
                <input type="hidden" name="token" value="<?= h($ajo['admin_token']) ?>">
                <input type="hidden" name="action" value="toggle_payout">
                <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= (int)$m['payout_received'] === 1 ? 'Undo payout' : 'Mark payout received' ?></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <div class="field" style="margin-top:18px;">
        <label class="label" for="defaulterBox">Defaulter list - ready for WhatsApp</label>
        <textarea class="textarea mono" id="defaulterBox" readonly><?= h($defaulterText) ?></textarea>
      </div>
      <button type="button" class="btn btn-primary" onclick="copyInput('defaulterBox', this)">Copy defaulter list</button>
    </div>
  </div>

  <div id="tab3" class="tab-panel">
    <div class="card">
      <div class="card-head">
        <h2 class="card-title">Payout Order</h2>
        <p class="card-sub"><?php if ($nextCollector): ?>Next person to collect: <b><?= h($nextCollector['name']) ?></b> (position #<?= (int)$nextCollector['payout_position'] ?>)<?php else: ?>Everyone has collected their payout<?php endif; ?></p>
      </div>
      <?php if ($totalMembers === 0): ?>
        <div class="empty"><div class="big">&#128184;</div>No members yet.</div>
      <?php else: ?>
        <?php foreach ($members as $m):
          $collected = ((int)$m['payout_received'] === 1);
          $isNext = ($nextCollector && (int)$nextCollector['id'] === (int)$m['id']); ?>
          <div class="member" style="<?= $isNext ? 'border-color:#0f9d58;background:#f3fdf8;' : '' ?>">
            <div class="member-left">
              <div class="avatar" style="<?= $collected ? 'background:#f1f5f9;color:#64748b;' : '' ?>"><?= (int)$m['payout_position'] ?></div>
              <div>
                <div class="member-name"><?= h($m['name']) ?></div>
                <div class="member-meta"><?= member_status($map, $m['id']) === 'paid' ? 'Paid this period' : 'Has not paid this period' ?><?php if ($isNext): ?> &bull; <b style="color:#0b7d45;">Next to collect</b><?php endif; ?></div>
              </div>
            </div>
            <div class="member-right">
              <?php if ($collected): ?><span class="badge badge-green">COLLECTED</span><?php else: ?><span class="badge badge-gray">WAITING</span><?php endif; ?>
              <form method="post" action="<?= BASE_URL ?>payout.php" style="display:inline;">
                <input type="hidden" name="token" value="<?= h($ajo['admin_token']) ?>">
                <input type="hidden" name="action" value="toggle_payout">
                <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                <button type="submit" class="btn <?= $collected ? 'btn-ghost' : 'btn-success' ?> btn-sm"><?= $collected ? 'Undo' : 'Mark collected' ?></button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer">SuperAjo &bull; Bookmark this admin link - it is the only key to this dashboard.</div>
</div>
<?php pwa_footer(); ?>
<script>
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active');});
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('active');});
    btn.classList.add('active');
    var t=document.getElementById(btn.getAttribute('data-tab'));
    if(t)t.classList.add('active');
  });
});
</script>
</body>
</html>
