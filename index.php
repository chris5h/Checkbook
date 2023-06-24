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
}
$balance = getBalance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Checkbook</title>
  <meta charset="utf-8">
  <link href="icon.png" rel="icon" type="image/x-icon" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.23/css/jquery.dataTables.css">  
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <style>
    .positive {
      color: darkgreen;
      font-size: 125%;
      font-weight: bold;
    }
    .negative {
      color: darkred;
      font-size: 125%;
      font-weight: bold;
    }
    .trans_row{
      cursor: pointer;
    }
    .ui-autocomplete {
      z-index: 1510 !important;
    }
  </style>
  <script>
    $( function() {
      $( "#description" ).autocomplete({
        source: 'handler.php',
        minLength: 2
      });
    } );
  </script>
</head>
<body>
<div class="container">
<br>
<br>
<div class="card" id = "nav_card">
  <div class="card-header" style="padding: 0px; padding-left: 10px;"><h4 class="card-title">Account Overview</h4></div>
  <div class="card-body"   style="padding: 0px; padding-left: 10px;">Balance: <span class="<?= $balance < 0 ? 'negative' : 'positive' ?>"><?= '$'.number_format($balance,2,'.',',') ?></span></div>
    <div class="card-footer"><div class="btn-group input-group" role="group">
      <button class="btn btn-outline-primary nav_bar" onclick="window.location = window.location['href']"> 🏠 Home </button>
      <button class="btn btn-outline-dark nav_bar" onclick="newTrans()"> ➕ Add </button>
      <button class="btn btn-outline-danger nav_bar" onclick="$('#searchModal').modal('show');setWidth('date_header');"> 🔍 Search </button>
      <button class="btn btn-outline-success nav_bar" onclick="window.location = 'sched.php'"> 📅 Schedule </button>
    </div>
  </div>
</div>
<br>
<table id="trans_table" class="display" >
    <thead>
        <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Check #</th>
        </tr>
    </thead>


    <tbody>
<?
  if (is_array(($trans))){
    foreach ($trans as $line){
        ?>
        <tr onclick="editTrans(<?= $line['id'] ?>)" class="trans_row">
            <td><?= date('m/d/Y', strtotime($line['trans_date'])) ?></td>
            <td class="<?= $line['amount'] < 0 ? 'negative' : 'positive' ?>">$<?= number_format($line['amount'],2,'.',',') ?></td>
            <td><?= $line['description'] ?></td>
            <td><?= $line['checknumber'] ?></td>

        </tr>
        <?
    }
  } else  {
    print "No records found!";
  }
?>
    </tbody>
</table>

<!--  Begin the modal for add/edit transaction  -->
<div class="modal" id="transModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title" id="transtitle"></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <!-- Modal body -->
      <div class="modal-body">
      <form method="POST">
      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Date</span>
        </div>
        <input type="date" name="date" id="date" class="form-control" required>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Type</span>
        </div>
        <select id="trans_type" name="trans_type" class="form-control" required>
            <option value="withdrawal">Withdrawal</option>
            <option value="deposit">Deposit</option>
        </select>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Amount</span>
        </div>
        <input type="number" id="amount" name="amount" min="0.01" step="0.01" class="form-control" onchange="$(this).val(Math.abs($(this).val()))" required>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Description</span>
        </div>
        <input id="description" name="description" class="form-control" required>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Check Number</span>
        </div>
        <input type="number" id="checknumber" name="checknumber" step="1" class="form-control">
      </div>

      <input type="hidden" id="trans_id" name="trans_id">
      <input type="hidden" id="type" name="type">
      <br>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <input type="submit" id="button" class="btn btn-primary buttongrp">
        <button type="button" id="delete_button" class="btn btn-danger buttongrp"  onclick="var x =  confirm('Are you sure?'); if (x){delTrans();return false;}">Delete</button>
        <button type="button"  class="btn btn-danger buttongrp" data-dismiss="modal">Close</button>      
      </div>
      </form>

    </div>
  </div>
</div>


<!--  Begin search modal  -->
<div class="modal" id="searchModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Search Transactions</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
      <form method="post" id="search_form"  onsubmit="return validateForm()" >
        <input type="hidden" name="type" value="search">
        <div class="input-group" style="border-radius: 0px !important;">
          <div class="input-group-prepend">
            <span class="input-group-text date_header"  style="border-bottom-left-radius: 0px !important;">From</span>
          </div>
          <input id="search_from" type="date" name="from" value="<?= $_POST['from'] ?>" class="form-control search_fields" style="border-bottom-right-radius: 0px !important;">
        </div>
        <div class="input-group" style="margin-bottom: 1rem!important;">
          <div class="input-group-prepend">
            <span class="input-group-text date_header" style="border-top-left-radius: 0px !important;">To</span>
          </div>
          <input id="search_to" type="date" name="to" value="<?= $_POST['to'] ?>" class="form-control search_fields" style="border-top-right-radius: 0px !important;">
        </div>

        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text date_header">Description</span>
          </div>
          <input id="search_description" type="text" name="description" value="<?= $_POST['desc'] ?>" class="form-control search_fields">
        </div>
        
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text date_header">Amount Range</span>
          </div>
          <input id="search_low" type="number" name="low" step="0.01" value="<?= $_POST['low'] ?>" class="form-control search_fields" onchange="$(this).val(Math.abs($(this).val()))">
          <div class="input-group-prepend">
            <span class="input-group-text">To</span>
          </div>
          <input id="search_high" type="number" name="high" step="0.01" value="<?= $_POST['high'] ?>" class="form-control search_fields" onchange="$(this).val(Math.abs($(this).val()))">
        </div>

        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text date_header">Check Numbers</span>
          </div>
          <input id="check_low" type="number" name="check_low" step="1" value="<?= $_POST['check_low'] ?>" class="form-control search_fields">
          <div class="input-group-prepend">
            <span class="input-group-text">To</span>
          </div>
          <input id="check_high" type="number" name="check_high" step="1" value="<?= $_POST['check_high'] ?>" class="form-control search_fields">
        </div>


        <div class="btn-group input-group" role="group" >
          <input type="submit" value="Search" class="btn btn-outline-primary">
          <input type="button" value="Export CSV" class="btn btn-outline-dark" onclick="exportCSV()">
          <input type="button" onclick="resetForm('search_fields')" value="Reset" class="btn btn-outline-danger">
        </div>

      </form>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script src="cb.js"></script>
</body>
</html>