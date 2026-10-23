let grid = null;
let cachedModulesData = null;

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initPasskeyButton();
    loadHubSummary();
});

async function loadHubSummary() {
    try {
        const res = await fetch('hub_api.php?action=get_widgets_summary');
        const response = await res.json();

        if (response.status === 'success') {
            cachedModulesData = response.modules || {};
            
            // Event senden
            dispatchHubData();

            initGridstack(response.layouts || {});
        }
    } catch (e) {
        console.error('Fehler beim Laden der Hub-Daten:', e);
    }
}

function dispatchHubData() {
    if (cachedModulesData) {
        window.dispatchEvent(new CustomEvent('hubDataLoaded', { detail: cachedModulesData }));
    }
}

// Falls Modul-Skripte erst nach dem Fetch bereitstehen, fordern sie die Daten aktiv an
window.addEventListener('requestHubData', () => {
    dispatchHubData();
});

function initGridstack(layouts = {}) {
    if (grid) return;

    grid = GridStack.init({
        column: 12,
        cellHeight: 80,
        margin: 12,
        handle: '.widget-title',
        animate: true
    });

    Object.keys(layouts).forEach(widgetId => {
        const layout = layouts[widgetId];
        const el = document.querySelector(`[gs-id="${widgetId}"]`);
        if (el && layout) {
            grid.update(el, { x: layout.x, y: layout.y, w: layout.w, h: layout.h });
        }
    });

    grid.on('change', function(event, items) {
        if (!items) return;
        const layoutData = items.map(item => ({
            id: item.el.getAttribute('gs-id'),
            x: item.x,
            y: item.y,
            w: item.w,
            h: item.h
        }));

        saveWidgetLayout(layoutData);
    });
}

async function saveWidgetLayout(layoutData) {
    try {
        await fetch('hub_api.php?action=save_widget_layout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(layoutData)
        });
    } catch (e) {
        console.error('Fehler beim Speichern des Layouts:', e);
    }
}

function switchView(viewName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.app-header .tab-btn').forEach(el => el.classList.remove('active'));

    const targetView = document.getElementById(`view-${viewName}`);
    const targetBtn = document.getElementById(`nav-btn-${viewName}`);

    if (targetView) targetView.classList.add('active');
    if (targetBtn) targetBtn.classList.add('active');

    window.dispatchEvent(new CustomEvent('hubViewSwitched', { detail: { view: viewName } }));

    if (viewName === 'hub') {
        if (grid) grid.onParentResize();
        loadHubSummary();
    }
}

function initPasskeyButton() {
    const btn = document.getElementById('btn-register-passkey');
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (typeof registerPasskey === 'function') {
            registerPasskey();
        } else {
            alert('Passkey Handler wird noch geladen. Bitte kurz warten.');
        }
    });
}

function escapeHtml(str) { 
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); 
}

// COLOR SCHEME TOGGLE LOGIC
const sunIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
const moonIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;

function initTheme() {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    let currentTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    applyTheme(currentTheme);

    toggleBtn.addEventListener('click', () => {
        currentTheme = (currentTheme === 'dark') ? 'light' : 'dark';
        applyTheme(currentTheme);
        localStorage.setItem('theme', currentTheme);
    });
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const toggleBtn = document.getElementById('theme-toggle');
    if (toggleBtn) toggleBtn.innerHTML = (theme === 'dark') ? sunIcon : moonIcon;
}
