<?php
require __DIR__ . '/config.php';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') { redirect(BASE_URL . 'index.php'); }
$ajo = db_one("SELECT * FROM ajos WHERE slug = '" . esc($slug) . "' LIMIT 1");
if (!$ajo) { sa_die('Ajo not found', 'This Ajo link is wrong or the Ajo was deleted. Ask your admin for the correct link.', BASE_URL . 'index.php', 404); }
$ajoId = (int)$ajo['id'];
$members = db_all("SELECT * FROM members WHERE ajo_id = $ajoId ORDER BY payout_position ASC, id ASC");
$map = payment_map($ajoId, CURRENT_PERIOD);
$totalMembers = count($members); $paidCount = 0; $pendingCount = 0;
foreach ($members as $m) { $st = member_status($map, $m['id']); if ($st === 'paid') { $paidCount++; } if ($st === 'pending') { $pendingCount++; } }
$percent = $totalMembers > 0 ? round(($paidCount / $totalMembers) * 100) : 0;
$msgs = array(
    'uploaded' => array('success', 'Proof submitted! Your admin will confirm it shortly.'),
    'already' => array('info', 'You already have a confirmed payment for this period.'),
    'err_member' => array('error', 'Please select your name from the list.'),
    'err_file' => array('error', 'Please attach your payment screenshot.'),
    'err_type' => array('error', 'Only JPG, JPEG and PNG images are allowed.'),
    'err_size' => array('error', 'That image is too large. Maximum size is 5MB.'),
    'err_save' => array('error', 'We could not save your upload. Please try again.'),
    'err_db' => array('error', 'Something went wrong saving your payment. Please try again.'),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($ajo['name']) ?> - SuperAjo</title>
<?php pwa_head(); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
</head>
<body>
<header class="sa-nav"><div class="container nav-inner">
  <a class="brand" href="<?= BASE_URL ?>index.php"><span class="brand-dot"></span> SuperAjo</a>
  <span class="nav-tag">Member payment page</span>
</div></header>
<div class="container" style="padding-top:26px;max-width:820px;">
  <?php alert_from($msgs); ?>
  <div class="card">
    <div class="card-head" style="margin-bottom:14px;">
      <h1 class="card-title" style="font-size:24px;"><?= h($ajo['name']) ?></h1>
      <p class="card-sub"><?= money($ajo['amount']) ?> &bull; <?= h(ucfirst($ajo['frequency'])) ?> &bull; <?= $totalMembers ?> members &bull; Started <?= h(date('d M Y', strtotime($ajo['start_date']))) ?></p>
    </div>
    <div class="row" style="justify-content:space-between;margin-bottom:8px;">
      <span class="badge badge-green"><?= $paidCount ?>/<?= $totalMembers ?> PAID</span>
      <?php if ($pendingCount > 0): ?><span class="badge badge-amber"><?= $pendingCount ?> AWAITING CONFIRMATION</span><?php endif; ?>
      <span class="member-meta"><?= h(CURRENT_PERIOD) ?></span>
    </div>
    <div class="progress"><span style="width:<?= $percent ?>%"></span></div>
  </div>
  <div class="card mt-16">
    <div class="card-head"><h2 class="card-title">Payment Status</h2><p class="card-sub">Green = confirmed by admin. Amber = proof uploaded, waiting for admin.</p></div>
    <?php if ($totalMembers === 0): ?>
      <div class="empty"><div class="big">&#128101;</div>No members in this Ajo yet.</div>
    <?php else: ?>
      <?php foreach ($members as $m):
        $st = member_status($map, $m['id']);
        $p = isset($map[(int)$m['id']]) ? $map[(int)$m['id']] : null; ?>
        <div class="member">
          <div class="member-left">
            <div class="avatar"><?= h(strtoupper(mb_substr($m['name'], 0, 1))) ?></div>
            <div><div class="member-name"><?= h($m['name']) ?></div><div class="member-meta">Payout position #<?= (int)$m['payout_position'] ?></div></div>
          </div>
          <div class="member-right">
            <?= status_badge($st) ?>
            <?php if ($p && $p['proof_image'] && proof_exists($p['proof_image'])): ?>
              <a href="<?= h(proof_url($p['proof_image'])) ?>" target="_blank" rel="noopener"><img class="thumb" src="<?= h(proof_url($p['proof_image'])) ?>" alt="proof" loading="lazy"></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="card mt-16">
    <div class="card-head"><h2 class="card-title">I Have Paid - Upload Proof</h2><p class="card-sub">Select your name, attach your transfer screenshot, and submit.</p></div>
    <form action="<?= BASE_URL ?>upload.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="slug" value="<?= h($ajo['slug']) ?>">
      <div class="field">
        <label class="label" for="member_id">Select Your Name</label>
        <select class="select" id="member_id" name="member_id" required>
          <option value="">- Choose your name -</option>
          <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="p_amount">Amount (N)</label>
        <input class="input" type="number" id="p_amount" name="amount" value="<?= (int)$ajo['amount'] ?>" min="1" step="1" required>
      </div>
      <div class="field">
        <label class="label" for="proof">Proof of Payment (screenshot)</label>
        <input class="input" type="file" id="proof" name="proof" accept="image/jpeg,image/jpg,image/png" required>
        <span class="hint">JPG, JPEG or PNG only. Maximum 5MB.</span>
        <img id="preview" src="" alt="" style="display:none;margin-top:10px;max-width:180px;border-radius:12px;border:1px solid #e2e8f0;">
      </div>
      <div class="field">
        <label class="label" for="transaction_id">Transaction ID (optional)</label>
        <input class="input" type="text" id="transaction_id" name="transaction_id" maxlength="100" placeholder="e.g. 1234567890">
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-block">Submit Payment Proof</button>
    </form>
  </div>
  <div class="footer">Powered by SuperAjo - proof vault for WhatsApp Ajo admins.</div>
</div>
<?php pwa_footer(); ?>
<script>
(function(){
  var input=document.getElementById('proof'),img=document.getElementById('preview');
  if(!input||!img)return;
  input.addEventListener('change',function(){
    var f=input.files&&input.files[0];
    if(!f){img.style.display='none';return;}
    if(!/^image\/(jpeg|jpg|png)$/i.test(f.type)){img.style.display='none';alert('Please choose a JPG, JPEG or PNG image.');input.value='';return;}
    var r=new FileReader();
    r.onload=function(e){img.src=e.target.result;img.style.display='block';};
    r.readAsDataURL(f);
  });
})();
</script>
</body>
</html>