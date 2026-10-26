<div class="card">
    <div class="view-header">
        <div>
            <h2 class="view-title">System-Einstellungen</h2>
            <p class="view-subtitle">Verwalte Module, passe das Erscheinungsbild an und verwalte dein Profil.</p>
        </div>
        <!-- UNTER-TABS FÜR EINSTELLUNGEN -->
        <div class="nav-tabs">
            <button class="tab-btn active" id="st-tab-btn-modules" onclick="switchSettingsSubTab('modules')">Module</button>
            <button class="tab-btn" id="st-tab-btn-theme" onclick="switchSettingsSubTab('theme')">Theme & Styling</button>
            <button class="tab-btn" id="st-tab-btn-profile" onclick="switchSettingsSubTab('profile')">Benutzerprofil</button>
        </div>
    </div>

    <!-- SUB-TAB 1: MODUL-VERWALTUNG -->
    <div id="st-subtab-modules" class="tab-content active">
        <div class="settings-section-header">
            <h3 class="form-title">Installierte Module</h3>
            <button class="btn btn-sm" onclick="openModuleUploadModal()">+ Modul installieren (.zip)</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Modul</th>
                    <th>ID / Ordner</th>
                    <th>Status</th>
                    <th>Typ</th>
                    <th class="table-actions-cell">Aktionen</th>
                </tr>
            </thead>
            <tbody id="settings-modules-list">
                <!-- Dynamisch via app.js -->
            </tbody>
        </table>
    </div>

    <!-- SUB-TAB 2: CSS VARIABLEN & THEME CUSTOMIZER -->
    <div id="st-subtab-theme" class="tab-content">
        <div class="settings-section-header">
            <div>
                <h3 class="form-title">CSS-Variablen Customizer</h3>
                <p class="view-subtitle">Passe Farben und Design deines Dashboards in Echtzeit an.</p>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="resetThemeVariables()">Standards wiederherstellen</button>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label class="form-label">Hauptfarbe (Primary)</label>
                <input type="color" id="css-var-primary" onchange="updateCssVariable('--primary', this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Hintergrund (Body)</label>
                <input type="color" id="css-var-bg-body" onchange="updateCssVariable('--bg-body', this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Sidebar & Header</label>
                <input type="color" id="css-var-bg-sidebar" onchange="updateCssVariable('--bg-sidebar', this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Kacheln & Cards</label>
                <input type="color" id="css-var-bg-card" onchange="updateCssVariable('--bg-card', this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Rahmenfarbe (Borders)</label>
                <input type="color" id="css-var-border-color" onchange="updateCssVariable('--border-color', this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Textfarbe (Haupttext)</label>
                <input type="color" id="css-var-text-main" onchange="updateCssVariable('--text-main', this.value)">
            </div>
        </div>

        <div class="settings-footer-actions">
            <button class="btn" onclick="saveCustomTheme()">Theme-Einstellungen speichern</button>
        </div>
    </div>

    <!-- SUB-TAB 3: BENUTZERPROFIL & PASSWORT -->
    <div id="st-subtab-profile" class="tab-content">
        <div class="settings-profile-container">
            <h3 class="form-title">Profil & Sicherheit</h3>
            <form id="user-profile-form">
                <div class="form-group">
                    <label class="form-label">E-Mail Adresse</label>
                    <input type="email" id="profile_email" required>
                </div>
                <div class="form-group form-group-spaced">
                    <label class="form-label">Neues Passwort (leer lassen für keine Änderung)</label>
                    <input type="password" id="profile_password" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label">Passwort bestätigen</label>
                    <input type="password" id="profile_password_confirm" placeholder="••••••••">
                </div>
                <div class="form-group-spaced">
                    <button type="submit" class="btn">Profil aktualisieren</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL FÜR MODUL UPLOAD -->
<div id="module-upload-modal" class="modal-overlay">
    <div class="modal">
        <h3 class="modal-title">Neues Modul installieren</h3>
        <form id="module-upload-form" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Modul ZIP-Datei auswählen</label>
                <input type="file" id="module_zip" accept=".zip" required class="file-input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModuleUploadModal()">Abbrechen</button>
                <button type="submit" class="btn">Installieren</button>
            </div>
        </form>
    </div>
</div>
