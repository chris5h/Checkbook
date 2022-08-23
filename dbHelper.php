<?
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

$json = file_get_contents('settings.json');
$settings = json_decode($json, true);

foreach ($settings as $key => $val){
    define($key, $val);
}

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
