<div class="card" style="padding: 1.5rem;">
    <!-- HEADER DER DETAILSEITE -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Lesezeichen Übersicht</h2>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.85rem;">Verwalte deine gespeicherten Links und Werkzeuge.</p>
        </div>
        <button class="btn" onclick="openAddBookmarkModal()">+ Neues Lesezeichen</button>
    </div>

    <!-- FILTER & SUCHE -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; align-items: center; background: var(--bg-subtle); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" id="bm-detail-search" placeholder="🔍 Lesezeichen durchsuchen..." style="width: 100%; padding: 0.55rem 0.85rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.875rem; outline: none;">
        </div>
        <div id="bm-detail-category-pills" style="display: flex; gap: 0.4rem; flex-wrap: wrap;"></div>
    </div>

    <!-- CARDS GRID MIT LINK PREVIEWS -->
    <div id="bm-detail-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
        <!-- Dynamisch gerendert via app.js -->
    </div>
</div>
