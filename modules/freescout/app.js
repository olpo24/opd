function updateFreescoutWidget(data) {
    if (!data) return;
    const countEl = document.getElementById('hub-fs-open');
    if (countEl) {
        countEl.textContent = data.total_open ?? 0;
    }
}

window.addEventListener('hubDataLoaded', (e) => {
    if (e.detail && e.detail.freescout) {
        updateFreescoutWidget(e.detail.freescout);
    }
});

window.addEventListener('hubViewSwitched', (e) => {
    if (e.detail && e.detail.view === 'freescout') {
        loadFreescoutData();
    }
});

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

async function loadFreescoutData() {
    const tbody = document.getElementById('freescout-tickets-table-body');
    if (!tbody) return;

    try {
        const res = await fetch('modules/freescout/api.php?action=get_summary');
        const response = await res.json();

        if (response.status === 'success') {
            const data = response.data;
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
                tbody.innerHTML = '<tr><td colspan="6" class="table-empty-cell text-success">✔ Keine offenen Tickets vorhanden.</td></tr>';
                return;
            }

            allOpenTickets.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

            allOpenTickets.forEach(t => {
                const tr = document.createElement('tr');
                tr.className = 'freescout-ticket-row';
                const ticketUrl = `https://support.olpo.de/conversation/${t.id}`;
                const customerName = t.customer 
                    ? `${escapeHtml(t.customer.firstName || '')} ${escapeHtml(t.customer.lastName || '')}`.trim() || escapeHtml(t.customer.email)
                    : 'Unbekannt';

                const createdAtFormatted = t.createdAt 
                    ? new Date(t.createdAt).toLocaleString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : '-';

                tr.innerHTML = `
                    <td><strong class="text-muted">#${t.number}</strong></td>
                    <td>
                        <a href="${ticketUrl}" target="_blank" class="freescout-ticket-subject">
                            ${escapeHtml(t.subject || 'Kein Betreff')}
                        </a>
                        ${t.preview ? `<span class="freescout-ticket-preview">${escapeHtml(t.preview)}</span>` : ''}
                    </td>
                    <td class="freescout-customer-name">${customerName}</td>
                    <td><span class="badge badge-zero">${escapeHtml(t.mailboxName)}</span></td>
                    <td class="freescout-date">${createdAtFormatted}</td>
                    <td class="table-actions-cell">
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
