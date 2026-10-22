let allBookmarks = [];
let activeCategory = 'all';
let grid = null;

document.addEventListener('DOMContentLoaded', () => {
    loadHubSummary();
    initTheme();

    document.getElementById('bm-search')?.addEventListener('input', () => {
        renderFilteredBookmarks();
    });

    document.getElementById('add-bookmark-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            title: document.getElementById('bm_title').value,
            url: document.getElementById('bm_url').value,
            category: document.getElementById('bm_category').value || 'Allgemein'
        };

        const res = await fetch('hub_api.php?action=add_bookmark', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (res.ok) {
            closeBookmarkModal();
            e.target.reset();
            loadHubSummary();
        }
    });
});

async function loadHubSummary() {
    try {
        const res = await fetch('hub_api.php?action=get_widgets_summary');
        const response = await res.json();

        if (response.status === 'success') {
            // Automatisch gerenderte Modul-Widgets befüllen
            const modulesData = response.modules || {};
            
            // Event senden, damit alle geladenen Modul-Skripte ihre Widget-UI aktualisieren können
            window.dispatchEvent(new CustomEvent('hubDataLoaded', { detail: modulesData }));

            allBookmarks = response.modules.bookmarks || [];
            renderCategoryPills();
            renderFilteredBookmarks();

            initGridstack(response.modules.layouts || {});
        }
    } catch (e) {
        console.error('Fehler beim Laden der Hub-Daten:', e);
    }
}

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

function renderCategoryPills() {
    const container = document.getElementById('bm-category-pills');
    if (!container) return;

    const categories = ['all', ...new Set(allBookmarks.map(b => b.category || 'Allgemein'))];

    container.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        const label = cat === 'all' ? 'Alle' : cat;
        btn.className = `btn btn-sm ${activeCategory === cat ? '' : 'btn-secondary'}`;
        btn.style.cssText = 'padding: 0.25rem 0.6rem; font-size: 0.75rem;';
        btn.textContent = label;
        btn.onclick = () => {
            activeCategory = cat;
            renderCategoryPills();
            renderFilteredBookmarks();
        };
        container.appendChild(btn);
    });
}

function renderFilteredBookmarks() {
    const container = document.getElementById('hub-bookmarks-list');
    const countBadge = document.getElementById('bm-count-badge');
    const searchTerm = (document.getElementById('bm-search')?.value || '').toLowerCase().trim();

    if (!container) return;

    const filtered = allBookmarks.filter(bm => {
        const matchesCategory = (activeCategory === 'all') || (bm.category === activeCategory);
        const matchesSearch = bm.title.toLowerCase().includes(searchTerm) || 
                              bm.url.toLowerCase().includes(searchTerm) || 
                              (bm.category && bm.category.toLowerCase().includes(searchTerm));
        return matchesCategory && matchesSearch;
    });

    if (countBadge) countBadge.textContent = filtered.length;
    container.innerHTML = '';

    if (filtered.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem; margin:0; grid-column: 1/-1;">Keine Lesezeichen gefunden.</p>';
        return;
    }

    const closeIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>`;

    filtered.forEach(bm => {
        let domain = '';
        try { domain = new URL(bm.url).hostname; } catch (e) { domain = bm.url; }
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=32`;

        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: 8px; transition: border-color 0.15s ease;';

        item.innerHTML = `
            <a href="${escapeHtml(bm.url)}" target="_blank" style="display: flex; align-items: center; gap: 0.6rem; color: var(--text-main); text-decoration: none; font-size: 0.85rem; font-weight: 500; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex: 1;">
                <img src="${faviconUrl}" alt="" style="width: 16px; height: 16px; border-radius: 3px; flex-shrink: 0;" onerror="this.style.display='none'">
                <span style="overflow: hidden; text-overflow: ellipsis;">${escapeHtml(bm.title)}</span>
            </a>
            <button onclick="deleteBookmark(${bm.id})" style="background: none; border: none; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; padding: 0.2rem; margin-left: 0.4rem;" title="Löschen">
                ${closeIconSvg}
            </button>
        `;

        item.addEventListener('mouseenter', () => item.style.borderColor = 'var(--primary)');
        item.addEventListener('mouseleave', () => item.style.borderColor = 'var(--border-color)');
        container.appendChild(item);
    });
}

async function deleteBookmark(id) {
    if (!confirm('Lesezeichen entfernen?')) return;
    await fetch(`hub_api.php?action=delete_bookmark&id=${id}`);
    loadHubSummary();
}

// GENERISCHER SEITENWECHSEL FÜR ALLE MODULE
function switchView(viewName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.app-header .tab-btn').forEach(el => el.classList.remove('active'));

    const targetView = document.getElementById(`view-${viewName}`);
    const targetBtn = document.getElementById(`nav-btn-${viewName}`);

    if (targetView) targetView.classList.add('active');
    if (targetBtn) targetBtn.classList.add('active');

    // Modul-Event absenden, wenn Ansicht geöffnet wird
    window.dispatchEvent(new CustomEvent('hubViewSwitched', { detail: { view: viewName } }));

    if (viewName === 'hub') {
        if (grid) grid.onParentResize();
        loadHubSummary();
    }
}

function openAddBookmarkModal() { document.getElementById('bookmark-modal')?.classList.add('active'); }
function closeBookmarkModal() { document.getElementById('bookmark-modal')?.classList.remove('active'); }
function escapeHtml(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

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
