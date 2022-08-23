<?
require_once 'dbHelper.php';

function importTrans($date, $desc, $amount){
    global $link;
    $sql = "INSERT INTO transactions (trans_date, description, amount) VALUES (?,?,?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $date, $desc, $amount);
    mysqli_stmt_execute($stmt);
}


function insertTrans($date, $desc, $amount, $type, $checknumber){
    global $link;
    $amount  = $type == "withdrawal" ? 0-$amount : $amount;
    die('here');
    $sql = "INSERT INTO transactions (trans_date, description, amount, checknumber) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $date, $desc, $amount, $checknumber);
    mysqli_stmt_execute($stmt);
}

function getBalance(){
    global $link;
    $sql = 'select sum(amount) "Amount" from transactions';
    if($stmt = mysqli_prepare($link, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                return $row['Amount'];
            }
        }
    }
}


function get1kTrans(){
    global $link;
    $sql = "SELECT * FROM transactions
    ORDER BY trans_date DESC, id desc
    LIMIT 1000";
    if($stmt = mysqli_prepare($link, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $data[] = $row;
            }
        }
    }
    return $data;
}

function getTrans($id){
    global $link;
    $sql = 'SELECT * FROM transactions WHERE id = ?';
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                return $row;
            }
        }
    }
}

function newTrans($date, $amount, $type, $des, $checknumber){
    global $link;
    $amount = $type == 'withdrawal' ? 0-$amount : $amount;
    $checknumber = $checknumber == '' ? null : $checknumber;
    $sql = "INSERT INTO transactions (trans_date, description, amount, checknumber) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $date, $des, $amount, $checknumber);
    mysqli_stmt_execute($stmt);
}

function editTrans($date, $amount, $type, $des, $checknumber, $id ){
    global $link;
    $amount = $type == 'withdrawal' ? 0-$amount : $amount;
    $checknumber = $checknumber == '' ? null : $checknumber;
    $sql = "UPDATE transactions SET trans_date = ?, description = ?, amount = ?, checknumber = ? WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $date, $des, $amount, $checknumber, $id);
    mysqli_stmt_execute($stmt);
}

function delTrans($id){
    global $link;
    $sql = "delete from  transactions  WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

function searchTags($search){
    global $link;
    $sql = "SELECT description, COUNT(description) FROM transactions 
    WHERE description LIKE concat(?,'%') AND trans_date > DATE_SUB(curdate(), INTERVAL 1 YEAR)
    GROUP BY description
    ORDER BY COUNT(description) DESC
    LIMIT 10";
    $data = [];
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $search);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $data[] = $row['description'];
            }
        }
    }
    return $data;
}

function searchTrans($arr){
    global $link;
    $sql = "SELECT * FROM transactions t	WHERE
        (t.trans_date >= ?  OR ? IS null)
    AND
        (t.trans_date <= ?  OR ? IS null)
    AND
        (ABS(t.amount) >= ? OR ? IS null)
    AND
        (ABS(t.amount) <= ? OR ? IS NULL)
    AND
        (t.description LIKE CONCAT('%',?,'%') OR ? IS NULL)
    AND
        (ABS(t.checknumber) >= ? OR ? IS null)
    AND
        (ABS(t.checknumber) <= ? OR ? IS NULL)
    ORDER BY t.trans_date DESC, t.id desc";
    foreach ($arr as $key => $val){
        $arr[$key] = ($arr[$key] == '' ? null : $arr[$key]);
    }
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ssssssssssssss", $arr[0], $arr[0], $arr[1], $arr[1], $arr[2], $arr[2], $arr[3], $arr[3], $arr[4], $arr[4], $arr[5], $arr[5], $arr[6], $arr[6]);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $trans[] = $row;
            }
        }
    }
    return $trans;
}