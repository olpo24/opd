window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'passkey') {
        loadPasskeyCredentials();
    }
});

async function loadPasskeyCredentials() {
    const tbody = document.getElementById('passkey-credentials-list');
    if (!tbody) return;

    try {
        const res = await fetch('modules/passkey/api.php?action=get_credentials');
        const response = await res.json();

        if (response.status === 'success' && Array.isArray(response.data)) {
            tbody.innerHTML = '';
            if (response.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="table-empty-cell">Keine Passkeys registriert.</td></tr>';
                return;
            }

            response.data.forEach(key => {
                const tr = document.createElement('tr');
                const shortId = (key.credential_id || '').substring(0, 24) + '...';
                const created = key.created_at ? new Date(key.created_at).toLocaleString('de-DE') : '-';

                tr.innerHTML = `
                    <td><code class="passkey-credential-id">${escapeHtml(shortId)}</code></td>
                    <td>${created}</td>
                    <td><span class="badge badge-zero">${key.sign_count || 0} Login(s)</span></td>
                    <td class="table-actions-cell">
                        <button onclick="deletePasskeyCredential(${key.id})" class="btn btn-danger btn-sm">Entfernen</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Fehler beim Laden der Passkeys:', e);
    }
}

async function registerPasskey() {
    try {
        const res = await fetch('modules/passkey/api.php?action=register_options');
        if (!res.ok) throw new Error(`HTTP Fehler ${res.status}`);

        const options = await res.json();
        if (options.status === 'error' || options.error) { 
            alert(options.message || options.error); 
            return; 
        }

        if (typeof SimpleWebAuthnBrowser === 'undefined') {
            alert('SimpleWebAuthn wurde nicht geladen.');
            return;
        }

        const attResp = await SimpleWebAuthnBrowser.startRegistration(options);
        const verificationRes = await fetch('modules/passkey/api.php?action=register_verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(attResp)
        });

        const verificationJSON = await verificationRes.json();
        if (verificationJSON.verified || verificationJSON.status === 'success') {
            alert('Passkey erfolgreich registriert!');
            loadPasskeyCredentials();
        } else {
            alert('Registrierung fehlgeschlagen: ' + (verificationJSON.message || 'Unbekannter Fehler'));
        }
    } catch (e) {
        console.error(e);
        alert('Passkey-Registrierung abgebrochen.');
    }
}

async function deletePasskeyCredential(id) {
    if (!confirm('Sicherheitsschlüssel wirklich entfernen?')) return;
    await fetch(`modules/passkey/api.php?action=delete_credential&id=${id}`);
    loadPasskeyCredentials();
}
