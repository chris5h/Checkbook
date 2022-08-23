<?
require_once 'calls.php';
$trans = searchTrans([$_POST['from'],	$_POST['to'],	$_POST['low'],	$_POST['high'],	$_POST['description'], $_POST['check_low'], $_POST['check_high']]);
$f = fopen('php://memory', 'w');
    $trans = array_merge([array_keys($trans[0])], $trans);
    foreach ($trans as $line) { 
        fputcsv($f, $line, ','); 
    }
fseek($f, 0);
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="report.csv";');
fpassthru($f);
?>