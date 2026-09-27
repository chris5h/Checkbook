<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Already logged in → go to app
if (isset($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Checkbook - Sign In</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="theme-color" content="#111318">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="cb.css">
  <style>
    .login-shell {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100dvh;
      padding: 32px 24px;
    }
    .login-card {
      width: 100%;
      max-width: 360px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .login-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;
    }
    .login-icon {
      width: 64px;
      height: 64px;
      border-radius: 16px;
    }
    .login-title {
      font-size: 1.6rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      color: var(--text);
    }
    .login-error {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.3);
      color: var(--neg);
      border-radius: var(--radius-sm);
      padding: 11px 14px;
      font-size: 0.875rem;
    }
    .login-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--text-dim);
      font-size: 0.8rem;
    }
    .login-divider::before,
    .login-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }
    .btn-biometric {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      padding: 13px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: var(--surface-2);
      color: var(--text);
      font-family: var(--font-brand);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.15s;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-biometric:active { background: var(--surface-3); }
    .btn-biometric:disabled { opacity: 0.6; cursor: not-allowed; }
    .bio-icon { font-size: 1.3rem; line-height: 1; }
  </style>
</head>
<body>
<div class="login-shell">
  <div class="login-card">

    <div class="login-brand">
      <img src="icon.png" alt="Checkbook" class="login-icon">
      <h1 class="login-title">Checkbook</h1>
    </div>

    <?php if ($error === '1'): ?>
      <div class="login-error">Incorrect username or password.</div>
    <?php elseif ($error === 'config'): ?>
      <div class="login-error">
        <strong>Setup required.</strong><br>
        The <code>users</code> table is missing or empty.<br>
        Run: <code>php create_user.php &lt;username&gt; &lt;password&gt;</code>
      </div>
    <?php endif; ?>

    <form method="POST" action="auth.php?action=login" id="loginForm">
      <div class="field-group">
        <label class="field-label">Username</label>
        <input type="text" name="username" class="field-input"
               autocomplete="username" required autofocus spellcheck="false">
      </div>
      <div class="field-group" style="margin-bottom:0">
        <label class="field-label">Password</label>
        <input type="password" name="password" class="field-input"
               autocomplete="current-password" required>
      </div>
      <div class="drawer-actions" style="padding-top:20px">
        <button type="submit" class="btn-primary">Sign In</button>
      </div>
    </form>

    <div class="login-divider" id="bioDivider" style="display:none">or</div>

    <button class="btn-biometric" id="bioBtn" style="display:none" onclick="loginWithBiometric()">
      <span class="bio-icon">🔐</span>
      <span id="bioBtnLabel">Use fingerprint / face</span>
    </button>

  </div>
</div>

<script>
/* ---- WebAuthn helpers ---- */
function b64uDecode(str) {
  str = str.replace(/-/g, '+').replace(/_/g, '/');
  while (str.length % 4) str += '=';
  return Uint8Array.from(atob(str), c => c.charCodeAt(0));
}
function b64uEncode(buf) {
  return btoa(String.fromCharCode(...new Uint8Array(buf)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/* Show biometric button only if a credential has been registered on this device */
if (localStorage.getItem('webauthn_enrolled') && window.PublicKeyCredential) {
  document.getElementById('bioDivider').style.display = '';
  document.getElementById('bioBtn').style.display     = '';
}

async function loginWithBiometric() {
  const btn   = document.getElementById('bioBtn');
  const label = document.getElementById('bioBtnLabel');
  btn.disabled = true;
  label.textContent = 'Waiting for biometric…';

  try {
    const data = await fetch('auth.php?action=webauthn_auth_challenge').then(r => r.json());

    if (!data.hasCredentials) {
      alert('No biometric credentials registered yet. Please sign in with your password first.');
      btn.disabled = false;
      label.textContent = 'Use fingerprint / face';
      return;
    }

    const cred = await navigator.credentials.get({
      publicKey: {
        challenge:        b64uDecode(data.challenge),
        allowCredentials: data.allowCredentials.map(c => ({
          type: 'public-key',
          id:   b64uDecode(c.id),
        })),
        userVerification: 'required',
        timeout:          60000,
      }
    });

    const body = {
      id:   cred.id,
      type: cred.type,
      response: {
        clientDataJSON:    b64uEncode(cred.response.clientDataJSON),
        authenticatorData: b64uEncode(cred.response.authenticatorData),
        signature:         b64uEncode(cred.response.signature),
        userHandle:        cred.response.userHandle ? b64uEncode(cred.response.userHandle) : null,
      },
    };

    const res  = await fetch('auth.php?action=webauthn_auth', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    });
    const resp = await res.json();

    if (resp.ok) {
      window.location.href = 'index.php';
    } else {
      alert('Biometric login failed: ' + (resp.error || 'Unknown error'));
      btn.disabled = false;
      label.textContent = 'Use fingerprint / face';
    }
  } catch (e) {
    if (e.name !== 'NotAllowedError') {
      alert('Biometric login failed: ' + e.message);
    }
    btn.disabled = false;
    label.textContent = 'Use fingerprint / face';
  }
}
</script>
</body>
</html>
