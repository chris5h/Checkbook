<?php
/**
 * Create or update a Checkbook user.
 * Run from the command line — do not expose this to the web.
 *
 * Usage:
 *   php create_user.php <username> <password>         — create new user
 *   php create_user.php <username> <password> --reset — update existing user's password
 *
 * Example:
 *   php create_user.php alice s3cr3tP@ssword
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script must be run from the command line.\n"
      . "Usage: php create_user.php <username> <password>\n");
}

if ($argc < 3) {
    fwrite(STDERR, "Usage: php create_user.php <username> <password> [--reset]\n");
    exit(1);
}

$username = trim($argv[1]);
$password = $argv[2];
$reset    = in_array('--reset', $argv, true);

if (strlen($username) < 1 || strlen($username) > 64) {
    fwrite(STDERR, "Error: username must be 1–64 characters.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Error: password must be at least 8 characters.\n");
    exit(1);
}

require_once __DIR__ . '/dbHelper.php';

$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$stmt = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($existing) {
    if (!$reset) {
        fwrite(STDERR, "Error: user '{$username}' already exists. Use --reset to update their password.\n");
        exit(1);
    }
    $stmt = mysqli_prepare($link, "UPDATE users SET password_hash = ? WHERE username = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
    mysqli_stmt_execute($stmt);
    echo "Password updated for user '{$username}'.\n";
} else {
    $stmt = mysqli_prepare($link, "INSERT INTO users (username, password_hash) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $hash);
    mysqli_stmt_execute($stmt);
    echo "User '{$username}' created successfully.\n";
}
