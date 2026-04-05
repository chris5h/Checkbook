<?php
/**
 * Auth endpoint — handles all login/logout and WebAuthn actions.
 *
 * Actions (via ?action=...):
 *   POST login                       — password login (queries users table)
 *   GET  logout                      — destroy session
 *   GET  webauthn_register_challenge — (authenticated) generate registration challenge
 *   POST webauthn_register           — (authenticated) store new credential tied to user
 *   GET  webauthn_auth_challenge     — generate assertion challenge + credential list
 *   POST webauthn_auth               — verify assertion → create session
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'dbHelper.php';
require_once 'webauthn_lib.php';

$action = $_GET['action'] ?? '';

// ---------------------------------------------------------------------------
// Helper: JSON response and exit
// ---------------------------------------------------------------------------
function jsonOut(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ---------------------------------------------------------------------------
// Helper: get APP_ORIGIN (required for WebAuthn RP validation)
// ---------------------------------------------------------------------------
function getAppOrigin(): string {
    if (!defined('APP_ORIGIN') || !APP_ORIGIN) {
        throw new Exception('APP_ORIGIN is not set in settings.json. WebAuthn requires an explicit origin.');
    }
    return rtrim(APP_ORIGIN, '/');
}

// ---------------------------------------------------------------------------
// Helper: start an authenticated session for a user row
// ---------------------------------------------------------------------------
function startSession(array $user): void {
    session_regenerate_id(true);
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['username']      = $user['username'];
}

// ---------------------------------------------------------------------------
// POST login — query users table
// ---------------------------------------------------------------------------
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($link, "SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        // users table likely doesn't exist yet
        header('Location: login.php?error=config');
        exit;
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password_hash'])) {
        startSession($user);
        header('Location: index.php');
    } else {
        header('Location: login.php?error=1');
    }
    exit;
}

// ---------------------------------------------------------------------------
// GET logout
// ---------------------------------------------------------------------------
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// ---------------------------------------------------------------------------
// GET webauthn_register_challenge — must be authenticated
// ---------------------------------------------------------------------------
if ($action === 'webauthn_register_challenge') {
    if (!isset($_SESSION['authenticated'])) {
        jsonOut(['error' => 'Not authenticated'], 401);
    }
    $challenge = random_bytes(32);
    $_SESSION['webauthn_challenge'] = $challenge;
    jsonOut(['challenge' => base64url_encode($challenge)]);
}

// ---------------------------------------------------------------------------
// POST webauthn_register — must be authenticated, ties credential to user
// ---------------------------------------------------------------------------
if ($action === 'webauthn_register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['authenticated'])) {
        jsonOut(['error' => 'Not authenticated'], 401);
    }

    $challenge = $_SESSION['webauthn_challenge'] ?? null;
    unset($_SESSION['webauthn_challenge']);
    if (!$challenge) {
        jsonOut(['error' => 'No challenge in session'], 400);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        jsonOut(['error' => 'Invalid request body'], 400);
    }

    try {
        $origin            = getAppOrigin();
        $clientDataJSON    = base64url_decode($body['response']['clientDataJSON']);
        $attestationObject = base64url_decode($body['response']['attestationObject']);

        // 1. Verify clientDataJSON
        verifyClientData($clientDataJSON, $challenge, $origin, 'webauthn.create');

        // 2. Decode attestationObject (CBOR)
        [$attObj] = cbor_decode($attestationObject);
        if (!isset($attObj['authData'])) {
            throw new Exception('Missing authData in attestationObject');
        }

        // 3. Parse authenticatorData
        $parsed = parseAuthenticatorData($attObj['authData']);

        // 4. Verify RP ID hash
        $hostname = parse_url($origin, PHP_URL_HOST);
        $rpIdHash = hash('sha256', $hostname, true);
        if (!hash_equals($rpIdHash, $parsed['rpIdHash'])) {
            throw new Exception('RP ID hash mismatch');
        }

        // 5. Verify user-present flag (bit 0)
        if (!($parsed['flags'] & 0x01)) {
            throw new Exception('User-present flag not set');
        }

        if (!$parsed['credentialId'] || !$parsed['publicKeyCbor']) {
            throw new Exception('No credential data in authenticatorData');
        }

        // 6. Convert COSE public key to PEM
        $pem          = coseKeyToPem($parsed['publicKeyCbor']);
        $credIdB64url = base64url_encode($parsed['credentialId']);
        $userId       = (int)$_SESSION['user_id'];

        // 7. Store credential linked to the current user
        $sql  = "INSERT INTO webauthn_credentials (user_id, credential_id, public_key, sign_count) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 'issi', $userId, $credIdB64url, $pem, $parsed['signCount']);
        mysqli_stmt_execute($stmt);

        jsonOut(['ok' => true, 'credentialId' => $credIdB64url]);

    } catch (Exception $e) {
        jsonOut(['error' => $e->getMessage()], 400);
    }
}

// ---------------------------------------------------------------------------
// GET webauthn_auth_challenge — unauthenticated, returns all credential IDs
// ---------------------------------------------------------------------------
if ($action === 'webauthn_auth_challenge') {
    $challenge = random_bytes(32);
    $_SESSION['webauthn_challenge'] = $challenge;

    $creds  = [];
    $result = mysqli_query($link, "SELECT credential_id FROM webauthn_credentials");
    while ($row = mysqli_fetch_assoc($result)) {
        $creds[] = ['type' => 'public-key', 'id' => $row['credential_id']];
    }

    jsonOut([
        'challenge'        => base64url_encode($challenge),
        'allowCredentials' => $creds,
        'hasCredentials'   => !empty($creds),
    ]);
}

// ---------------------------------------------------------------------------
// POST webauthn_auth — verify assertion, resolve user via credential
// ---------------------------------------------------------------------------
if ($action === 'webauthn_auth' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $challenge = $_SESSION['webauthn_challenge'] ?? null;
    unset($_SESSION['webauthn_challenge']);
    if (!$challenge) {
        jsonOut(['error' => 'No challenge in session'], 400);
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        jsonOut(['error' => 'Invalid request body'], 400);
    }

    try {
        $origin         = getAppOrigin();
        $clientDataJSON = base64url_decode($body['response']['clientDataJSON']);
        $authDataRaw    = base64url_decode($body['response']['authenticatorData']);
        $signatureDer   = base64url_decode($body['response']['signature']);
        $credentialId   = $body['id'] ?? '';

        // 1. Verify clientDataJSON
        verifyClientData($clientDataJSON, $challenge, $origin, 'webauthn.get');

        // 2. Look up credential (joins to user)
        $sql  = "SELECT c.*, u.id AS uid, u.username FROM webauthn_credentials c
                 JOIN users u ON u.id = c.user_id
                 WHERE c.credential_id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 's', $credentialId);
        mysqli_stmt_execute($stmt);
        $cred = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$cred) {
            throw new Exception('Credential not found');
        }

        // 3. Verify RP ID hash
        $hostname = parse_url($origin, PHP_URL_HOST);
        $rpIdHash = hash('sha256', $hostname, true);
        $parsed   = parseAuthenticatorData($authDataRaw);
        if (!hash_equals($rpIdHash, $parsed['rpIdHash'])) {
            throw new Exception('RP ID hash mismatch');
        }

        // 4. Verify user-present flag
        if (!($parsed['flags'] & 0x01)) {
            throw new Exception('User-present flag not set');
        }

        // 5. Replay-attack prevention
        if ($parsed['signCount'] > 0 && $parsed['signCount'] <= (int)$cred['sign_count']) {
            throw new Exception('Sign count invalid — possible cloned authenticator');
        }

        // 6. Verify signature
        verifyAssertionSignature($authDataRaw, $clientDataJSON, $signatureDer, $cred['public_key']);

        // 7. Update sign count
        $newCount = $parsed['signCount'];
        $credDbId = (int)$cred['id'];
        $sql      = "UPDATE webauthn_credentials SET sign_count = ? WHERE id = ?";
        $stmt     = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $newCount, $credDbId);
        mysqli_stmt_execute($stmt);

        // 8. Start session as the user the credential belongs to
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id']       = (int)$cred['uid'];
        $_SESSION['username']      = $cred['username'];

        jsonOut(['ok' => true]);

    } catch (Exception $e) {
        jsonOut(['error' => $e->getMessage()], 400);
    }
}

http_response_code(400);
jsonOut(['error' => 'Unknown action']);
