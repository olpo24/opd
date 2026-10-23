let globalSitesData = [];

// Hilfsfunktion zum Aktualisieren der Widget-Zahlen im DOM
function updateWpWidget(data) {
    if (!data) return;
    const sitesEl = document.getElementById('hub-wp-sites');
    const updatesEl = document.getElementById('hub-wp-updates');
    const vulnsEl = document.getElementById('hub-wp-vulns');

    if (sitesEl) sitesEl.textContent = data.sites ?? 0;
    if (updatesEl) updatesEl.textContent = data.updates ?? 0;
    if (vulnsEl) vulnsEl.textContent = data.vulnerabilities ?? 0;
}

// 1. Reagieren auf Event von hub_api.php
window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail && e.detail.wp_maintenance) {
        updateWpWidget(e.detail.wp_maintenance);
    }
});

// 2. Reagieren auf View-Switching zur WP Detailansicht
window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'wp_maintenance') {
        loadSites();
    }
});

// Fallback: Daten direkt beim DOM-Laden per API holen, falls das Event verpasst wurde
document.addEventListener('DOMContentLoaded', () => {
    fetch('modules/wp_maintenance/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                updateWpWidget(res.data);
            }
        })
        .catch(err => console.error('WP Summary Fetch Error:', err));
});
