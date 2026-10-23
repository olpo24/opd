let allBookmarks = [];
let activeCategory = 'all';

document.addEventListener('DOMContentLoaded', () => {
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

        const res = await fetch('modules/bookmarks/api.php?action=add_bookmark', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (res.ok) {
            closeBookmarkModal();
            e.target.reset();
            if (typeof loadHubSummary === 'function') loadHubSummary();
        }
    });
});

window.addEventListener('hubDataLoaded', (e) => {
    const bmData = e.detail.bookmarks;
    if (bmData) {
        allBookmarks = Array.isArray(bmData) ? bmData : (bmData.data || []);
        renderCategoryPills();
        renderFilteredBookmarks();
    }
});

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
        const matchesSearch = (bm.title || '').toLowerCase().includes(searchTerm) || 
                              (bm.url || '').toLowerCase().includes(searchTerm) || 
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
    await fetch(`modules/bookmarks/api.php?action=delete_bookmark&id=${id}`);
    if (typeof loadHubSummary === 'function') loadHubSummary();
}

function openAddBookmarkModal() { document.getElementById('bookmark-modal')?.classList.add('active'); }
function closeBookmarkModal() { document.getElementById('bookmark-modal')?.classList.remove('active'); }
