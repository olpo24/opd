let globalSitesData = [];

document.addEventListener('DOMContentLoaded', () => {
    loadSites();

    document.getElementById('add-site-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            name: document.getElementById('site_name').value,
            url: document.getElementById('site_url').value,
            api_key: document.getElementById('api_key').value
        };

        const res = await fetch('modules/wp_maintenance/api.php?action=add_site', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (res.ok) {
            e.target.reset();
            loadSites();
            switchTab('dashboard');
        }
    });

    document.getElementById('edit-site-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            id: document.getElementById('edit_site_id').value,
            name: document.getElementById('edit_site_name').value,
            url: document.getElementById('edit_site_url').value,
            api_key: document.getElementById('edit_api_key').value
        };

        const res = await fetch('modules/wp_maintenance/api.php?action=edit_site', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (res.ok) {
            closeEditModal();
            loadSites();
        }
    });
});

async function loadSites() {
    try {
        const res = await fetch('modules/wp_maintenance/api.php?action=get_sites');
        if (!res.ok) {
            console.error('API Aufruf fehlgeschlagen mit Status:', res.status);
            return;
        }
        const response = await res.json();
        
        if (response.status === 'success' && Array.isArray(response.data)) {
            globalSitesData = response.data;
            renderDashboardTable(globalSitesData);
            renderSettingsTable(globalSitesData);
        } else {
            console.error('Unerwartetes API-Format:', response);
        }
    } catch (e) {
        console.error('Fehler beim Laden der Seiten:', e);
    }
}

function renderDashboardTable(sites) {
    const tbody = document.getElementById('sites-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (sites.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; color: var(--text-muted); padding: 2rem;">Keine WordPress-Installationen vorhanden.</td></tr>';
        return;
    }

    sites.forEach(site => {
        const coreCount = site.updates?.core?.length || 0;
        const pluginCount = site.updates?.plugin?.length || 0;
        const themeCount = site.updates?.theme?.length || 0;
        const transCount = site.updates?.translation?.length || 0;
        const vulnCount = site.vulnerabilities?.length || 0;

        const lastCheck = site.last_check ? new Date(site.last_check).toLocaleString('de-DE') : 'Noch nie';

        const securityBadge = vulnCount > 0
            ? `<span class="badge badge-updates">${vulnCount} CVE</span>`
            : `<span class="badge badge-success">Sicher</span>`;

        const chevronSvg = `<svg class="chevron" id="arrow-${site.id}" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.16 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
        </svg>`;

        const tr = document.createElement('tr');
        tr.className = 'site-row';
        tr.id = `row-site-${site.id}`;
        tr.onclick = (e) => {
            if (e.target.closest('button') || e.target.closest('a')) return;
            toggleAccordion(site.id);
        };

        tr.innerHTML = `
            <td>${chevronSvg}</td>
            <td>
                <strong style="color:var(--text-main);">${escapeHtml(site.name)}</strong><br>
                <a href="${escapeHtml(site.url)}" target="_blank" style="color:var(--text-muted); font-size:0.8rem; text-decoration:none;" onclick="event.stopPropagation();">${escapeHtml(site.url)}</a>
            </td>
            <td style="font-size:0.85rem; color:var(--text-muted);">
                WP ${escapeHtml(site.wp_version || '?')}<br>
                PHP ${escapeHtml(site.php_version || '?')}
            </td>
            <td>${securityBadge}</td>
            <td>${renderCountBadge(coreCount, 'badge-core')}</td>
            <td>${renderCountBadge(pluginCount, 'badge-updates')}</td>
            <td>${renderCountBadge(themeCount, 'badge-theme')}</td>
            <td>${renderCountBadge(transCount)}</td>
            <td style="font-size:0.85rem; color:var(--text-muted);">${lastCheck}</td>
            <td style="text-align: right;" onclick="event.stopPropagation();">
                <button onclick="syncSite(${site.id}, this)" class="btn btn-secondary btn-sm">Sync</button>
                <button onclick="autologinSite(${site.id})" class="btn btn-sm">Admin</button>
            </td>
        `;

        const trDetail = document.createElement('tr');
        trDetail.className = 'detail-row';
        trDetail.id = `detail-${site.id}`;

        trDetail.innerHTML = `
            <td colspan="10" class="detail-content">
                ${renderVulnerabilityAlert(site.vulnerabilities)}
                <div class="update-grid" style="margin-top: 1rem;">
                    ${renderUpdateSection('Core Updates', site.updates?.core, 'badge-core')}
                    ${renderUpdateSection('Plugin Updates', site.updates?.plugin)}
                    ${renderUpdateSection('Theme Updates', site.updates?.theme, 'badge-theme')}
                    ${renderUpdateSection('Übersetzungen', site.updates?.translation)}
                </div>
            </td>
        `;

        tbody.appendChild(tr);
        tbody.appendChild(trDetail);
    });
}

function renderCountBadge(count, extraClass = '') {
    if (count === 0) {
        return '<span class="badge badge-zero">0</span>';
    }
    return `<span class="badge badge-updates ${extraClass}">${count}</span>`;
}

function renderUpdateSection(title, items, badgeClass = '') {
    if (!items || items.length === 0) {
        return `
            <div class="update-card">
                <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem; color:var(--text-muted);">${title}</h4>
                <p style="color:var(--status-success-text); font-size:0.85rem; margin:0;">✔ Keine offenen Updates</p>
            </div>`;
    }

    let listHtml = '';
    items.forEach(item => {
        let changelog = '';
        if (item.details_json) {
            try {
                const parsed = JSON.parse(item.details_json);
                if (parsed.changelog_url) {
                    changelog = ` <a href="${parsed.changelog_url}" target="_blank" style="color:var(--primary); font-size:0.75rem;">Changelog</a>`;
                }
            } catch(e){}
        }

        listHtml += `
            <li class="update-item">
                <div><strong>${escapeHtml(item.item_name)}</strong>${changelog}</div>
                <div style="color:var(--text-muted); font-size:0.8rem;">
                    ${escapeHtml(item.current_version || '?')} &rarr; <strong style="color:var(--status-success-text);">${escapeHtml(item.new_version || '?')}</strong>
                </div>
            </li>`;
    });

    return `
        <div class="update-card">
            <h4 style="margin:0 0 0.5rem 0; font-size:0.9rem;">${title} <span class="badge ${badgeClass || 'badge-updates'}">${items.length}</span></h4>
            <ul class="update-list">${listHtml}</ul>
        </div>`;
}

function renderVulnerabilityAlert(vulnerabilities) {
    if (!vulnerabilities || vulnerabilities.length === 0) return '';

    let vulnHtml = '';
    vulnerabilities.forEach(v => {
        const scoreBadge = v.cvss_score 
            ? `<span style="background:#991b1b; color:#fca5a5; padding:0.1rem 0.4rem; border-radius:4px; font-size:0.75rem; font-weight:bold;">CVSS ${v.cvss_score}</span>` 
            : '';

        vulnHtml += `
            <li class="update-item" style="border-color: #7f1d1d;">
                <div>
                    <strong style="color:#f87171;">[${escapeHtml(v.item_type.toUpperCase())}] ${escapeHtml(v.item_slug)}</strong> 
                    <span style="color:var(--text-muted); font-size:0.8rem;">(${escapeHtml(v.cve)})</span> ${scoreBadge}
                </div>
                <div style="font-size:0.85rem; margin-top:0.2rem;">${escapeHtml(v.title)}</div>
                <div style="color:var(--text-muted); font-size:0.8rem; margin-top:0.1rem;">
                    Betrifft installierte Version &bull; <strong style="color:#10b981;">Gefixt ab Version: ${escapeHtml(v.fixed_in || 'Unbekannt')}</strong>
                </div>
            </li>`;
    });

    return `
        <div style="background:var(--status-danger-bg); border: 1px solid rgba(239, 68, 68, 0.4); border-radius:6px; padding:1rem; margin-bottom:1rem;">
            <h4 style="margin:0 0 0.5rem 0; color:var(--status-danger-text); font-size:0.95rem; display:flex; align-items:center; gap:0.5rem;">
                ⚠️ Bekannte Sicherheitslücken (WPVulnerability) <span class="badge" style="background:#ef4444; color:white;">${vulnerabilities.length}</span>
            </h4>
            <ul class="update-list">${vulnHtml}</ul>
        </div>`;
}

function toggleAccordion(siteId) {
    const detailRow = document.getElementById(`detail-${siteId}`);
    const siteRow = document.getElementById(`row-site-${siteId}`);

    if (!detailRow || !siteRow) return;

    const isOpen = detailRow.classList.contains('active');

    if (isOpen) {
        detailRow.classList.remove('active');
        siteRow.classList.remove('open');
    } else {
        detailRow.classList.add('active');
        siteRow.classList.add('open');
    }
}

function renderSettingsTable(sites) {
    const tbody = document.getElementById('settings-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    sites.forEach(site => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${escapeHtml(site.name)}</strong></td>
            <td><a href="${escapeHtml(site.url)}" target="_blank" style="color:var(--text-main);">${escapeHtml(site.url)}</a></td>
            <td><code style="background:var(--bg-body); padding:0.2rem 0.4rem; border-radius:4px; font-size:0.85rem;">${escapeHtml(site.api_key.substring(0, 8))}...</code></td>
            <td style="text-align: right;">
                <button onclick="openEditModal(${site.id})" class="btn btn-secondary btn-sm">Bearbeiten</button>
                <button onclick="deleteSite(${site.id})" class="btn btn-danger btn-sm">Löschen</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function switchTab(tabName) {
    const dashboardTab = document.getElementById('tab-dashboard');
    const settingsTab = document.getElementById('tab-settings');

    if (!dashboardTab || !settingsTab) return;

    if (tabName === 'dashboard') {
        dashboardTab.style.display = 'block';
        settingsTab.style.display = 'none';
    } else if (tabName === 'settings') {
        dashboardTab.style.display = 'none';
        settingsTab.style.display = 'block';
    }

    const btns = document.querySelectorAll('#view-wp .nav-tabs .tab-btn');
    btns.forEach(btn => btn.classList.remove('active'));
    if (tabName === 'dashboard' && btns[0]) btns[0].classList.add('active');
    if (tabName === 'settings' && btns[1]) btns[1].classList.add('active');
}

function openEditModal(siteId) {
    const site = globalSitesData.find(s => s.id == siteId);
    if (!site) return;

    document.getElementById('edit_site_id').value = site.id;
    document.getElementById('edit_site_name').value = site.name;
    document.getElementById('edit_site_url').value = site.url;
    document.getElementById('edit_api_key').value = site.api_key;

    document.getElementById('edit-modal')?.classList.add('active');
}

function closeEditModal() {
    document.getElementById('edit-modal')?.classList.remove('active');
}

async function syncSite(id, btn) {
    btn.disabled = true;
    btn.textContent = 'Check...';
    try {
        const res = await fetch(`modules/wp_maintenance/api.php?action=sync_site&id=${id}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            loadSites();
        } else {
            alert('Fehler beim Aktualisieren: ' + (data.message || 'Unbekannter Serverfehler'));
        }
    } catch (e) {
        alert('Fehler beim Verarbeiten der Antwort.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sync';
    }
}

async function autologinSite(id) {
    try {
        const res = await fetch(`modules/wp_maintenance/api.php?action=generate_autologin&id=${id}`);
        const data = await res.json();
        if (data.status === 'success' && data.url) {
            window.open(data.url, '_blank');
        } else {
            alert('Login fehlgeschlagen: ' + (data.message || 'Unbekannter Fehler'));
        }
    } catch (e) {
        alert('Fehler beim Anfordern des Login-Links.');
    }
}

async function deleteSite(id) {
    if (!confirm('Soll diese Website wirklich aus dem Dashboard entfernt werden?')) return;
    await fetch(`modules/wp_maintenance/api.php?action=delete_site&id=${id}`);
    loadSites();
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}