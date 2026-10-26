<div class="card">
    <div class="view-header">
        <div>
            <h2 class="view-title">Passkeys & FIDO2 Authentifizierung</h2>
            <p class="view-subtitle">Verwalte deine biometrischen Logins und Sicherheitsschlüssel für den passwortlosen Zugriff.</p>
        </div>
        <button class="btn" onclick="registerPasskey()">+ Neuen Passkey registrieren</button>
    </div>

    <div class="passkey-list-container">
        <h3 class="form-title">Registrierte Sicherheitsschlüssel</h3>
        <table>
            <thead>
                <tr>
                    <th>Credential ID / Schlüssel</th>
                    <th>Erstellt am</th>
                    <th>Nutzungen</th>
                    <th class="table-actions-cell">Aktion</th>
                </tr>
            </thead>
            <tbody id="passkey-credentials-list">
                <!-- Dynamisch via app.js -->
            </tbody>
        </table>
    </div>
</div>
