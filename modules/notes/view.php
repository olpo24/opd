<div class="card">
    <div class="view-header">
        <div>
            <h2 class="view-title">Notizen, Snippets & Aufgaben</h2>
            <p class="view-subtitle">Verwalte Code-Snippets, Befehle und ausführliche Notizen.</p>
        </div>
        <button class="btn" onclick="openAddNoteModal()">+ Neue Notiz / Snippet</button>
    </div>

    <div class="filter-bar">
        <div class="filter-search-wrap">
            <input type="text" id="notes-detail-search" placeholder="🔍 Notizen & Snippets durchsuchen...">
        </div>
        <div id="notes-detail-type-pills" class="filter-pills"></div>
    </div>

    <div id="notes-detail-grid" class="notes-grid"></div>
</div>

<div id="note-modal" class="modal-overlay">
    <div class="modal modal-lg">
        <h3 class="modal-title" id="note-modal-title">Neue Notiz erstellen</h3>
        <form id="add-note-form">
            <input type="hidden" id="note_id" value="">
            <div class="form-grid-2-1">
                <div class="form-group">
                    <label class="form-label">Titel</label>
                    <input type="text" id="note_title" required placeholder="z. B. Nginx Reverse Proxy Config">
                </div>
                <div class="form-group">
                    <label class="form-label">Typ</label>
                    <select id="note_type">
                        <option value="note">Notiz</option>
                        <option value="snippet">Code-Snippet</option>
                        <option value="todo">Aufgabe</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kategorie</label>
                <input type="text" id="note_category" placeholder="z. B. Server, CLI, Ideas">
            </div>
            <div class="form-group">
                <label class="form-label">Inhalt / Code</label>
                <textarea id="note_content" rows="8" class="code-textarea" placeholder="Notiz-Text oder Code-Snippet hier eingeben..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeNoteModal()">Abbrechen</button>
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    </div>
</div>
