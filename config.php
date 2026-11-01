<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDir = rtrim($scriptDir, '/');
define('BASE_URL', ($scriptDir === '' ? '' : $scriptDir) . '/');
define('CURRENT_PERIOD', 'Period 1');
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header('Location: ' . $url); exit; }
function money($n) { return 'N' . number_format((float)$n); }
function rand_str($len = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789'; $max = strlen($chars) - 1; $out = '';
    for ($i = 0; $i < $len; $i++) { try { $out .= $chars[random_int(0,$max)]; } catch (Exception $e) { $out .= $chars[mt_rand(0,$max)]; } }
    return $out;
}
function ensure_upload_dir() {
    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
    return is_dir(UPLOAD_DIR);
}
function sa_die($title, $message, $backUrl = null, $code = 400) {
    http_response_code($code); if ($backUrl === null) { $backUrl = BASE_URL . 'index.php'; }
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title><?= h($title) ?> - SuperAjo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css"></head><body>
    <div class="sa-nav"><div class="container nav-inner">
    <a class="brand" href="<?= BASE_URL ?>index.php"><span class="brand-dot"></span> SuperAjo</a>
    </div></div>
    <div class="container" style="max-width:620px;padding:56px 18px;"><div class="card">
    <h1 class="card-title" style="font-size:22px;"><?= h($title) ?></h1>
    <p class="card-sub"><?= h($message) ?></p>
    <p style="margin-top:18px;"><a class="btn btn-primary" href="<?= h($backUrl) ?>">Go back</a></p>
    </div></div></body></html><?php exit;
}
function alert_from($map) {
    $m = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
    if ($m !== '' && isset($map[$m])) {
        echo '<div class="alert alert-' . h($map[$m][0]) . '">' . h($map[$m][1]) . '</div>';
    }
}

// --- DYNAMIC DATABASE CONFIGURATION ---
// Uses ZevCloud environment variables if they exist, otherwise falls back to local XAMPP.
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'superajo_db';

$conn = @mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>SuperAjo</title>'
       . '<link rel="stylesheet" href="' . BASE_URL . 'assets/style.css"></head><body>'
       . '<div class="container" style="max-width:660px;padding:60px 18px;"><div class="card">'
       . '<h1 class="card-title">Database connection failed</h1>'
       . '<p class="card-sub">' . h(mysqli_connect_error()) . '</p>'
       . '<p class="card-sub">Open phpMyAdmin and import <b>database.sql</b>.</p>'
       . '</div></div></body></html>';
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');
function esc($v) { global $conn; return mysqli_real_escape_string($conn, (string)$v); }
function db_all($sql) { global $conn; $out = array(); $res = mysqli_query($conn, $sql);
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; } mysqli_free_result($res); } return $out; }
function db_one($sql) { global $conn; $res = mysqli_query($conn, $sql);
    if ($res) { $row = mysqli_fetch_assoc($res); mysqli_free_result($res); if ($row) { return $row; } } return null; }
function db_exec($sql) { global $conn; return mysqli_query($conn, $sql); }
function payment_map($ajo_id, $period) {
    $ajo_id = (int)$ajo_id;
    $rows = db_all("SELECT * FROM payments WHERE ajo_id = $ajo_id AND period_label = '" . esc($period) . "' ORDER BY id ASC");
    $rank = array('confirmed' => 3, 'pending' => 2, 'rejected' => 1); $map = array();
    foreach ($rows as $p) {
        $mid = (int)$p['member_id'];
        if (!isset($map[$mid])) { $map[$mid] = $p; continue; }
        $oR = isset($rank[$map[$mid]['status']]) ? $rank[$map[$mid]['status']] : 0;
        $nR = isset($rank[$p['status']]) ? $rank[$p['status']] : 0;
        if ($nR > $oR) { $map[$mid] = $p; }
    }
    return $map;
}
function member_status($map, $id) {
    $id = (int)$id;
    if (isset($map[$id])) {
        if ($map[$id]['status'] === 'confirmed') { return 'paid'; }
        if ($map[$id]['status'] === 'pending')   { return 'pending'; }
    }
    return 'unpaid';
}
function status_badge($st) {
    if ($st === 'paid')    { return '<span class="badge badge-green">PAID</span>'; }
    if ($st === 'pending') { return '<span class="badge badge-amber">PENDING</span>'; }
    return '<span class="badge badge-red">NOT PAID</span>';
}
function proof_url($path) {
    if (!$path) { return ''; }
    if (strpos($path, 'http') === 0) { return $path; }
    return BASE_URL . ltrim($path, '/');
}
function proof_exists($path) {
    if (!$path) { return false; }
    return file_exists(__DIR__ . '/' . ltrim($path, '/'));
}
function root_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hostName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $hostName . BASE_URL;
}
function pwa_head() { ?>
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <meta name="theme-color" content="#0b7d45">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SuperAjo">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/icon-192.png">
<?php }
function pwa_footer() { ?>
    <button id="saInstall" type="button" class="sa-install" style="display:none;">Install App</button>
    <div id="saToast"></div>
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('<?= BASE_URL ?>service-worker.js')
          .catch(function (e) { console.log('SW failed:', e); });
      });
    }
    var deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault(); deferredPrompt = e;
      var b = document.getElementById('saInstall'); if (b) b.style.display = 'inline-flex';
    });
    window.addEventListener('appinstalled', function () {
      var b = document.getElementById('saInstall'); if (b) b.style.display = 'none';
    });
    (function () {
      var b = document.getElementById('saInstall'); if (!b) return;
      b.addEventListener('click', function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function () { deferredPrompt = null; b.style.display = 'none'; });
      });
    })();
    function toast(m) {
      var e = document.getElementById('saToast'); if (!e) return;
      e.textContent = m; e.classList.add('show');
      clearTimeout(window.__saToastT);
      window.__saToastT = setTimeout(function () { e.classList.remove('show'); }, 2000);
    }
    function copyInput(id, btn) {
      var el = document.getElementById(id); if (!el) return;
      var text = el.value !== undefined ? el.value : el.textContent;
      function done() { toast('Copied');
        if (btn) { var o = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = o; }, 1500); } }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () { legacyCopy(text, done); });
      } else { legacyCopy(text, done); }
    }
    function legacyCopy(text, cb) {
      var t = document.createElement('textarea');
      t.value = text; t.style.position = 'fixed'; t.style.top = '-1000px';
      document.body.appendChild(t); t.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(t); if (cb) cb();
    }
    </script>
<?php }