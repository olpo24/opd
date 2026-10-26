let allBookmarks = [];
let activeCategory = 'all';

function processBookmarksData(bmData) {
    if (!bmData) return;

    if (Array.isArray(bmData)) {
        allBookmarks = bmData;
    } else if (bmData.data && Array.isArray(bmData.data)) {
        allBookmarks = bmData.data;
    } else {
        allBookmarks = [];
    }

    renderWidgetBookmarks();
    renderCategoryPills();
    renderFilteredCards();
}

window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail && e.detail.bookmarks) {
        processBookmarksData(e.detail.bookmarks);
    }
});

window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'bookmarks') {
        reloadBookmarks();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    fetch('modules/bookmarks/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                processBookmarksData(res.data);
            }
        })
        .catch(err => console.error('Bookmarks Fetch Error:', err));

    document.getElementById('bm-detail-search')?.addEventListener('input', () => {
        renderFilteredCards();
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
            reloadBookmarks();
        }
    });
});

async function reloadBookmarks() {
    try {
        const res = await fetch('modules/bookmarks/api.php?action=get_summary');
        const response = await res.json();
        if (response.status === 'success' && response.data) {
            processBookmarksData(response.data);
        }
    } catch (e) {
        console.error('Fehler beim Neuladen der Bookmarks:', e);
    }
}

function renderWidgetBookmarks() {
    const container = document.getElementById('hub-bookmarks-list');
    const countBadge = document.getElementById('bm-count-badge');

    if (!container) return;
    if (countBadge) countBadge.textContent = allBookmarks.length;

    container.innerHTML = '';

    if (allBookmarks.length === 0) {
        container.innerHTML = '<p class="bm-empty-text">Keine Lesezeichen vorhanden.</p>';
        return;
    }

    const closeIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>`;

    allBookmarks.forEach(bm => {
        let domain = '';
        try { domain = new URL(bm.url).hostname; } catch (e) { domain = bm.url; }
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=32`;

        const item = document.createElement('div');
        item.className = 'bm-item';

        item.innerHTML = `
            <a href="${escapeHtml(bm.url)}" target="_blank" class="bm-link">
                <img src="${faviconUrl}" alt="" class="bm-favicon" onerror="this.style.display='none'">
                <span class="bm-title-text">${escapeHtml(bm.title)}</span>
            </a>
            <button onclick="deleteBookmark(${bm.id})" class="bm-delete-btn" title="Löschen">
                ${closeIconSvg}
            </button>
        `;

        container.appendChild(item);
    });
}

function renderCategoryPills() {
    const container = document.getElementById('bm-detail-category-pills');
    if (!container) return;

    const categories = ['all', ...new Set(allBookmarks.map(b => b.category || 'Allgemein'))];

    container.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        const label = cat === 'all' ? 'Alle' : cat;
        btn.className = `btn btn-sm ${activeCategory === cat ? '' : 'btn-secondary'}`;
        btn.textContent = label;
        btn.onclick = () => {
            activeCategory = cat;
            renderCategoryPills();
            renderFilteredCards();
        };
        container.appendChild(btn);
    });
}

function renderFilteredCards() {
    const container = document.getElementById('bm-detail-cards-grid');
    const searchTerm = (document.getElementById('bm-detail-search')?.value || '').toLowerCase().trim();

    if (!container) return;

    const filtered = allBookmarks.filter(bm => {
        const matchesCategory = (activeCategory === 'all') || (bm.category === activeCategory);
        const matchesSearch = (bm.title || '').toLowerCase().includes(searchTerm) || 
                              (bm.url || '').toLowerCase().includes(searchTerm) || 
                              (bm.category && bm.category.toLowerCase().includes(searchTerm));
        return matchesCategory && matchesSearch;
    });

    container.innerHTML = '';

    if (filtered.length === 0) {
        container.innerHTML = '<p class="bm-empty-text full-width">Keine Lesezeichen gefunden.</p>';
        return;
    }

    filtered.forEach(bm => {
        let domain = '';
        try { domain = new URL(bm.url).hostname; } catch (e) { domain = bm.url; }
        
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=64`;
        const previewImgUrl = bm.icon_url ? `modules/bookmarks/previews/${encodeURIComponent(bm.icon_url)}` : null;

        const card = document.createElement('div');
        card.className = 'bm-card';

        const imageHtml = previewImgUrl 
            ? `<img src="${previewImgUrl}" alt="${escapeHtml(bm.title)}">`
            : '';

        card.innerHTML = `
            <div class="bm-card-preview">
                ${imageHtml}
                <div class="bm-card-no-preview" style="${previewImgUrl ? 'display:none;' : ''}">Kein Screenshot</div>
            </div>

            <div class="bm-card-body">
                <div>
                    <div class="bm-card-header">
                        <img src="${faviconUrl}" alt="" class="bm-favicon" onerror="this.style.display='none'">
                        <span class="badge badge-zero">${escapeHtml(bm.category || 'Allgemein')}</span>
                    </div>

                    <h4 class="bm-card-title">${escapeHtml(bm.title)}</h4>
                    <a href="${escapeHtml(bm.url)}" target="_blank" class="bm-card-url">${escapeHtml(bm.url)}</a>
                </div>

                <div class="bm-card-footer">
                    <a href="${escapeHtml(bm.url)}" target="_blank" class="btn btn-sm">Öffnen &rarr;</a>
                    <button onclick="deleteBookmark(${bm.id})" class="btn btn-sm btn-danger">Löschen</button>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

async function deleteBookmark(id) {
    if (!confirm('Lesezeichen entfernen?')) return;
    await fetch(`modules/bookmarks/api.php?action=delete_bookmark&id=${id}`);
    reloadBookmarks();
}

function openAddBookmarkModal() { document.getElementById('bookmark-modal')?.classList.add('active'); }
function closeBookmarkModal() { document.getElementById('bookmark-modal')?.classList.remove('active'); }
function escapeHtml(str) { return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
