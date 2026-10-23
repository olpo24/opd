async function registerPasskey() {
    try {
        const res = await fetch('passkey_handler.php?action=register_options');
        
        if (!res.ok) {
            throw new Error(`HTTP Fehler ${res.status}`);
        }

        const options = await res.json();
        if (options.error) { 
            alert(options.error); 
            return; 
        }

        if (typeof SimpleWebAuthnBrowser === 'undefined') {
            alert('SimpleWebAuthn wurde nicht geladen.');
            return;
        }

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
