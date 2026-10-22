<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_once __DIR__ . '/auth.php';
    if (Auth::loginWithPassword($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ungültiger Benutzername oder Passwort.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WP Maintenance Dashboard</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #3b82f6; --text: #f8fafc; --border: #334155; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 2rem; width: 100%; max-width: 400px; box-sizing: border-box; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; color: #94a3b8; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.6rem; background: #0f172a; border: 1px solid var(--border); color: white; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; background: var(--accent); color: white; border: none; padding: 0.75rem; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 0.5rem; }
        .btn-passkey { background: #10b981; margin-top: 1rem; }
        .error { background: #ef444422; border: 1px solid #ef4444; color: #f87171; padding: 0.6rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
        .divider { text-align: center; margin: 1.5rem 0; color: #64748b; font-size: 0.85rem; position: relative; }
        .divider::before, .divider::after { content: ""; position: absolute; top: 50%; width: 40%; height: 1px; background: var(--border); }
        .divider::before { left: 0; } .divider::after { right: 0; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="margin-top:0; text-align:center;">Dashboard Login</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn">Anmelden</button>
        </form>

        <div class="divider">ODER</div>

        <button type="button" id="btn-passkey-login" class="btn btn-passkey">🔑 Mit Passkey anmelden</button>
    </div>

<script src="js/simplewebauthn.min.js"></script>
    <script>
        document.getElementById('btn-passkey-login').addEventListener('click', async () => {
            try {
                // 1. Challenge vom Server holen
                const resp = await fetch('passkey_handler.php?action=get_auth_options');
                const options = await resp.json();

                // 2. Browser WebAuthn Prompt öffnen (Touch ID / Face ID / YubiKey)
                const authResp = await SimpleWebAuthnBrowser.startAuthentication(options);

                // 3. Antwort an Server verifizieren lassen
                const verifyResp = await fetch('passkey_handler.php?action=verify_auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(authResp)
                });

                const result = await verifyResp.json();
                if (result.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    alert('Passkey-Anmeldung fehlgeschlagen: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Passkey-Anmeldung abgebrochen oder nicht unterstützt.');
            }
        });
    </script>
</body>
</html>