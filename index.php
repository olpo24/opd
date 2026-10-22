<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
Auth::check();
?>
<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Operations Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- LOKALE CSS DEPENDENCIES -->
    <link rel="stylesheet" href="css/gridstack.min.css" />
    <link rel="stylesheet" href="css/gridstack-extra.min.css" />

    <style>
        :root[data-theme="dark"] {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --bg-card-hover: #1f2937;
            --bg-subtle: #182235;
            --border-color: #1f2937;
            --border-focus: #3b82f6;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --text-dark: #6b7280;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --status-success-bg: #064e3b;
            --status-success-text: #34d399;
            --status-danger-bg: #7f1d1d;
            --status-danger-text: #fca5a5;
            --status-warning-bg: #78350f;
            --status-warning-text: #fde68a;
            --modal-overlay: rgba(0, 0, 0, 0.75);
            --accordion-bg: #080c14;
            --th-bg: #0d131f;
        }

        :root[data-theme="light"] {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --bg-subtle: #e2e8f0;
            --border-color: #e2e8f0;
            --border-focus: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-dark: #94a3b8;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --status-success-bg: #d1fae5;
            --status-success-text: #065f46;
            --status-danger-bg: #fee2e2;
            --status-danger-text: #991b1b;
            --status-warning-bg: #fef3c7;
            --status-warning-text: #92400e;
            --modal-overlay: rgba(15, 23, 42, 0.4);
            --accordion-bg: #f1f5f9;
            --th-bg: #f8fafc;
        }

        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; padding: 2rem; transition: background 0.2s, color 0.2s; }
        .container { max-width: 1400px; margin: 0 auto; }

        .app-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1.5rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); }
        .brand-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem; letter-spacing: -0.02em; }
        .brand-pill { font-size: 0.75rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; background: rgba(37, 99, 235, 0.15); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.3); text-transform: uppercase; }

        .header-actions { display: flex; align-items: center; gap: 0.75rem; }

        .nav-tabs { display: flex; background: var(--bg-card); padding: 0.25rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .tab-btn { background: transparent; color: var(--text-muted); border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.15s ease; }
        .tab-btn.active { background: var(--bg-subtle); color: var(--text-main); font-weight: 600; }

        .btn { background-color: var(--primary); color: #ffffff; border: 1px solid transparent; padding: 0.5rem 0.875rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.15s ease; }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-secondary { background: var(--bg-card); color: var(--text-main); border-color: var(--border-color); }
        .btn-secondary:hover { background: var(--bg-card-hover); }
        .btn-danger { background: #dc2626; color: #ffffff; }
        .btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8rem; }

        .theme-toggle-btn { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 0.5rem; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; }

        /* GRIDSTACK CONTENT STYLES */
        .grid-stack-item-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .widget-title {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
            user-select: none;
        }

        .widget-body {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .metric-group { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        .metric-box { flex: 1; background: var(--bg-subtle); padding: 0.75rem; border-radius: 8px; text-align: center; }
        .metric-val { font-size: 1.25rem; font-weight: 700; }
        .metric-lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem; }

        /* TABELLEN STYLES */
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
        th { background: var(--th-bg); color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .site-row { cursor: pointer; transition: background-color 0.15s ease; }
        .site-row:hover { background-color: var(--bg-card-hover); }
        .site-row.open { background-color: var(--bg-subtle); }

        .chevron { display: inline-block; width: 16px; height: 16px; transition: transform 0.2s ease; color: var(--text-dark); }
        .site-row.open .chevron { transform: rotate(90deg); color: var(--text-main); }

        .badge { display: inline-flex; align-items: center; padding: 0.15rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-zero { background: var(--bg-subtle); color: var(--text-dark); border: 1px solid var(--border-color); }
        .badge-updates { background: var(--status-danger-bg); color: var(--status-danger-text); }
        .badge-core { background: #581c87; color: #e9d5ff; }
        .badge-theme { background: var(--status-warning-bg); color: var(--status-warning-text); }
        .badge-success { background: var(--status-success-bg); color: var(--status-success-text); }

        .detail-row { display: none; background: var(--accordion-bg); }
        .detail-row.active { display: table-row; }
        .detail-content { padding: 1.5rem; }

        .update-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; }
        .update-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; }
        .update-list { list-style: none; padding: 0; margin: 0; }
        .update-item { padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
        .update-item:last-child { border-bottom: none; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) auto; gap: 1rem; align-items: end; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: var(--text-muted); margin-bottom: 0.35rem; }
        .form-group input { width: 100%; padding: 0.55rem 0.75rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.875rem; outline: none; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--modal-overlay); backdrop-filter: blur(4px); justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- APP HEADER -->
        <header class="app-header">
            <div class="brand-title">
                Core Hub
                <span class="brand-pill">Operations</span>
            </div>

            <div class="header-actions">
                <nav class="nav-tabs">
                    <button class="tab-btn active" id="nav-btn-hub" onclick="switchView('hub')">Startseite</button>
                    <button class="tab-btn" id="nav-btn-wp" onclick="switchView('wp')">WP Maintenance</button>
                    <button class="tab-btn" id="nav-btn-freescout" onclick="switchView('freescout')">Freescout</button>
                </nav>

                <button id="theme-toggle" class="theme-toggle-btn" title="Farbschema wechseln"></button>
                <button id="btn-register-passkey" class="btn btn-secondary">Passkey</button>
                <a href="logout.php" class="btn btn-secondary btn-danger">Abmelden</a>
            </div>
        </header>

        <!-- VIEW 1: HUB STARTSEITE (GRIDSTACK GRID) -->
        <div id="view-hub" class="tab-content active">
            <div class="grid-stack">
                <!-- WIDGET 1: WORDPRESS MAINTENANCE -->
                <div class="grid-stack-item" gs-id="widget_wp" gs-x="0" gs-y="0" gs-w="4" gs-h="3" gs-min-w="3" gs-min-h="2">
                    <div class="grid-stack-item-content">
                        <div class="widget-title">
                            <span>WordPress Maintenance</span>
                            <button class="btn btn-sm btn-secondary" onclick="switchView('wp')">Öffnen &rarr;</button>
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
                                    <div class="metric-val" id="hub-wp-vulns" style="color: #ef4444;">-</div>
                                    <div class="metric-lbl">Lücken</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- WIDGET 2: FREESCOUT HELPDESK -->
                <div class="grid-stack-item" gs-id="widget_freescout" gs-x="4" gs-y="0" gs-w="4" gs-h="3" gs-min-w="3" gs-min-h="2">
                    <div class="grid-stack-item-content">
                        <div class="widget-title">
                            <span>Freescout Helpdesk</span>
                            <button class="btn btn-sm btn-secondary" onclick="switchView('freescout')">Öffnen &rarr;</button>
                        </div>
                        <div class="widget-body">
                            <div class="metric-group">
                                <div class="metric-box">
                                    <div class="metric-val" id="hub-fs-open">0</div>
                                    <div class="metric-lbl">Offene Tickets</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WIDGET 3: BOOKMARKS & QUICK LINKS -->
                <div class="grid-stack-item" gs-id="widget_bookmarks" gs-x="0" gs-y="3" gs-w="8" gs-h="4" gs-min-w="4" gs-min-h="3">
                    <div class="grid-stack-item-content">
                        <div class="widget-title">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span>Lesezeichen & Quick Links</span>
                                <span id="bm-count-badge" class="badge badge-zero" style="font-size:0.75rem;">0</span>
                            </div>
                            <button class="btn btn-sm" onclick="openAddBookmarkModal()">+ Link hinzufügen</button>
                        </div>

                        <div class="widget-body">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center;">
                                <div style="position: relative; flex: 1; min-width: 180px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="var(--text-muted)" viewBox="0 0 256 256" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                                    </svg>
                                    <input type="text" id="bm-search" placeholder="Lesezeichen durchsuchen..." style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2.25rem; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 0.85rem; outline: none;">
                                </div>
                                <div id="bm-category-pills" style="display: flex; gap: 0.35rem; flex-wrap: wrap;"></div>
                            </div>

                            <div id="hub-bookmarks-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.65rem;">
                                <!-- Rendered via js/hub.js -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW 2: WP MAINTENANCE MODULE -->
        <div id="view-wp" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="margin: 0; font-size: 1.25rem;">WordPress Wartung</h2>
                <div class="nav-tabs">
                    <button class="tab-btn active" onclick="switchTab('dashboard')">Übersicht</button>
                    <button class="tab-btn" onclick="switchTab('settings')">Einstellungen</button>
                </div>
            </div>
            <?php include __DIR__ . '/modules/wp_maintenance/view.php'; ?>
        </div>

   <div id="view-freescout" class="tab-content">
    <?php include __DIR__ . '/modules/freescout/view.php'; ?>
</div>

    <!-- BOOKMARK ADD MODAL -->
    <div id="bookmark-modal" class="modal-overlay">
        <div class="modal">
            <h3 style="margin-top:0; margin-bottom: 1.25rem; font-size: 1.1rem; font-weight: 600;">Neues Lesezeichen hinzufügen</h3>
            <form id="add-bookmark-form">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>URL</label>
                    <input type="url" id="bm_url" required placeholder="https://...">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Titel <span style="color:var(--text-muted); font-size:0.75rem;">(Optional - wird sonst automatisch geholt)</span></label>
                    <input type="text" id="bm_title" placeholder="Automatisch abrufen...">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Kategorie</label>
                    <input type="text" id="bm_category" placeholder="z. B. Server, Tools">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeBookmarkModal()">Abbrechen</button>
                    <button type="submit" class="btn">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- LOKALE JS DEPENDENCIES -->
    <script src="js/simplewebauthn.min.js"></script>
    <script src="js/gridstack-all.min.js"></script>
    <script src="modules/wp_maintenance/app.js"></script>
    <script src="js/hub.js"></script>
</body>
</html>
