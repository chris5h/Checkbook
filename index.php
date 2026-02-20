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
  $trans = get1kTrans();
  $schedule = getSchedule();
}
$balance = getBalance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Checkbook</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="theme-color" content="#141414">
  <link href="icon.png" rel="icon" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="cb.css">
  <script>
    $(function() {
      $("#description").autocomplete({
        source: 'handler.php',
        minLength: 2,
        classes: { "ui-autocomplete": "cb-autocomplete" }
      });
    });
  </script>
</head>
<body>
<div class="app-shell">

  <!-- Header: balance only, no brand -->
  <header class="app-header">
    <div class="header-inner">
      <span class="header-label">Balance</span>
      <div class="balance-display"><?= $balance ?></div>
    </div>
  </header>

  <!-- Bottom Nav -->
  <nav class="bottom-nav">
    <button class="nav-btn active" onclick="window.location = window.location['href']">
      <span class="nav-icon">🏠</span>
      <span class="nav-label">Home</span>
    </button>
    <button class="nav-btn" onclick="newTrans()">
      <span class="nav-icon">➕</span>
      <span class="nav-label">Add</span>
    </button>
    <button class="nav-btn" onclick="openSearch()">
      <span class="nav-icon">🔍</span>
      <span class="nav-label">Search</span>
    </button>
    <button class="nav-btn" onclick="openSchedList()">
      <span class="nav-icon">📅</span>
      <span class="nav-label">Schedule</span>
    </button>
  </nav>

  <!-- Main content -->
  <main class="main-content">
    <div class="trans-list">
      <?php
      if (is_array($trans)) {
        $lastDate = '';
        foreach ($trans as $line) {
          $dateFormatted = date('M j, Y', strtotime($line['trans_date']));
          if ($dateFormatted !== $lastDate) {
            if ($lastDate !== '') echo '</div>';
            echo '<div class="date-group">';
            echo '<div class="date-label">' . $dateFormatted . '</div>';
            $lastDate = $dateFormatted;
          }
          $isNeg = $line['amount'] < 0;
          $amtClass = $isNeg ? 'amt-neg' : 'amt-pos';
          $amtStr = ($isNeg ? '-' : '+') . '$' . number_format(abs($line['amount']), 2, '.', ',');
          $check = $line['checknumber'] ? '<span class="check-badge">#' . $line['checknumber'] . '</span>' : '';
          echo '<div class="trans-row" onclick="editTrans(' . $line['id'] . ')">';
          echo '  <div class="trans-main">';
          echo '    <span class="trans-desc">' . htmlspecialchars($line['description']) . '</span>';
          echo '    ' . $check;
          echo '  </div>';
          echo '  <div class="trans-amt ' . $amtClass . '">' . $amtStr . '</div>';
          echo '</div>';
        }
        if ($lastDate !== '') echo '</div>';
      } else {
        echo '<div class="empty-state"><p>No transactions found.</p></div>';
      }
      ?>
    </div>
  </main>

  <!-- Shared overlay -->
  <div class="drawer-overlay" id="drawerOverlay" onclick="closeAllDrawers()"></div>

  <!-- ── Transaction Drawer ── -->
  <div class="drawer" id="transDrawer">
    <div class="drawer-handle" onclick="closeAllDrawers()"></div>
    <div class="drawer-header">
      <h2 class="drawer-title" id="transTitle">Add Transaction</h2>
      <button class="drawer-close" onclick="closeAllDrawers()">✕</button>
    </div>
    <div class="drawer-body">
      <form method="POST" id="transForm">
        <div class="field-group">
          <label class="field-label">Date</label>
          <input type="date" name="date" id="date" class="field-input" required>
        </div>
        <div class="field-group">
          <label class="field-label">Type</label>
          <div class="toggle-group">
            <button type="button" class="toggle-btn active" id="btn_withdrawal" onclick="setType('withdrawal')">Withdrawal</button>
            <button type="button" class="toggle-btn" id="btn_deposit" onclick="setType('deposit')">Deposit</button>
          </div>
          <input type="hidden" id="trans_type" name="trans_type" value="withdrawal">
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
          <input type="text" id="description" name="description" class="field-input" required placeholder="What was this for?">
        </div>
        <div class="field-group">
          <label class="field-label">Check Number <span class="field-optional">(optional)</span></label>
          <input type="number" id="checknumber" name="checknumber" step="1" class="field-input" placeholder="—">
        </div>
        <input type="hidden" id="trans_id" name="trans_id">
        <input type="hidden" id="type" name="type">
        <div class="drawer-actions">
          <button type="submit" id="submitBtn" class="btn-primary">Add Transaction</button>
          <button type="button" id="deleteBtn" class="btn-danger" style="display:none" onclick="confirmDelete()">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Schedule List Drawer ── -->
  <div class="drawer" id="schedListDrawer">
    <div class="drawer-handle" onclick="closeAllDrawers()"></div>
    <div class="drawer-header">
      <h2 class="drawer-title">Scheduled Payments</h2>
      <button class="drawer-close" onclick="closeAllDrawers()">✕</button>
    </div>
    <div class="drawer-body drawer-body--list">
      <button class="btn-secondary btn-add-sched" onclick="newSched()">➕&nbsp; Add Scheduled Payment</button>
      <div class="sched-list">
        <?php
        if (is_array($schedule)) {
          foreach ($schedule as $line) {
            $isNeg = $line['amount'] < 0;
            $amtClass = $isNeg ? 'amt-neg' : 'amt-pos';
            $amtStr = ($isNeg ? '-' : '+') . '$' . number_format(abs($line['amount']), 2, '.', ',');
            $active = $line['active'] == 1;
            $nextRun = date('M j', strtotime($line['nextrun']));
            $lastRun = $line['lastrun'] ? date('M j, Y', strtotime($line['lastrun'])) : 'Never';
            echo '<div class="sched-row" onclick="editSched(' . $line['id'] . ')">';
            echo '  <div class="sched-left">';
            echo '    <span class="sched-desc">' . htmlspecialchars($line['description']) . '</span>';
            echo '    <span class="sched-meta">Day ' . $line['dayofmonth'] . ' &nbsp;·&nbsp; Next: ' . $nextRun . ' &nbsp;·&nbsp; Last: ' . $lastRun . '</span>';
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
    </div>
  </div>

  <!-- ── Schedule Edit/Add Drawer ── -->
  <div class="drawer" id="schedDrawer">
    <div class="drawer-handle" onclick="backToSchedList()"></div>
    <div class="drawer-header">
      <button class="drawer-back" onclick="backToSchedList()">‹ Back</button>
      <h2 class="drawer-title" id="schedTitle">Add Schedule</h2>
      <button class="drawer-close" onclick="closeAllDrawers()">✕</button>
    </div>
    <div class="drawer-body">
      <form method="POST" id="schedForm">
        <div class="field-group">
          <label class="field-label">Day of Month</label>
          <select name="dayofmonth" id="sched_date" class="field-input">
            <?php for ($x = 1; $x < 29; $x++) echo "<option value=\"$x\">$x</option>"; ?>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label">Amount</label>
          <div class="amount-input-wrap">
            <span class="amount-prefix">$</span>
            <input type="number" id="sched_amount" name="amount" min="0.01" step="0.01" class="field-input amount-input" onchange="$(this).val(Math.abs($(this).val()))" required placeholder="0.00">
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
        <input type="hidden" id="sched_id" name="trans_id">
        <input type="hidden" id="sched_type" name="type">
        <div class="drawer-actions">
          <button type="submit" id="schedSubmitBtn" class="btn-primary">Add Schedule</button>
          <button type="button" id="schedDeleteBtn" class="btn-danger" style="display:none" onclick="confirmDeleteSched()">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Search Drawer ── -->
  <div class="drawer" id="searchDrawer">
    <div class="drawer-handle" onclick="closeAllDrawers()"></div>
    <div class="drawer-header">
      <h2 class="drawer-title">Search</h2>
      <button class="drawer-close" onclick="closeAllDrawers()">✕</button>
    </div>
    <div class="drawer-body">
      <form method="post" id="search_form" onsubmit="return validateForm()">
        <input type="hidden" name="type" value="search">
        <div class="field-row">
          <div class="field-group">
            <label class="field-label">From</label>
            <input id="search_from" type="date" name="from" value="<?= $_POST['from'] ?? '' ?>" class="field-input search_fields">
          </div>
          <div class="field-group">
            <label class="field-label">To</label>
            <input id="search_to" type="date" name="to" value="<?= $_POST['to'] ?? '' ?>" class="field-input search_fields">
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Description</label>
          <input id="search_description" type="text" name="description" value="<?= $_POST['description'] ?? '' ?>" class="field-input search_fields" placeholder="Filter by description...">
        </div>
        <div class="field-group">
          <label class="field-label">Amount Range</label>
          <div class="range-row">
            <input id="search_low" type="number" name="low" step="0.01" value="<?= $_POST['low'] ?? '' ?>" class="field-input search_fields" placeholder="Min" onchange="$(this).val(Math.abs($(this).val()))">
            <span class="range-sep">–</span>
            <input id="search_high" type="number" name="high" step="0.01" value="<?= $_POST['high'] ?? '' ?>" class="field-input search_fields" placeholder="Max" onchange="$(this).val(Math.abs($(this).val()))">
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Check Numbers</label>
          <div class="range-row">
            <input id="check_low" type="number" name="check_low" step="1" value="<?= $_POST['check_low'] ?? '' ?>" class="field-input search_fields" placeholder="Min">
            <span class="range-sep">–</span>
            <input id="check_high" type="number" name="check_high" step="1" value="<?= $_POST['check_high'] ?? '' ?>" class="field-input search_fields" placeholder="Max">
          </div>
        </div>
        <div class="drawer-actions">
          <button type="submit" class="btn-primary">Search</button>
          <button type="button" class="btn-secondary" onclick="exportCSV()">Export CSV</button>
          <button type="button" class="btn-ghost" onclick="resetForm('search_fields')">Reset</button>
        </div>
      </form>
    </div>
  </div>

</div><!-- end app-shell -->
<script src="cb.js"></script>
</body>
</html>
