<?php
require_once 'calls.php';
if ($_GET){
    if (array_key_exists('term', $_GET)){
        $result = searchTags($_GET['term']);
        print json_encode($result, JSON_PRETTY_PRINT);
    }   else    {
        if ($_GET['type'] == 'gettransaction'){
            $data = getTrans($_GET['id']);
            print json_encode($data, JSON_PRETTY_PRINT);
        }   elseif ($_GET['type'] == 'getschedule'){
            $data = getSched($_GET['id']);
            print json_encode($data, JSON_PRETTY_PRINT);            
        }   elseif ($_GET['type'] == 'deltrans'){
            delTrans($_GET['id']);
        } elseif ($_GET['type'] == 'delsched'){
            delSched($_GET['id']);
        }   elseif ($_GET['type'] == 'search'){
            $trans = searchTrans([$_POST['from'],	$_POST['to'],	$_POST['low'],	$_POST['high'],	$_POST['description'], $_POST['check_low'], $_POST['check_high']]);
            print json_encode($trans, JSON_PRETTY_PRINT);
        }
    }
    die();
}   elseif ($_POST)    {
    switch ($_POST['type']){
        case 'new':
            newTrans($_POST['date'], $_POST['amount'], $_POST['trans_type'], $_POST['description'], $_POST['checknumber']);
            header('Location: '.$_SERVER['PHP_SELF']);
            die();
            break;
        case 'edit':
            editTrans($_POST['date'], $_POST['amount'], $_POST['trans_type'], $_POST['description'], $_POST['checknumber'], $_POST['trans_id']);
            header('Location: '.$_SERVER['PHP_SELF']);
            die();
            break;
        case 'search':
            $trans = searchTrans([$_POST['from'],	$_POST['to'],	$_POST['low'],	$_POST['high'],	$_POST['description'], $_POST['check_low'], $_POST['check_high']]);
            break;
        case 'newsched':
            newSchedule($_POST['dayofmonth'], $_POST['amount'], $_POST['active'], $_POST['description']);
            header('Location: '.$_SERVER['PHP_SELF']);
            die();            
            break;
        case 'editsched':
            editSchedule($_POST['dayofmonth'], $_POST['amount'], $_POST['active'], $_POST['description'], $_POST['trans_id']);
            header('Location: '.$_SERVER['PHP_SELF']);
            die();
            break;
    }
}