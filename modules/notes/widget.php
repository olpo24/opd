<div class="grid-stack-item" gs-id="widget_notes" gs-x="8" gs-y="0" gs-w="4" gs-h="4" gs-min-w="3" gs-min-h="3">
    <div class="grid-stack-item-content">
        <div class="widget-title">
            <span>Quick Scratchpad & To-Dos</span>
            <button class="btn btn-sm btn-secondary" onclick="switchView('notes')">Öffnen &rarr;</button>
        </div>
        <div class="widget-body">
            <!-- Tabs im Widget: Scratchpad vs Quick To-Dos -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                <button class="btn btn-sm" id="notes-wtab-btn-pad" onclick="switchWidgetNotesTab('pad')" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Scratchpad</button>
                <button class="btn btn-sm btn-secondary" id="notes-wtab-btn-todo" onclick="switchWidgetNotesTab('todo')" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">To-Dos</button>
            </div>

            <!-- SCRATCHPAD AREA -->
            <div id="notes-widget-pad-container" style="flex: 1; display: flex; flex-direction: column;">
                <textarea id="notes-scratchpad-text" placeholder="Schnelle Notizen hier tippen (speichert automatisch)..." style="width: 100%; flex: 1; background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.65rem; color: var(--text-main); font-family: inherit; font-size: 0.85rem; resize: none; outline: none;"></textarea>
                <div style="font-size: 0.7rem; color: var(--text-dark); margin-top: 0.35rem; text-align: right;" id="scratchpad-status">Automatisch gespeichert</div>
            </div>

            <!-- QUICK TO-DO LIST -->
            <div id="notes-widget-todo-container" style="display: none; flex: 1; flex-direction: column;">
                <form id="quick-todo-form" style="display: flex; gap: 0.4rem; margin-bottom: 0.65rem;">
                    <input type="text" id="quick-todo-input" placeholder="Neue Aufgabe..." required style="flex: 1; padding: 0.4rem 0.6rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.8rem; outline: none;">
                    <button type="submit" class="btn btn-sm">+</button>
                </form>
                <div id="quick-todo-list" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.35rem;"></div>
            </div>
        </div>
    </div>
</div>
