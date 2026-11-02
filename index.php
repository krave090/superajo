<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>SuperAjo - WhatsApp Ajo without fight, without loss</title>
<?php pwa_head(); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
<style>
  .lined-textarea {
    background-color: #fff;
    background-image: repeating-linear-gradient(transparent, transparent 31px, #e2e8f0 31px, #e2e8f0 32px);
    line-height: 32px; background-attachment: local; padding: 12px 16px;
    min-height: 160px; font-size: 15px; border: 1px solid var(--line); border-radius: 11px;
  }
  .lined-textarea:focus { border-color: var(--green); outline:none; box-shadow: 0 0 0 3px rgba(15,157,88,.14); }
</style>
</head>
<body>
<header class="sa-nav"><div class="container nav-inner">
  <a class="brand" href="<?= BASE_URL ?>index.php"><span class="brand-dot"></span> SuperAjo</a>
  <span class="nav-tag">Proof Vault &bull; Defaulter Protection &bull; Payout Tracker</span>
</div></header>
<section class="hero"><div class="container">
  <h1>WhatsApp Ajo without fight,<br><span class="hl">without loss.</span></h1>
  <p class="lead">SuperAjo gives you a public proof vault, an automatic defaulter list, and a payout tracker - all from one private admin link.</p>
</div></section>

<div class="container">
  <div class="grid-2">
    <div class="card mt-16" id="create">
      <div class="card-head">
        <h2 class="card-title">Create Ajo Vault</h2>
        <p class="card-sub">Requires approval. You will get a tracking code to check your status.</p>
      </div>
      <form action="<?= BASE_URL ?>create.php" method="post" autocomplete="off">
        <div class="grid-2">
          <div class="field"><label class="label" for="ajo_name">Ajo Name</label><input class="input" type="text" id="ajo_name" name="ajo_name" required></div>
          <div class="field"><label class="label" for="amount">Contribution (N)</label><input class="input" type="number" id="amount" name="amount" min="100" required></div>
        </div>
        <div class="grid-2">
          <div class="field"><label class="label" for="admin_email">Your Email</label><input class="input" type="email" id="admin_email" name="admin_email" required></div>
          <div class="field"><label class="label" for="admin_phone">WhatsApp Number</label><input class="input" type="text" id="admin_phone" name="admin_phone" required></div>
        </div>
        <div class="grid-2">
          <div class="field"><label class="label" for="frequency">Frequency</label>
            <select class="select" id="frequency" name="frequency"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly" selected>Monthly</option></select>
          </div>
          <div class="field"><label class="label" for="start_date">Start Date</label><input class="input" type="date" id="start_date" name="start_date" required></div>
        </div>
        <div class="field">
          <label class="label" for="members">Member Names - one per line</label>
          <textarea class="lined-textarea" id="members" name="members" required placeholder="Mama Nkechi&#10;Chinedu&#10;Ada"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block">Submit Ajo Request &rarr;</button>
      </form>
    </div>

    <div class="card mt-16" style="height: fit-content;">
      <div class="card-head">
        <h2 class="card-title">Check Your Ajo Status</h2>
        <p class="card-sub">Enter your tracking code to check if your Ajo is approved.</p>
      </div>
      <form action="<?= BASE_URL ?>status.php" method="GET" class="row">
        <input class="input" type="text" name="code" placeholder="e.g. SUPER-X7B9" required style="flex:1;">
        <button type="submit" class="btn btn-primary">Check</button>
      </form>
    </div>
  </div>
  <div class="footer">SuperAjo &bull; Built for Nigerian Ajo / Thrift / Esusu admins</div>
</div>
<?php pwa_footer(); ?>
<script>
  var d = document.getElementById('start_date');
  if(d && !d.value){ var t = new Date(); d.value = t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0'); }
</script>
</body>
</html>
