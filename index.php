<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="SuperAjo - proof vault, defaulter protection and payout tracker for WhatsApp Ajo admins in Nigeria.">
<title>SuperAjo - WhatsApp Ajo without fight, without loss</title>
<?php pwa_head(); ?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
<style>
  /* Lined notebook style for the textarea */
  .lined-textarea {
    background-color: #fff;
    background-image: repeating-linear-gradient(
      transparent,
      transparent 31px,
      #e2e8f0 31px,
      #e2e8f0 32px
    );
    line-height: 32px;
    background-attachment: local;
    padding-top: 12px;
    padding-bottom: 12px;
    padding-left: 16px;
    padding-right: 16px;
    min-height: 180px;
    font-size: 15px;
    border: 1px solid var(--line);
    border-radius: 11px;
    resize: vertical;
    width: 100%;
    outline: none;
    font-family: inherit;
    color: var(--ink);
    transition: border-color .15s, box-shadow .15s;
    cursor: text;
  }
  .lined-textarea:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(15,157,88,.14);
  }
  
  /* Live Preview Styling */
  .preview-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    display: none;
    max-height: 280px;
    overflow-y: auto;
  }
  .preview-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
  }
  .preview-item:last-child { border-bottom: none; }
  .preview-num {
    background: var(--green-050);
    color: var(--green-700);
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
  }
  
  /* Add Next Button */
  .btn-add-next {
    background: var(--green-050);
    color: var(--green-700);
    border: 1px solid #bfe8d3;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background .15s;
  }
  .btn-add-next:hover {
    background: #d8f2e5;
  }
</style>
</head>
<body>
<header class="sa-nav"><div class="container nav-inner">
  <a class="brand" href="<?= BASE_URL ?>index.php"><span class="brand-dot"></span> SuperAjo</a>
  <span class="nav-tag">Proof Vault &bull; Defaulter Protection &bull; Payout Tracker</span>
</div></header>
<section class="hero"><div class="container">
  <h1>WhatsApp Ajo without fight,<br><span class="hl">without loss.</span></h1>
  <p class="lead">Members claim <b>"I have paid"</b>. Some send <b>fake screenshots</b>. You end up covering <b>N20,000 - N50,000</b> from your own pocket. SuperAjo gives you a public proof vault, an automatic defaulter list, and a payout tracker - all from one private admin link.</p>
  <div class="pill-row">
    <span class="pill">&#128274; Private admin link</span>
    <span class="pill">&#128248; Screenshot proof vault</span>
    <span class="pill">&#128680; One-tap defaulter list</span>
    <span class="pill">&#128184; Payout order tracker</span>
  </div>
</div></section>

<div class="container">
  <div class="steps">
    <div class="step"><div class="step-num">1</div><h3>Create your Ajo Vault</h3><p>Enter the Ajo name, contribution amount, frequency and paste your member names - one per line.</p></div>
    <div class="step"><div class="step-num">2</div><h3>Share the public link</h3><p>Drop the public link in your WhatsApp group. Members upload their payment proof themselves.</p></div>
    <div class="step"><div class="step-num">3</div><h3>Confirm &amp; track</h3><p>You confirm or reject proofs from your private dashboard. Defaulters and payouts update automatically.</p></div>
  </div>

  <div class="card mt-24" id="create">
    <div class="card-head">
      <h2 class="card-title">Create Ajo Vault</h2>
      <p class="card-sub">Takes 30 seconds. No signup, no password - your secret admin link is your key.</p>
    </div>
    <form action="<?= BASE_URL ?>create.php" method="post" autocomplete="off">
      <div class="grid-2">
        <div class="field">
          <label class="label" for="ajo_name">Ajo Name</label>
          <input class="input" type="text" id="ajo_name" name="ajo_name" placeholder="e.g. Dec 20k Ajo" maxlength="255" required>
        </div>
        <div class="field">
          <label class="label" for="amount">Contribution Amount (N)</label>
          <input class="input" type="number" id="amount" name="amount" placeholder="20000" min="100" step="100" required>
        </div>
      </div>
      <div class="grid-2">
        <div class="field">
          <label class="label" for="total_members">Total Members</label>
          <input class="input" type="number" id="total_members" name="total_members" value="0" min="2" max="100" readonly style="background:#f8fafc; font-weight:bold;">
          <span class="hint" id="nameCount" style="color:var(--red); font-weight:600;">0 names detected. Minimum 2 required.</span>
        </div>
        <div class="field">
          <label class="label" for="frequency">Frequency</label>
          <select class="select" id="frequency" name="frequency" required>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly" selected>Monthly</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="label" for="start_date">Start Date</label>
        <input class="input" type="date" id="start_date" name="start_date" required>
      </div>
      
      <div class="field">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:8px;">
          <label class="label" for="members" style="margin:0;">Member Names - one per line</label>
          <button type="button" class="btn-add-next" id="addNextBtn">➕ Add Next Member</button>
        </div>
        <textarea class="lined-textarea" id="members" name="members" required placeholder="Mama Nkechi&#10;Chinedu&#10;Ada&#10;Mama Chinedu&#10;Emeka"></textarea>
        <span class="hint">Click anywhere on the lines below to type the next person. Order = Payout Order.</span>
      </div>

      <!-- Live Preview Section -->
      <div id="previewBox" class="preview-box">
        <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:8px;">Payout Order Preview</div>
        <div id="previewList"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg btn-block">Create Ajo Vault &rarr;</button>
    </form>
  </div>

  <div class="footer">SuperAjo &bull; Built for Nigerian Ajo / Thrift / Esusu admins</div>
</div>
<?php pwa_footer(); ?>
<script>
(function(){
  var ta = document.getElementById('members');
  var total = document.getElementById('total_members');
  var info = document.getElementById('nameCount');
  var previewBox = document.getElementById('previewBox');
  var previewList = document.getElementById('previewList');
  var addNextBtn = document.getElementById('addNextBtn');

  function sync(){
    // Split textarea by new lines, trim spaces, remove empty lines
    var lines = ta.value.split(/\r?\n/).map(function(s){ return s.trim(); }).filter(Boolean);
    total.value = lines.length;

    // Update the text hint
    if (lines.length < 2) {
      info.textContent = lines.length + ' name detected. Minimum 2 required.';
      info.style.color = 'var(--red)';
    } else {
      info.textContent = lines.length + ' names detected - it syncs automatically.';
      info.style.color = 'var(--green-600)';
    }

    // Build the live preview list
    if (lines.length === 0) {
      previewBox.style.display = 'none';
    } else {
      previewBox.style.display = 'block';
      var html = '';
      lines.forEach(function(name, index) {
        // Escape HTML so names don't break the page
        var safeName = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        html += '<div class="preview-item">'
             + '<div class="preview-num">' + (index + 1) + '</div>'
             + '<div style="font-weight:600; font-size:14.5px; color:#0f172a;">' + safeName + '</div>'
             + '<div style="margin-left:auto; font-size:12px; font-weight:700; color:#64748b;">Payout #' + (index + 1) + '</div>'
             + '</div>';
      });
      previewList.innerHTML = html;
    }
  }

  // 1. Listen for typing
  ta.addEventListener('input', sync);

  // 2. The "Smart Click" feature you asked for
  ta.addEventListener('click', function(e) {
    var lines = ta.value.split('\n');
    var lastLineIndex = lines.length - 1;
    
    // Calculate which visual line was clicked
    var rect = ta.getBoundingClientRect();
    var clickY = e.clientY - rect.top + ta.scrollTop;
    var lineHeight = 32; 
    var paddingTop = 12; 
    var clickedLine = Math.floor((clickY - paddingTop) / lineHeight);
    
    // If they clicked on an empty line below the text, jump the cursor there!
    if (clickedLine > lastLineIndex) {
      var needed = clickedLine - lastLineIndex;
      if (!ta.value.endsWith('\n') && ta.value.length > 0) {
        ta.value += '\n';
        needed--;
      }
      for(var i=0; i<needed; i++){
        ta.value += '\n';
      }
      
      // Move cursor to the end and trigger update
      ta.focus();
      ta.selectionStart = ta.selectionEnd = ta.value.length;
      ta.dispatchEvent(new Event('input'));
    }
  });

  // 3. The "Add Next Member" Button
  addNextBtn.addEventListener('click', function() {
    if (ta.value.length > 0 && !ta.value.endsWith('\n')) {
      ta.value += '\n';
    } else if (ta.value.length === 0) {
      // Do nothing, just focus
    } else {
      ta.value += '\n';
    }
    ta.focus();
    ta.scrollTop = ta.scrollHeight;
    ta.selectionStart = ta.selectionEnd = ta.value.length;
    ta.dispatchEvent(new Event('input'));
  });

  // Initialize
  sync();

  // Auto set today's date
  var d = document.getElementById('start_date');
  if(d && !d.value){
    var t = new Date();
    d.value = t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
  }
})();
</script>
</body>
</html>