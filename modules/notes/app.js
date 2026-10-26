let allNotesData = [];
let scratchpadTimeout = null;
let activeNotesTypeFilter = 'all';

// Hilfsfunktion: Widget-Daten rendern
function processNotesWidgetData(data) {
    if (!data) return;

    const padText = document.getElementById('notes-scratchpad-text');
    if (padText && data.scratchpad !== undefined && document.activeElement !== padText) {
        padText.value = data.scratchpad;
    }

    if (Array.isArray(data.todos)) {
        renderQuickTodos(data.todos);
    }
}

// 1. Hub-Events
window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail && e.detail.notes) {
        processNotesWidgetData(e.detail.notes);
    }
});

window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'notes') {
        loadNotesDetail();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Fallback Fetch beim Start
    fetch('modules/notes/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                processNotesWidgetData(res.data);
            }
        })
        .catch(err => console.error('Notes Fetch Error:', err));

    // Auto-Save für Scratchpad
    const padText = document.getElementById('notes-scratchpad-text');
    if (padText) {
        padText.addEventListener('input', () => {
            const statusEl = document.getElementById('scratchpad-status');
            if (statusEl) statusEl.textContent = 'Speichere...';

            clearTimeout(scratchpadTimeout);
            scratchpadTimeout = setTimeout(async () => {
                await fetch('modules/notes/api.php?action=save_scratchpad', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ content: padText.value })
                });
                if (statusEl) statusEl.textContent = 'Gespeichert';
            }, 800);
        });
    }

    // Quick To-Do Formular
    document.getElementById('quick-todo-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('quick-todo-input');
        const title = input.value.trim();
        if (!title) return;

        await fetch('modules/notes/api.php?action=save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title: title, type: 'todo', category: 'To-Do' })
        });

        input.value = '';
        reloadNotesSummary();
    });

    // Detail-Suche & Filter
    document.getElementById('notes-detail-search')?.addEventListener('input', () => {
        renderFilteredNotesDetail();
    });

    // Note Modal Form
    document.getElementById('add-note-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            id: document.getElementById('note_id').value,
            title: document.getElementById('note_title').value,
            type: document.getElementById('note_type').value,
            category: document.getElementById('note_category').value || 'Allgemein',
            content: document.getElementById('note_content').value
        };

        const res = await fetch('modules/notes/api.php?action=save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            closeNoteModal();
            loadNotesDetail();
            reloadNotesSummary();
        }
    });
});

async function reloadNotesSummary() {
    try {
        const res = await fetch('modules/notes/api.php?action=get_summary');
        const response = await res.json();
        if (response.status === 'success' && response.data) {
            processNotesWidgetData(response.data);
        }
    } catch (e) { console.error(e); }
}

function renderQuickTodos(todos) {
    const container = document.getElementById('quick-todo-list');
    if (!container) return;

    container.innerHTML = '';
    if (todos.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:0.8rem; margin:0;">Keine offenen Aufgaben.</p>';
        return;
    }

    todos.forEach(t => {
        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.5rem; background: var(--bg-subtle); border-radius: 6px; font-size: 0.8rem;';
        
        const isChecked = parseInt(t.is_completed) === 1;
        
        item.innerHTML = `
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; flex: 1; overflow: hidden; ${isChecked ? 'text-decoration: line-through; opacity: 0.6;' : ''}">
                <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleTodoStatus(${t.id})">
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(t.title)}</span>
            </label>
            <button onclick="deleteNote(${t.id})" style="background:none; border:none; color:var(--text-dark); cursor:pointer; font-size:0.75rem; padding: 0 0.2rem;">✕</button>
        `;
        container.appendChild(item);
    });
}

async function toggleTodoStatus(id) {
    await fetch(`modules/notes/api.php?action=toggle_todo&id=${id}`);
    reloadNotesSummary();
}

function switchWidgetNotesTab(tab) {
    const padBox = document.getElementById('notes-widget-pad-container');
    const todoBox = document.getElementById('notes-widget-todo-container');
    const btnPad = document.getElementById('notes-wtab-btn-pad');
    const btnTodo = document.getElementById('notes-wtab-btn-todo');

    if (tab === 'pad') {
        if (padBox) padBox.style.display = 'flex';
        if (todoBox) todoBox.style.display = 'none';
        btnPad?.classList.remove('btn-secondary');
        btnTodo?.classList.add('btn-secondary');
    } else {
        if (padBox) padBox.style.display = 'none';
        if (todoBox) todoBox.style.display = 'flex';
        btnTodo?.classList.remove('btn-secondary');
        btnPad?.classList.add('btn-secondary');
    }
}

// DETAILSEITE: ALLE NOTIZEN UND SNIPPETS LADEN
async function loadNotesDetail() {
    try {
        const res = await fetch('modules/notes/api.php?action=get_all_notes');
        const response = await res.json();
        if (response.status === 'success' && Array.isArray(response.data)) {
            allNotesData = response.data;
            renderNotesTypePills();
            renderFilteredNotesDetail();
        }
    } catch (e) {
        console.error('Fehler beim Laden der Notizen:', e);
    }
}

function renderNotesTypePills() {
    const container = document.getElementById('notes-detail-type-pills');
    if (!container) return;

    const types = [
        { id: 'all', label: 'Alle' },
        { id: 'note', label: 'Notizen' },
        { id: 'snippet', label: 'Snippets' },
        { id: 'todo', label: 'Aufgaben' }
    ];

    container.innerHTML = '';
    types.forEach(t => {
        const btn = document.createElement('button');
        btn.className = `btn btn-sm ${activeNotesTypeFilter === t.id ? '' : 'btn-secondary'}`;
        btn.style.cssText = 'padding: 0.3rem 0.75rem; font-size: 0.8rem;';
        btn.textContent = t.label;
        btn.onclick = () => {
            activeNotesTypeFilter = t.id;
            renderNotesTypePills();
            renderFilteredNotesDetail();
        };
        container.appendChild(btn);
    });
}

function renderFilteredNotesDetail() {
    const container = document.getElementById('notes-detail-grid');
    const searchTerm = (document.getElementById('notes-detail-search')?.value || '').toLowerCase().trim();

    if (!container) return;

    const filtered = allNotesData.filter(n => {
        const matchesType = (activeNotesTypeFilter === 'all') || (n.type === activeNotesTypeFilter);
        const matchesSearch = (n.title || '').toLowerCase().includes(searchTerm) || 
                              (n.content || '').toLowerCase().includes(searchTerm) || 
                              (n.category && n.category.toLowerCase().includes(searchTerm));
        return matchesType && matchesSearch;
    });

    container.innerHTML = '';

    if (filtered.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:0.9rem; grid-column: 1/-1; padding: 2rem 0; text-align: center;">Keine Notizen oder Snippets gefunden.</p>';
        return;
    }

    filtered.forEach(n => {
        const card = document.createElement('div');
        card.style.cssText = 'background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;';

        const isSnippet = n.type === 'snippet';
        const typeBadge = isSnippet ? '<span class="badge badge-core">Snippet</span>' : (n.type === 'todo' ? '<span class="badge badge-theme">To-Do</span>' : '<span class="badge badge-zero">Notiz</span>');

        let bodyHtml = '';
        if (isSnippet) {
            bodyHtml = `
                <div style="position: relative; margin: 0.75rem 0;">
                    <pre style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem; font-family: monospace; font-size: 0.8rem; color: var(--text-main); overflow-x: auto; max-height: 200px; margin: 0;"><code>${escapeHtml(n.content)}</code></pre>
                    <button onclick="copySnippetToClipboard(this, \`${escapeJs(n.content)}\`)" class="btn btn-sm btn-secondary" style="position: absolute; top: 0.5rem; right: 0.5rem; font-size: 0.7rem; padding: 0.2rem 0.4rem;">Kopieren</button>
                </div>
            `;
        } else {
            bodyHtml = `<p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap; margin: 0.75rem 0; max-height: 200px; overflow-y: auto;">${escapeHtml(n.content)}</p>`;
        }

        card.innerHTML = `
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    ${typeBadge}
                    <span style="font-size: 0.75rem; color: var(--text-dark);">${escapeHtml(n.category || 'Allgemein')}</span>
                </div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">${escapeHtml(n.title)}</h4>
                ${bodyHtml}
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                <button onclick="openEditNoteModal(${n.id})" class="btn btn-sm btn-secondary">Bearbeiten</button>
                <button onclick="deleteNote(${n.id})" class="btn btn-sm btn-secondary btn-danger">Löschen</button>
            </div>
        `;

        container.appendChild(card);
    });
}

function copySnippetToClipboard(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Kopiert! ✔';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}

function openAddNoteModal() {
    document.getElementById('note_id').value = '';
    document.getElementById('note-modal-title').textContent = 'Neue Notiz erstellen';
    document.getElementById('add-note-form').reset();
    document.getElementById('note-modal')?.classList.add('active');
}

function openEditNoteModal(id) {
    const note = allNotesData.find(n => n.id == id);
    if (!note) return;

    document.getElementById('note_id').value = note.id;
    document.getElementById('note_title').value = note.title;
    document.getElementById('note_type').value = note.type;
    document.getElementById('note_category').value = note.category;
    document.getElementById('note_content').value = note.content;
    document.getElementById('note-modal-title').textContent = 'Notiz bearbeiten';

    document.getElementById('note-modal')?.classList.add('active');
}

function closeNoteModal() {
    document.getElementById('note-modal')?.classList.remove('active');
}

async function deleteNote(id) {
    if (!confirm('Eintrag wirklich löschen?')) return;
    await fetch(`modules/notes/api.php?action=delete_note&id=${id}`);
    loadNotesDetail();
    reloadNotesSummary();
}

function escapeJs(str) {
    return String(str || '').replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$/g, '\\$');
}
