<div class="card" style="padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem;">Freescout Ticket-Übersicht</h3>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.85rem;">Alle verbundenen Postfächer von support.olpo.de</p>
        </div>
        <button onclick="loadFreescoutData()" class="btn btn-secondary btn-sm">Aktualisieren</button>
    </div>

    <div id="freescout-mailboxes-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
        <p style="color: var(--text-muted);">Lade Postfächer...</p>
    </div>
</div>
