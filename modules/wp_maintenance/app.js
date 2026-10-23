let globalSitesData = [];

// Hilfsfunktion: Schreibt die Werte direkt in die DOM-Elemente des Widgets
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

// 1. Event-Listener für das zentrale hubDataLoaded Event
window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail) {
        // Prüft sowohl wp_maintenance als auch wp als Key
        const wpData = e.detail.wp_maintenance || e.detail.wp;
        if (wpData) {
            renderWpWidgetData(wpData);
        }
    }
});

// 2. Event-Listener beim Wechsel zur WP-Detailansicht
window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'wp_maintenance') {
        loadSites();
    }
});

// 3. Beim Laden der Seite direkt als Fallback das Summary abfragen und eintragen
document.addEventListener('DOMContentLoaded', () => {
    fetch('modules/wp_maintenance/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                renderWpWidgetData(res.data);
            }
        })
        .catch(err => console.error('WP Summary Fetch Error:', err));
});
