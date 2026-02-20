<? 
set_error_handler(function(int $errno, string $errstr) {
  if ((strpos($errstr, 'Undefined array key') === false) && (strpos($errstr, 'Undefined variable') === false)) {
      return false;
  } else {
      return true;
  }
}, E_WARNING);
if ($_POST){
  require_once 'handler.php';
} else  {
  require_once 'calls.php';
  $trans = getSchedule();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Checkbook – Schedule</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="theme-color" content="#1a2e1a">
  <link href="icon.png" rel="icon" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="cb.css">
</head>
<body>
<div class="app-shell">

  <header class="app-header">
    <div class="header-inner">
      <div class="header-brand">
        <img src="icon.png" alt="Checkbook" class="brand-icon">
        <span class="brand-name">Scheduled Payments</span>
      </div>
    </div>
  </header>

  <nav class="bottom-nav">
    <button class="nav-btn" onclick="window.location = '/'">
      <span class="nav-icon">🏠</span>
      <span class="nav-label">Home</span>
    </button>
    <button class="nav-btn active" onclick="newSched()">
      <span class="nav-icon">➕</span>
      <span class="nav-label">Add</span>
    </button>
  </nav>

  <main class="main-content">
    <div class="trans-list">
      <?php
      if (is_array($trans)) {
        foreach ($trans as $line) {
          $isNeg = $line['amount'] < 0;
          $amtClass = $isNeg ? 'amt-neg' : 'amt-pos';
          $amtStr = ($isNeg ? '-' : '+') . '$' . number_format(abs($line['amount']), 2, '.', ',');
          $active = $line['active'] == 1;
          $nextRun = date('M j', strtotime($line['nextrun']));
          $lastRun = $line['lastrun'] ? date('M j, Y', strtotime($line['lastrun'])) : 'Never';
          echo '<div class="sched-row" onclick="editSched(' . $line['id'] . ')">';
          echo '  <div class="sched-left">';
          echo '    <span class="sched-desc">' . htmlspecialchars($line['description']) . '</span>';
          echo '    <span class="sched-meta">Day ' . $line['dayofmonth'] . ' · Next: ' . $nextRun . ' · Last: ' . $lastRun . '</span>';
          echo '  </div>';
          echo '  <div class="sched-right">';
          echo '    <span class="trans-amt ' . $amtClass . '">' . $amtStr . '</span>';
          echo '    <span class="status-badge ' . ($active ? 'status-active' : 'status-inactive') . '">' . ($active ? 'Active' : 'Off') . '</span>';
          echo '  </div>';
          echo '</div>';
        }
      } else {
        echo '<div class="empty-state"><p>No scheduled payments yet.</p></div>';
      }
      ?>
    </div>
  </main>

  <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

  <!-- Add/Edit Schedule Drawer -->
  <div class="drawer" id="transDrawer">
    <div class="drawer-handle" onclick="closeDrawer()"></div>
    <div class="drawer-header">
      <h2 class="drawer-title" id="transTitle">Add Schedule</h2>
      <button class="drawer-close" onclick="closeDrawer()">✕</button>
    </div>
    <div class="drawer-body">
      <form method="POST" id="transForm">
        <div class="field-group">
          <label class="field-label">Day of Month</label>
          <select name="dayofmonth" id="date" class="field-input">
            <?php for ($x = 1; $x < 29; $x++) echo "<option value=\"$x\">$x</option>"; ?>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label">Amount</label>
          <div class="amount-input-wrap">
            <span class="amount-prefix">$</span>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01" class="field-input amount-input" onchange="$(this).val(Math.abs($(this).val()))" required placeholder="0.00">
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Description</label>
          <input type="text" id="description_sched" name="description" class="field-input" required placeholder="e.g. Netflix, Rent...">
        </div>
        <div class="field-group">
          <label class="field-label">Status</label>
          <div class="toggle-group">
            <button type="button" class="toggle-btn active" id="btn_active1" onclick="setActive(1)">Active</button>
            <button type="button" class="toggle-btn" id="btn_active0" onclick="setActive(0)">Inactive</button>
          </div>
          <input type="hidden" id="active" name="active" value="1">
        </div>
        <input type="hidden" id="trans_id" name="trans_id">
        <input type="hidden" id="type" name="type">
        <div class="drawer-actions">
          <button type="submit" id="submitBtn" class="btn-primary">Add Schedule</button>
          <button type="button" id="deleteBtn" class="btn-danger" style="display:none" onclick="confirmDeleteSched()">Delete</button>
        </div>
      </form>
    </div>
  </div>

</div>

<script src="cb.js"></script>
</body>
</html>
