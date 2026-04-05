<?php
/*
 * Database credentials — read from settings.json.
 * Copy settings.json.example to settings.json and fill in your values.
 */
$json     = file_get_contents(__DIR__ . '/settings.json');
$settings = json_decode($json, true);
foreach ($settings as $key => $val) {
    define($key, $val);
}

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
