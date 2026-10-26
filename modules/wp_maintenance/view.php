<div class="card">
    <div class="view-header">
        <div>
            <h2 class="view-title">WordPress Wartungszentrale</h2>
            <p class="view-subtitle">Überwache Updates, Versionen und Sicherheitslücken deiner Instanzen.</p>
        </div>
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="switchTab('dashboard')">Übersicht</button>
            <button class="tab-btn" onclick="switchTab('settings')">Instanzen verwalten</button>
        </div>
    </div>

    <div id="tab-dashboard">
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;"></th>
                    <th>Instanz</th>
                    <th>Versionen</th>
                    <th>Sicherheit</th>
                    <th>Core</th>
                    <th>Plugins</th>
                    <th>Themes</th>
                    <th>Sprachen</th>
                    <th>Letzter Check</th>
                    <th class="table-actions-cell">Aktionen</th>
                </tr>
            </thead>
            <tbody id="sites-table-body"></tbody>
        </table>
    </div>

    <div id="tab-settings" style="display: none;">
        <div class="form-box">
            <h3 class="form-title">Neue WordPress Instanz hinzufügen</h3>
            <form id="add-site-form" class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" id="site_name" required placeholder="z. B. Kundenwebseite">
                </div>
                <div class="form-group">
                    <label class="form-label">URL</label>
                    <input type="url" id="site_url" required placeholder="https://domain.de">
                </div>
                <div class="form-group">
                    <label class="form-label">API-Key</label>
                    <input type="text" id="api_key" required placeholder="Aus Worker Plugin kopieren">
                </div>
                <div class="form-actions-full">
                    <button type="submit" class="btn">+ Instanz speichern</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>URL</th>
                    <th>API-Key</th>
                    <th class="table-actions-cell">Aktionen</th>
                </tr>
            </thead>
            <tbody id="settings-table-body"></tbody>
        </table>
    </div>
</div>

<div id="edit-modal" class="modal-overlay">
    <div class="modal">
        <h3 class="modal-title">Instanz bearbeiten</h3>
        <form id="edit-site-form">
            <input type="hidden" id="edit_site_id">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" id="edit_site_name" required>
            </div>
            <div class="form-group">
                <label class="form-label">URL</label>
                <input type="url" id="edit_site_url" required>
            </div>
            <div class="form-group">
                <label class="form-label">API-Key</label>
                <input type="text" id="edit_api_key" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Abbrechen</button>
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    </div>
</div>
