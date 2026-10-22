<div class="card" style="padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem;">Freescout Ticket-Übersicht</h3>
            <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.85rem;">Alle offenen Kundentickets im Überblick</p>
        </div>
        <button onclick="loadFreescoutData()" class="btn btn-secondary btn-sm">Aktualisieren</button>
    </div>

    <!-- TABELLE FÜR FREESCOUT TICKETS -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Ticket</th>
                    <th>Betreff</th>
                    <th>Kunde</th>
                    <th>Postfach</th>
                    <th>Datum</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody id="freescout-tickets-table-body">
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Lade Tickets...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
