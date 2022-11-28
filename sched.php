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
  <div class="card-header" style="padding: 0px; padding-left: 10px;"><h4 class="card-title">Scheduled Payments</h4></div>
    <div class="card-footer">
      <div class="btn-group input-group" role="group">
      <button class="btn btn-outline-primary nav_bar" onclick="window.location = '/'"> 🏠 Home </button>
      <button class="btn btn-outline-dark nav_bar" onclick="newSched()"> ➕ Add </button>
    </div>
  </div>
</div>
<br>
<table id="trans_table" class="display" >
    <thead>
        <tr>
          <th>Description</th>
          <th>Amount</th>
          <th>Day of Month</th>
          <th>Next Run</th>
          <th>Last Run</th>          
          <th>Enabled</th>
        </tr>
    </thead>


    <tbody>
<?
  if (is_array(($trans))){
    foreach ($trans as $line){
        ?>
        <tr onclick="editSched(<?= $line['id'] ?>)" class="trans_row">
            <td><?= $line['description'] ?></td>
            <td class="<?= $line['amount'] < 0 ? 'negative' : 'positive' ?>">$<?= number_format($line['amount'],2,'.',',') ?></td>
            <td><?= $line['dayofmonth'] ?></td>
            <td><?= date('m/d/Y', strtotime($line['nextrun'])) ?></td>
            <td><?= is_null($line['lastrun']) ? '' : date('m/d/Y', strtotime($line['lastrun'])) ?></td>
            <td><?= $line['active'] == 1 ? '✔️' : '🚫' ?></td>
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
        <select name="dayofmonth" id="date" class="form-control">
          <?php
            $x = 1;
            while ($x < 29){
              ?>
          <option value="<?= $x ?>"><?= $x ?></option>
              <?
              $x++;
            }
          ?>
        </select>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Amount</span>
        </div>
        <input type="number" id="amount" name="amount" min="0.01" step="0.01" class="form-control" required>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Description</span>
        </div>
        <input id="description_sched" name="description" class="form-control" required>
      </div>

      <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text trans_header">Active</span>
        </div>
        <select name="active" id="active" class="form-control" required>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>            

      <input type="hidden" id="trans_id" name="trans_id">
      <input type="hidden" id="type" name="type">
      <br>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <input type="submit" id="button" class="btn btn-primary buttongrp">
        <button type="button" id="delete_button" class="btn btn-danger buttongrp"  onclick="var x =  confirm('Are you sure?'); if (x){delSched();return false;}">Delete</button>
        <button type="button"  class="btn btn-danger buttongrp" data-dismiss="modal">Close</button>      
      </div>
      </form>

    </div>
  </div>
</div>


<script src="cb.js"></script>
</body>
</html>