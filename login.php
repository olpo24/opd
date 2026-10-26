<?php
declare(strict_types=1);

// Prüfen, ob das Passkey-Modul existiert und AKTIV ist
$passkeyModuleDir = __DIR__ . '/modules/passkey';
$isPasskeyEnabled = is_dir($passkeyModuleDir) && !file_exists($passkeyModuleDir . '/.disabled');
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Core Operations Dashboard</title>
    <link rel="stylesheet" href="css/app.css">
    
    <!-- DYNAMISCHER SCRIPT-LOADER: Nur wenn Passkey-Modul aktiv ist -->
    <?php if ($isPasskeyEnabled): ?>
        <script src="modules/passkey/simplewebauthn.min.js"></script>
    <?php endif; ?>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h2 class="brand-title">Core Hub Login</h2>
                <p class="text-muted">Bitte melde dich an, um auf das Dashboard zuzugreifen.</p>
            </div>

            <!-- STANDARD PASSWORT FORMULAR -->
            <form id="login-form" action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Benutzername</label>
                    <input type="text" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Passwort</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn full-width">Anmelden</button>
            </form>

            <!-- DYNAMISCHER PASSKEY BUTTON: Nur gerendert wenn Modul aktiv -->
            <?php if ($isPasskeyEnabled): ?>
                <div class="login-divider">
                    <span>oder</span>
                </div>
                <button type="button" id="btn-passkey-login" onclick="loginWithPasskey()" class="btn btn-secondary full-width">
                    🔑 Mit Passkey anmelden
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- DYNAMISCHE PASSKEY JAVASCRIPT-LOGIK -->
    <?php if ($isPasskeyEnabled): ?>
    <script>
    async function loginWithPasskey() {
        try {
            const res = await fetch('modules/passkey/api.php?action=auth_options');
            if (!res.ok) throw new Error('Passkey Login fehlgeschlagen.');

            const options = await res.json();
            if (options.status === 'error') {
                alert(options.message || 'Fehler beim Abrufen der Login-Optionen.');
                return;
            }

            const assertion = await SimpleWebAuthnBrowser.startAuthentication(options);
            
            const verifyRes = await fetch('modules/passkey/api.php?action=auth_verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(assertion)
            });

            const verifyData = await verifyRes.json();
            if (verifyData.status === 'success') {
                window.location.href = 'index.php';
            } else {
                alert('Passkey-Anmeldung fehlgeschlagen: ' + (verifyData.message || 'Unbekannter Fehler'));
            }
        } catch (e) {
            console.error(e);
            alert('Passkey-Anmeldung abgebrochen oder fehlgeschlagen.');
        }
    }
    </script>
    <?php endif; ?>
</body>
</html>
