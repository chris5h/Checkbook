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
    $balance = "";
    $postdates = 0;
    $subtotal = "";
    $sql = 'SELECT 
        sum(amount) "Amount"
        ,SUM(IF(trans_date > CURDATE(), 1, 0)) "postdates" 
        ,SUM(IF(trans_date <= CURDATE(), amount, 0)) "Subtotal"
    from transactions';
    if($stmt = mysqli_prepare($link, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $balance = $row['Amount'];
                $postdates = $row['postdates'];
                $subtotal = $row['Subtotal'];
                
            }
        }
    }
    $class = ($balance < 0 ? 'negative' : 'positive');
    $total = number_format($balance,2,'.',',');
    $rtn = '<span class="'.$class.'">$'.$total.'</span>';
    if($postdates > 0){
        $val = number_format($subtotal,2,'.',',');
        $class = ($subtotal < 0 ? 'negative_sub' : 'positive_sub');
        $rtn .= ' <i><span class="'.$class.'">($'.$val.' subtotal)</span></i>';
    }
    return $rtn;
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

function getSched($id){
    global $link;
    $sql = "SELECT *, str_to_date(concat(month(curdate()) + if(dayofmonth(curdate()) > s.dayofmonth,1,0),',',s.dayofmonth,',',year(curdate())),'%m,%d,%Y') AS `nextrun` FROM `schedule` s where s.id = ?";
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

function newSchedule($dayofmonth, $amount, $active, $desc){
    global $link;
    $amount =  0 - $amount;
    $sql = "INSERT INTO schedule (`dayofmonth`, `amount`,`description`,`active`) VALUES (?,?,?,?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $dayofmonth, $amount, $desc, $active,);
    mysqli_stmt_execute($stmt);
}

function editSchedule($dayofmonth, $amount, $active, $description, $id ){
    global $link;
    $amount = 0 - $amount;
    $sql = "UPDATE schedule SET `dayofmonth` = ?, `amount` = ?, `description` = ?, `active` = ? WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "issii", $dayofmonth, $amount, $description, $active, $id);
    mysqli_stmt_execute($stmt);
}

function delSched($id){
    global $link;
    $sql = "delete from schedule WHERE id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
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

function getSchedule(){
    global $link;
    $sql = "SELECT * FROM allschedules";
    if($stmt = mysqli_prepare($link, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $results[] = $row;
            }
        }
    }    
    return is_array($results) ? $results : false;
}