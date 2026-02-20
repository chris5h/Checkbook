<?php
/*
 * Database credentials.
 * Reads from environment variables first (set via Docker / docker-compose).
 * Falls back to settings.json for local development without Docker.
 */

$envServer   = getenv('DB_SERVER');
$envUsername = getenv('DB_USERNAME');
$envPassword = getenv('DB_PASSWORD');
$envName     = getenv('DB_NAME');

if ($envServer && $envUsername && $envName) {
    // Running inside Docker (or any environment with env vars set)
    define('DB_SERVER',   $envServer);
    define('DB_USERNAME', $envUsername);
    define('DB_PASSWORD', $envPassword);
    define('DB_NAME',     $envName);
} else {
    // Fallback: local dev without Docker
    $json     = file_get_contents(__DIR__ . '/settings.json');
    $settings = json_decode($json, true);
    foreach ($settings as $key => $val) {
        define($key, $val);
    }
}

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
