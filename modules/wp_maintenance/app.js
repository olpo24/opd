let globalSitesData = [];

function renderWpWidgetData(data) {
    if (!data) return;

    const sites = data.sites ?? data.total_sites ?? 0;
    const updates = data.updates ?? data.total_updates ?? 0;
    const vulns = data.vulnerabilities ?? data.total_vulnerabilities ?? 0;

    const sitesEl = document.getElementById('hub-wp-sites');
    const updatesEl = document.getElementById('hub-wp-updates');
    const vulnsEl = document.getElementById('hub-wp-vulns');

    if (sitesEl) sitesEl.textContent = sites;
    if (updatesEl) updatesEl.textContent = updates;
    if (vulnsEl) vulnsEl.textContent = vulns;
}

window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail) {
        const wpData = e.detail.wp_maintenance || e.detail.wp;
        if (wpData) {
            renderWpWidgetData(wpData);
        }
    }
});

window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'wp_maintenance') {
        loadSites();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    loadSites();
    fetch('modules/wp_maintenance/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                renderWpWidgetData(res.data);
            }
        })
        .catch(err => console.error('WP Summary Fetch Error:', err));
});

async function loadSites() {
    try {
        const res = await fetch('modules/wp_maintenance/api.php?action=get_sites');
        if (!res.ok) return;
        const response = await res.json();
        
        if (response.status === 'success' && Array.isArray(response.data)) {
            globalSitesData = response.data;
            renderDashboardTable(globalSitesData);
            renderSettingsTable(globalSitesData);
        }
    } catch (e) {
        console.error('Fehler beim Laden der Seiten:', e);
    }
}

function renderDashboardTable(sites) {
    const tbody = document.getElementById('sites-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!Array.isArray(sites) || sites.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="table-empty-cell">Keine WordPress-Installationen vorhanden.</td></tr>';
        return;
    }

    sites.forEach(site => {
        const updates = site.updates || {};
        const coreCount = Array.isArray(updates.core) ? updates.core.length : 0;
        const pluginCount = Array.isArray(updates.plugin) ? updates.plugin.length : 0;
        const themeCount = Array.isArray(updates.theme) ? updates.theme.length : 0;
        const transCount = Array.isArray(updates.translation) ? updates.translation.length : 0;
        const vulns = Array.isArray(site.vulnerabilities) ? site.vulnerabilities : [];
        const vulnCount = vulns.length;

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
                <strong class="site-name-text">${escapeHtml(site.name)}</strong><br>
                <a href="${escapeHtml(site.url)}" target="_blank" class="site-url-link" onclick="event.stopPropagation();">${escapeHtml(site.url)}</a>
            </td>
            <td class="site-meta-cell">
                WP ${escapeHtml(site.wp_version || '?')}<br>
                PHP ${escapeHtml(site.php_version || '?')}
            </td>
            <td>${securityBadge}</td>
            <td>${renderCountBadge(coreCount, 'badge-core')}</td>
            <td>${renderCountBadge(pluginCount, 'badge-updates')}</td>
            <td>${renderCountBadge(themeCount, 'badge-theme')}</td>
            <td>${renderCountBadge(transCount)}</td>
            <td class="site-meta-cell">${lastCheck}</td>
            <td class="table-actions-cell" onclick="event.stopPropagation();">
                <button onclick="syncSite(${site.id}, this)" class="btn btn-secondary btn-sm">Sync</button>
                <button onclick="autologinSite(${site.id})" class="btn btn-sm">Admin</button>
            </td>
        `;

        const trDetail = document.createElement('tr');
        trDetail.className = 'detail-row';
        trDetail.id = `detail-${site.id}`;

        trDetail.innerHTML = `
            <td colspan="10" class="detail-content">
                ${renderVulnerabilityAlert(vulns)}
                <div class="update-grid">
                    ${renderUpdateSection('Core Updates', updates.core, 'badge-core')}
                    ${renderUpdateSection('Plugin Updates', updates.plugin)}
                    ${renderUpdateSection('Theme Updates', updates.theme, 'badge-theme')}
                    ${renderUpdateSection('Übersetzungen', updates.translation)}
                </div>
            </td>
        `;

        tbody.appendChild(tr);
        tbody.appendChild(trDetail);
    });
}

function renderCountBadge(count, extraClass = '') {
    if (count === 0) return '<span class="badge badge-zero">0</span>';
    return `<span class="badge badge-updates ${extraClass}">${count}</span>`;
}

function renderUpdateSection(title, items, badgeClass = '') {
    const list = Array.isArray(items) ? items : [];
    if (list.length === 0) {
        return `
            <div class="update-card">
                <h4 class="update-card-title muted">${title}</h4>
                <p class="update-card-ok">✔ Keine offenen Updates</p>
            </div>`;
    }

    let listHtml = '';
    list.forEach(item => {
        let changelog = '';
        if (item.details_json) {
            try {
                const parsed = JSON.parse(item.details_json);
                if (parsed.changelog_url) {
                    changelog = ` <a href="${parsed.changelog_url}" target="_blank" class="changelog-link">Changelog</a>`;
                }
            } catch(e){}
        }

        listHtml += `
            <li class="update-item">
                <div><strong>${escapeHtml(item.item_name || 'Unbekannt')}</strong>${changelog}</div>
                <div class="update-version-text">
                    ${escapeHtml(item.current_version || '?')} &rarr; <strong class="text-success">${escapeHtml(item.new_version || '?')}</strong>
                </div>
            </li>`;
    });

    return `
        <div class="update-card">
            <h4 class="update-card-title">${title} <span class="badge ${badgeClass || 'badge-updates'}">${list.length}</span></h4>
            <ul class="update-list">${listHtml}</ul>
        </div>`;
}

function renderVulnerabilityAlert(vulnerabilities) {
    if (!Array.isArray(vulnerabilities) || vulnerabilities.length === 0) return '';

    let vulnHtml = '';
    vulnerabilities.forEach(v => {
        const scoreBadge = v.cvss_score 
            ? `<span class="cvss-score-badge">CVSS ${v.cvss_score}</span>` 
            : '';

        vulnHtml += `
            <li class="update-item vuln-item">
                <div>
                    <strong class="vuln-slug">[${escapeHtml((v.item_type || '').toUpperCase())}] ${escapeHtml(v.item_slug || '')}</strong> 
                    <span class="vuln-cve">(${escapeHtml(v.cve || '')})</span> ${scoreBadge}
                </div>
                <div class="vuln-title">${escapeHtml(v.title || '')}</div>
                <div class="vuln-fix-text">
                    Betrifft installierte Version &bull; <strong class="text-success">Gefixt ab Version: ${escapeHtml(v.fixed_in || 'Unbekannt')}</strong>
                </div>
            </li>`;
    });

    return `
        <div class="vuln-alert-box">
            <h4 class="vuln-alert-title">
                ⚠️ Bekannte Sicherheitslücken (WPVulnerability) <span class="badge badge-updates">${vulnerabilities.length}</span>
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

    if (!Array.isArray(sites)) return;

    sites.forEach(site => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${escapeHtml(site.name)}</strong></td>
            <td><a href="${escapeHtml(site.url)}" target="_blank" class="site-url-link">${escapeHtml(site.url)}</a></td>
            <td><code class="code-badge">${escapeHtml((site.api_key || '').substring(0, 8))}...</code></td>
            <td class="table-actions-cell">
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

    const btns = document.querySelectorAll('#view-wp_maintenance .nav-tabs .tab-btn');
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
