<!-- TAB 1: OVERVIEW TABLE -->
<div id="tab-dashboard" class="tab-content active" style="display: block;">
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th style="width: 36px;"></th>
                    <th>Website</th>
                    <th>Umgebung</th>
                    <th>Sicherheit</th>
                    <th>Core</th>
                    <th>Plugins</th>
                    <th>Themes</th>
                    <th>Sprachen</th>
                    <th>Letzter Check</th>
                    <th style="text-align: right;">Aktionen</th>
                </tr>
            </thead>
            <tbody id="sites-table-body">
                <!-- Content rendered via modules/wp_maintenance/app.js -->
            </tbody>
        </table>
    </div>
</div>

<!-- TAB 2: SETTINGS -->
<div id="tab-settings" class="tab-content" style="display: none;">
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Neue WordPress-Installation anlegen</h3>
        <form id="add-site-form" class="form-grid">
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="site_name" required placeholder="z. B. Kunde Mustermann">
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="url" id="site_url" required placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label>API Secret Key</label>
                <input type="text" id="api_key" required placeholder="Key aus Child-Plugin">
            </div>
            <button type="submit" class="btn">Hinzufügen</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>URL</th>
                    <th>API Key</th>
                    <th style="text-align: right;">Aktionen</th>
                </tr>
            </thead>
            <tbody id="settings-table-body">
                <!-- Rendered via modules/wp_maintenance/app.js -->
            </tbody>
        </table>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="edit-modal" class="modal-overlay">
    <div class="modal">
        <h3 style="margin-top:0; margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 600;">Website bearbeiten</h3>
        <form id="edit-site-form">
            <input type="hidden" id="edit_site_id">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Name</label>
                <input type="text" id="edit_site_name" required>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>URL</label>
                <input type="url" id="edit_site_url" required>
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>API Secret Key</label>
                <input type="text" id="edit_api_key" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Abbrechen</button>
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    </div>
</div>