<?
require_once 'calls.php';
set_time_limit(300);
$csvFile = file('clearcheckbook.csv');
unset($csvFile[0]);
$csvFile = array_reverse($csvFile);
$data = []; 
foreach ($csvFile as $i => $line) {
    $data = str_getcsv($line);
    $date = date('Y-m-d', strtotime($data[0]));
    importTrans($date, $data[2], $data[1]);
}