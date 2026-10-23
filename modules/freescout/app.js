// Hilfsfunktion: Schreibt die Anzahl offener Tickets in das Startseiten-Widget
function updateFreescoutWidget(data) {
    if (!data) return;
    const countEl = document.getElementById('hub-fs-open');
    if (countEl) {
        countEl.textContent = data.total_open ?? 0;
    }
}

// 1. Auf zentrales Hub-Event lauschen (sofern getriggert)
window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail && e.detail.freescout) {
        updateFreescoutWidget(e.detail.freescout);
    }
});

// 2. Bei Ansichts-Wechsel zur Detailseite Tickets laden
window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'freescout') {
        loadFreescoutData();
    }
});

// 3. FALLBACK: Beim ersten Laden der Seite sofort selbst per API abfragen
document.addEventListener('DOMContentLoaded', () => {
    fetch('modules/freescout/api.php?action=get_summary')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                updateFreescoutWidget(res.data);
            }
        })
        .catch(err => console.error('Freescout Summary Fetch Error:', err));
});

// Funktion für die Detailansicht (Tabelle)
async function loadFreescoutData() {
    const tbody = document.getElementById('freescout-tickets-table-body');
    if (!tbody) return;

    try {
        const res = await fetch('modules/freescout/api.php?action=get_summary');
        const response = await res.json();

        if (response.status === 'success') {
            const data = response.data;
            
            // Widget-Zähler gleich mit aktualisieren
            updateFreescoutWidget(data);

            tbody.innerHTML = '';
            let allOpenTickets = [];
            if (data.mailboxes && Array.isArray(data.mailboxes)) {
                data.mailboxes.forEach(mb => {
                    if (mb.conversations && Array.isArray(mb.conversations)) {
                        mb.conversations.forEach(c => {
                            allOpenTickets.push({ ...c, mailboxName: mb.name });
                        });
                    }
                });
            }

            if (allOpenTickets.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: var(--status-success-text); padding: 2rem;">✔ Keine offenen Tickets vorhanden.</td></tr>';
                return;
            }

            allOpenTickets.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

            allOpenTickets.forEach(t => {
                const tr = document.createElement('tr');
                const ticketUrl = `https://support.olpo.de/conversation/${t.id}`;
                const customerName = t.customer 
                    ? `${escapeHtml(t.customer.firstName || '')} ${escapeHtml(t.customer.lastName || '')}`.trim() || escapeHtml(t.customer.email)
                    : 'Unbekannt';

                const createdAtFormatted = t.createdAt 
                    ? new Date(t.createdAt).toLocaleString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '-';

                tr.innerHTML = `
                    <td><strong style="color:var(--text-muted);">#${t.number}</strong></td>
                    <td>
                        <a href="${ticketUrl}" target="_blank" style="color:var(--text-main); font-weight:600; text-decoration:none;">
                            ${escapeHtml(t.subject || 'Kein Betreff')}
                        </a>
                        ${t.preview ? `<br><span style="color:var(--text-muted); font-size:0.75rem; display:inline-block; max-width:400px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(t.preview)}</span>` : ''}
                    </td>
                    <td style="font-size:0.85rem; color:var(--text-main);">${customerName}</td>
                    <td><span class="badge badge-zero">${escapeHtml(t.mailboxName)}</span></td>
                    <td style="font-size:0.85rem; color:var(--text-muted);">${createdAtFormatted}</td>
                    <td style="text-align: right;">
                        <a href="${ticketUrl}" target="_blank" class="btn btn-sm">Öffnen &rarr;</a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Fehler beim Laden der Freescout Daten:', e);
    }
}
