<div class="card" style="padding: 1.5rem;">
    <!-- HEADER DER DETAILSEITE -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Notizen, Snippets & Aufgaben</h2>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.85rem;">Verwalte Code-Snippets, Befehle und ausführliche Notizen.</p>
        </div>
        <button class="btn" onclick="openAddNoteModal()">+ Neue Notiz / Snippet</button>
    </div>

    <!-- FILTER & SUCHE -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; align-items: center; background: var(--bg-subtle); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" id="notes-detail-search" placeholder="🔍 Notizen & Snippets durchsuchen..." style="width: 100%; padding: 0.55rem 0.85rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.875rem; outline: none;">
        </div>
        <div id="notes-detail-type-pills" style="display: flex; gap: 0.4rem; flex-wrap: wrap;"></div>
    </div>

    <!-- GRID FÜR CARDS -->
    <div id="notes-detail-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;"></div>
</div>

<!-- MODAL FÜR NEUE NOTIZ / SNIPPET -->
<div id="note-modal" class="modal-overlay">
    <div class="modal" style="max-width: 600px;">
        <h3 style="margin-top:0; margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 600;" id="note-modal-title">Neue Notiz erstellen</h3>
        <form id="add-note-form">
            <input type="hidden" id="note_id" value="">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                <div>
                    <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Titel</label>
                    <input type="text" id="note_title" required placeholder="z. B. Nginx Reverse Proxy Config" style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Typ</label>
                    <select id="note_type" style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
                        <option value="note">Notiz</option>
                        <option value="snippet">Code-Snippet</option>
                        <option value="todo">Aufgabe</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Kategorie</label>
                <input type="text" id="note_category" placeholder="z. B. Server, CLI, Ideas" style="width:100%; padding:0.55rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; font-size:0.8rem; margin-bottom:0.35rem; color:var(--text-muted);">Inhalt / Code</label>
                <textarea id="note_content" rows="8" placeholder="Notiz-Text oder Code-Snippet hier eingeben..." style="width:100%; padding:0.65rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:6px; color:var(--text-main); font-family: monospace; font-size: 0.85rem; resize: vertical; outline: none;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeNoteModal()">Abbrechen</button>
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    </div>
</div>
