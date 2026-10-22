<div class="grid-stack-item" gs-id="widget_bookmarks" gs-x="0" gs-y="3" gs-w="8" gs-h="4" gs-min-w="4" gs-min-h="3">
    <div class="grid-stack-item-content">
        <div class="widget-title">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span>Lesezeichen & Quick Links</span>
                <span id="bm-count-badge" class="badge badge-zero" style="font-size:0.75rem;">0</span>
            </div>
            <button class="btn btn-sm" onclick="openAddBookmarkModal()">+ Link hinzufügen</button>
        </div>

        <div class="widget-body">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center;">
                <div style="position: relative; flex: 1; min-width: 180px;">
                    <input type="text" id="bm-search" placeholder="Lesezeichen durchsuchen..." style="width: 100%; padding: 0.45rem 0.75rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                </div>
                <div id="bm-category-pills" style="display: flex; gap: 0.35rem; flex-wrap: wrap;"></div>
            </div>

            <div id="hub-bookmarks-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.65rem;"></div>
        </div>
    </div>
</div>

<!-- MODAL FÜR LESEZEICHEN -->
<div id="bookmark-modal" class="modal-overlay">
    <div class="modal">
        <h3 style="margin-top:0; margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 600;">Neues Lesezeichen hinzufügen</h3>
        <form id="add-bookmark-form">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">URL</label>
                <input type="url" id="bm_url" required placeholder="https://..." style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Titel (Optional)</label>
                <input type="text" id="bm_title" placeholder="Automatisch abrufen..." style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
            </div>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Kategorie</label>
                <input type="text" id="bm_category" placeholder="z. B. Server, Tools" style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeBookmarkModal()">Abbrechen</button>
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    </div>
</div>
