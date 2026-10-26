window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'settings') {
        loadSettingsModules();
        loadSettingsProfile();
        loadSettingsThemeVars();
    }
});

function switchSettingsSubTab(tab) {
    document.querySelectorAll('#view-settings .tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('#view-settings .nav-tabs .tab-btn').forEach(el => el.classList.remove('active'));

    const targetSubTab = document.getElementById(`st-subtab-${tab}`);
    const targetBtn = document.getElementById(`st-tab-btn-${tab}`);

    if (targetSubTab) targetSubTab.style.display = 'block';
    if (targetBtn) targetBtn.classList.add('active');
}

// 1. MODULE MANAGING
async function loadSettingsModules() {
    const tbody = document.getElementById('settings-modules-list');
    if (!tbody) return;

    try {
        const res = await fetch('modules/settings/api.php?action=get_modules');
        const response = await res.json();

        if (response.status === 'success' && Array.isArray(response.data)) {
            tbody.innerHTML = '';
            response.data.forEach(m => {
                const tr = document.createElement('tr');
                const isSystem = m.folder === 'settings';
                const statusBadge = m.enabled 
                    ? '<span class="badge badge-success">Aktiv</span>' 
                    : '<span class="badge badge-zero">Deaktiviert</span>';

                tr.innerHTML = `
                    <td><strong>${escapeHtml(m.name || m.folder)}</strong></td>
                    <td><code>modules/${escapeHtml(m.folder)}</code></td>
                    <td>${statusBadge}</td>
                    <td>${m.has_view ? 'View & Widget' : 'Nur Widget'}</td>
                    <td class="table-actions-cell">
                        ${!isSystem ? `<button onclick="toggleModule('${escapeHtml(m.folder)}')" class="btn btn-sm btn-secondary">${m.enabled ? 'Deaktivieren' : 'Aktivieren'}</button>` : '<span class="text-muted">System</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Fehler beim Laden der Module:', e);
    }
}

async function toggleModule(folder) {
    try {
        const res = await fetch(`modules/settings/api.php?action=toggle_module&folder=${encodeURIComponent(folder)}`);
        const data = await res.json();
        if (data.status === 'success') {
            // Seite neu laden, damit index.php und Sidebar die Modul-Liste neu aufbauen
            window.location.reload();
        } else {
            alert('Fehler beim Ändern des Modul-Status: ' + (data.message || 'Unbekannter Fehler'));
        }
    } catch (e) {
        console.error('Fehler beim Umschalten des Moduls:', e);
    }
}

// 2. THEME & CSS VARIABLES CUSTOMIZER
function loadSettingsThemeVars() {
    const rootStyle = getComputedStyle(document.documentElement);
    
    const primary = rootStyle.getPropertyValue('--primary').trim() || '#6366f1';
    const bgBody = rootStyle.getPropertyValue('--bg-body').trim() || '#0f172a';
    const bgSidebar = rootStyle.getPropertyValue('--bg-sidebar').trim() || '#1e293b';
    const bgCard = rootStyle.getPropertyValue('--bg-card').trim() || '#1e293b';
    const borderColor = rootStyle.getPropertyValue('--border-color').trim() || '#334155';
    const textMain = rootStyle.getPropertyValue('--text-main').trim() || '#f8fafc';

    if (document.getElementById('css-var-primary')) document.getElementById('css-var-primary').value = rgbToHex(primary);
    if (document.getElementById('css-var-bg-body')) document.getElementById('css-var-bg-body').value = rgbToHex(bgBody);
    if (document.getElementById('css-var-bg-sidebar')) document.getElementById('css-var-bg-sidebar').value = rgbToHex(bgSidebar);
    if (document.getElementById('css-var-bg-card')) document.getElementById('css-var-bg-card').value = rgbToHex(bgCard);
    if (document.getElementById('css-var-border-color')) document.getElementById('css-var-border-color').value = rgbToHex(borderColor);
    if (document.getElementById('css-var-text-main')) document.getElementById('css-var-text-main').value = rgbToHex(textMain);
}

function updateCssVariable(varName, value) {
    document.documentElement.style.setProperty(varName, value);
}

function saveCustomTheme() {
    const vars = {
        '--primary': document.getElementById('css-var-primary').value,
        '--bg-body': document.getElementById('css-var-bg-body').value,
        '--bg-sidebar': document.getElementById('css-var-bg-sidebar').value,
        '--bg-card': document.getElementById('css-var-bg-card').value,
        '--border-color': document.getElementById('css-var-border-color').value,
        '--text-main': document.getElementById('css-var-text-main').value
    };

    localStorage.setItem('custom_theme_vars', JSON.stringify(vars));
    alert('Theme-Einstellungen erfolgreich lokal gespeichert!');
}

function resetThemeVariables() {
    localStorage.removeItem('custom_theme_vars');
    location.reload();
}

// Beim Seitenstart abgespeicherte Custom CSS Variables anwenden
(function applySavedThemeVars() {
    const saved = localStorage.getItem('custom_theme_vars');
    if (saved) {
        try {
            const vars = JSON.parse(saved);
            Object.keys(vars).forEach(k => {
                document.documentElement.style.setProperty(k, vars[k]);
            });
        } catch(e){}
    }
})();

// 3. USER PROFILE & SECURITY
async function loadSettingsProfile() {
    try {
        const res = await fetch('modules/settings/api.php?action=get_profile');
        const response = await res.json();
        if (response.status === 'success' && response.data) {
            if (document.getElementById('profile_email')) document.getElementById('profile_email').value = response.data.email || '';
        }
    } catch(e){}
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('user-profile-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('profile_email').value;
        const pass = document.getElementById('profile_password').value;
        const passConfirm = document.getElementById('profile_password_confirm').value;

        if (pass && pass !== passConfirm) {
            alert('Die eingegebenen Passwörter stimmen nicht überein!');
            return;
        }

        const res = await fetch('modules/settings/api.php?action=update_profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: pass })
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
            alert(data.message || 'Profil aktualisiert!');
            document.getElementById('profile_password').value = '';
            document.getElementById('profile_password_confirm').value = '';
        } else {
            alert('Fehler: ' + (data.message || 'Konnte Profil nicht aktualisieren.'));
        }
    });
});

function openModuleUploadModal() { document.getElementById('module-upload-modal')?.classList.add('active'); }
function closeModuleUploadModal() { document.getElementById('module-upload-modal')?.classList.remove('active'); }

function rgbToHex(col) {
    if (col.startsWith('#')) return col;
    const matches = col.match(/\d+/g);
    if (!matches || matches.length < 3) return '#000000';
    return "#" + ((1 << 24) + (parseInt(matches[0]) << 16) + (parseInt(matches[1]) << 8) + parseInt(matches[2])).toString(16).slice(1);
}
