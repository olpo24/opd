<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// WICHTIG: Wenn als <script> aufgerufen, JavaScript Header senden!
if (isset($_GET['script'])) {
    header('Content-Type: application/javascript; charset=utf-8');
    ?>
    async function registerPasskey() {
        try {
            const res = await fetch('passkey_handler.php?action=register_options');
            const options = await res.json();
            if (options.error) { alert(options.error); return; }

            const attResp = await SimpleWebAuthnBrowser.startRegistration(options);
            const verificationRes = await fetch('passkey_handler.php?action=register_verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(attResp)
            });

            const verificationJSON = await verificationRes.json();
            if (verificationJSON.verified) {
                alert('Passkey erfolgreich registriert!');
            } else {
                alert('Registrierung fehlgeschlagen: ' + (verificationJSON.error || 'Unbekannter Fehler'));
            }
        } catch (e) {
            console.error(e);
            alert('Fehler bei der Passkey-Registrierung: ' + e.message);
        }
    }
    <?php
    exit;
}

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht angemeldet']);
    exit;
}

// ... Hier folgt dein bisheriger PHP-Code für register_options / register_verify ...
