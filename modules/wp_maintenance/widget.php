<div class="grid-stack-item" gs-id="widget_wp_maintenance" gs-x="0" gs-y="0" gs-w="4" gs-h="3" gs-min-w="3" gs-min-h="2">
    <div class="grid-stack-item-content">
        <div class="widget-title">
            <span>WordPress Maintenance</span>
            <button class="btn btn-sm btn-secondary" onclick="switchView('wp_maintenance')">Öffnen &rarr;</button>
        </div>
        <div class="widget-body">
            <div class="metric-group">
                <div class="metric-box">
                    <div class="metric-val" id="hub-wp-sites">-</div>
                    <div class="metric-lbl">Instanzen</div>
                </div>
                <div class="metric-box">
                    <div class="metric-val" id="hub-wp-updates">-</div>
                    <div class="metric-lbl">Updates</div>
                </div>
                <div class="metric-box">
                    <div class="metric-val" id="hub-wp-vulns" style="color: var(--status-danger-text, #ef4444);">-</div>
                    <div class="metric-lbl">Lücken</div>
                </div>
            </div>
        </div>
    </div>
</div>
