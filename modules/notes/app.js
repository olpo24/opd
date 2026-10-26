let allNotesData = [];
let scratchpadTimeout = null;
let activeNotesTypeFilter = 'all';

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
    fetch('modules/notes/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                processNotesWidgetData(res.data);
            }
        })
        .catch(err => console.error('Notes Fetch Error:', err));

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

    document.getElementById('notes-detail-search')?.addEventListener('input', () => {
        renderFilteredNotesDetail();
    });

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
        container.innerHTML = '<p class="notes-empty-text">Keine offenen Aufgaben.</p>';
        return;
    }

    todos.forEach(t => {
        const item = document.createElement('div');
        item.className = 'notes-todo-item';
        
        const isChecked = parseInt(t.is_completed) === 1;
        
        item.innerHTML = `
            <label class="notes-todo-label ${isChecked ? 'completed' : ''}">
                <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleTodoStatus(${t.id})">
                <span class="notes-todo-title">${escapeHtml(t.title)}</span>
            </label>
            <button onclick="deleteNote(${t.id})" class="notes-delete-btn">✕</button>
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
        container.innerHTML = '<p class="notes-empty-text full-width">Keine Notizen oder Snippets gefunden.</p>';
        return;
    }

    filtered.forEach(n => {
        const card = document.createElement('div');
        card.className = 'notes-card';

        const isSnippet = n.type === 'snippet';
        const typeBadge = isSnippet ? '<span class="badge badge-core">Snippet</span>' : (n.type === 'todo' ? '<span class="badge badge-warning">To-Do</span>' : '<span class="badge badge-zero">Notiz</span>');

        let bodyHtml = '';
        if (isSnippet) {
            bodyHtml = `
                <div class="notes-snippet-wrap">
                    <pre class="notes-card-code"><code>${escapeHtml(n.content)}</code></pre>
                    <button onclick="copySnippetToClipboard(this, \`${escapeJs(n.content)}\`)" class="btn btn-sm btn-secondary notes-copy-btn">Kopieren</button>
                </div>
            `;
        } else {
            bodyHtml = `<p class="notes-card-text">${escapeHtml(n.content)}</p>`;
        }

        card.innerHTML = `
            <div>
                <div class="notes-card-header">
                    ${typeBadge}
                    <span class="notes-card-category">${escapeHtml(n.category || 'Allgemein')}</span>
                </div>
                <h4 class="notes-card-title">${escapeHtml(n.title)}</h4>
                ${bodyHtml}
            </div>
            <div class="notes-card-footer">
                <button onclick="openEditNoteModal(${n.id})" class="btn btn-sm btn-secondary">Bearbeiten</button>
                <button onclick="deleteNote(${n.id})" class="btn btn-sm btn-danger">Löschen</button>
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
