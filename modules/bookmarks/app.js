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

// Event-Listener aus dem Core
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
    // Fallback Fetch beim Start
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

// 1. KERN-WIDGET AUF DER STARTSEITE RENDERN (Kompakte Ansicht)
function renderWidgetBookmarks() {
    const container = document.getElementById('hub-bookmarks-list');
    const countBadge = document.getElementById('bm-count-badge');

    if (!container) return;
    if (countBadge) countBadge.textContent = allBookmarks.length;

    container.innerHTML = '';

    if (allBookmarks.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem; margin:0; grid-column: 1/-1;">Keine Lesezeichen vorhanden.</p>';
        return;
    }

    const closeIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>`;

    allBookmarks.forEach(bm => {
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

// 2. KATEGORIE PILLS AUF DER DETAILSEITE
function renderCategoryPills() {
    const container = document.getElementById('bm-detail-category-pills');
    if (!container) return;

    const categories = ['all', ...new Set(allBookmarks.map(b => b.category || 'Allgemein'))];

    container.innerHTML = '';
    categories.forEach(cat => {
        const btn = document.createElement('button');
        const label = cat === 'all' ? 'Alle' : cat;
        btn.className = `btn btn-sm ${activeCategory === cat ? '' : 'btn-secondary'}`;
        btn.style.cssText = 'padding: 0.3rem 0.75rem; font-size: 0.8rem;';
        btn.textContent = label;
        btn.onclick = () => {
            activeCategory = cat;
            renderCategoryPills();
            renderFilteredCards();
        };
        container.appendChild(btn);
    });
}

// DETAILSEITE: VISUELLE PREVIEW-CARDS MIT LOKALEN SCREENSHOTS RENDERN
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
        container.innerHTML = '<p style="color:var(--text-muted); font-size:0.9rem; grid-column: 1/-1; padding: 2rem 0; text-align: center;">Keine Lesezeichen gefunden.</p>';
        return;
    }

    filtered.forEach(bm => {
        let domain = '';
        try { domain = new URL(bm.url).hostname; } catch (e) { domain = bm.url; }
        
        const faviconUrl = `https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}&sz=64`;
        
        // Lokaler Pfad zum ApiFlash-Screenshot
        const previewImgUrl = bm.icon_url 
            ? `modules/bookmarks/previews/${encodeURIComponent(bm.icon_url)}` 
            : null;

        const card = document.createElement('div');
        card.style.cssText = 'background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.15s ease, border-color 0.15s ease;';

        const imageHtml = previewImgUrl 
            ? `<img src="${previewImgUrl}" alt="${escapeHtml(bm.title)}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">`
            : '';

        card.innerHTML = `
            <div style="height: 150px; background: var(--bg-subtle); overflow: hidden; position: relative; border-bottom: 1px solid var(--border-color);">
                ${imageHtml}
                <div style="display: ${previewImgUrl ? 'none' : 'flex'}; width: 100%; height: 100%; align-items: center; justify-content: center; background: var(--bg-subtle); color: var(--text-dark); font-size: 0.8rem;">Kein Screenshot</div>
            </div>

            <div style="padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <img src="${faviconUrl}" alt="" style="width: 18px; height: 18px; border-radius: 3px;" onerror="this.style.display='none'">
                        <span class="badge badge-zero" style="font-size: 0.7rem;">${escapeHtml(bm.category || 'Allgemein')}</span>
                    </div>

                    <h4 style="margin: 0 0 0.35rem 0; font-size: 0.95rem; font-weight: 600; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        ${escapeHtml(bm.title)}
                    </h4>

                    <a href="${escapeHtml(bm.url)}" target="_blank" style="color: var(--text-muted); font-size: 0.75rem; text-decoration: none; word-break: break-all; display: block; margin-bottom: 1rem;">
                        ${escapeHtml(bm.url)}
                    </a>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                    <a href="${escapeHtml(bm.url)}" target="_blank" class="btn btn-sm">Öffnen &rarr;</a>
                    <button onclick="deleteBookmark(${bm.id})" class="btn btn-sm btn-secondary btn-danger">Löschen</button>
                </div>
            </div>
        `;

        card.addEventListener('mouseenter', () => card.style.borderColor = 'var(--primary)');
        card.addEventListener('mouseleave', () => card.style.borderColor = 'var(--border-color)');

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
