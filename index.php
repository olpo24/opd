<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
Auth::check();

// Dynamisches Erkennen aller installierten Module
$modules = [];
$moduleDirs = glob(__DIR__ . '/modules/*', GLOB_ONLYDIR);

foreach ($moduleDirs as $dir) {
    $manifestPath = $dir . '/module.json';
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (is_array($manifest) && !empty($manifest['id'])) {
            $manifest['dir'] = basename($dir);
            $modules[$manifest['id']] = $manifest;
        }
    }
}
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

        .grid-stack-item-content { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; overflow: hidden; }
        .widget-title { font-size: 1rem; font-weight: 600; margin-top: 0; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; cursor: move; user-select: none; }
        .widget-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

        .metric-group { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        .metric-box { flex: 1; background: var(--bg-subtle); padding: 0.75rem; border-radius: 8px; text-align: center; }
        .metric-val { font-size: 1.25rem; font-weight: 700; }
        .metric-lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem; }

        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
        th { background: var(--th-bg); color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .badge { display: inline-flex; align-items: center; padding: 0.15rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-zero { background: var(--bg-subtle); color: var(--text-dark); border: 1px solid var(--border-color); }
        .badge-updates { background: var(--status-danger-bg); color: var(--status-danger-text); }
        .badge-success { background: var(--status-success-bg); color: var(--status-success-text); }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--modal-overlay); backdrop-filter: blur(4px); justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER MIT DYNAMISCHER NAVIGATION -->
        <header class="app-header">
            <div class="brand-title">
                Core Hub
                <span class="brand-pill">Operations</span>
            </div>

            <div class="header-actions">
                <nav class="nav-tabs">
                    <button class="tab-btn active" id="nav-btn-hub" onclick="switchView('hub')">Startseite</button>
                    
                    <!-- Dynamisch generierte Navigation aus den Modul-Manifesten -->
                    <?php foreach ($modules as $m): ?>
                        <?php if (!empty($m['has_view'])): ?>
                            <button class="tab-btn" id="nav-btn-<?= htmlspecialchars($m['id']) ?>" onclick="switchView('<?= htmlspecialchars($m['id']) ?>')">
                                <?= htmlspecialchars($m['name']) ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <button id="theme-toggle" class="theme-toggle-btn" title="Farbschema wechseln"></button>
                <button id="btn-register-passkey" class="btn btn-secondary">Passkey</button>
                <a href="logout.php" class="btn btn-secondary btn-danger">Abmelden</a>
            </div>
        </header>

        <!-- VIEW 1: HUB STARTSEITE (GRIDSTACK GRID) -->
        <div id="view-hub" class="tab-content active">
            <div class="grid-stack">
                <!-- Dynamisches Includieren aller Modul-Widgets -->
                <?php foreach ($modules as $m): ?>
                    <?php 
                    $widgetFile = __DIR__ . '/modules/' . $m['dir'] . '/widget.php';
                    if (file_exists($widgetFile)) {
                        include $widgetFile;
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- DYNAMISCHE MODUL DETAILANSICHTEN -->
        <?php foreach ($modules as $m): ?>
            <?php if (!empty($m['has_view'])): ?>
                <div id="view-<?= htmlspecialchars($m['id']) ?>" class="tab-content">
                    <?php 
                    $viewFile = __DIR__ . '/modules/' . $m['dir'] . '/view.php';
                    if (file_exists($viewFile)) {
                        include $viewFile;
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- LOKALE DEPENDENCIES & SKRIPTE -->
    <script src="js/simplewebauthn.min.js"></script>
    <script src="js/gridstack-all.uamd.min.js"></script>

    <!-- Dynamisches Laden aller Client-seitigen Skripte aus den Modulen -->
    <?php foreach ($modules as $m): ?>
        <?php 
        $scriptFile = __DIR__ . '/modules/' . $m['dir'] . '/app.js';
        if (file_exists($scriptFile)): 
        ?>
            <script src="modules/<?= htmlspecialchars($m['dir']) ?>/app.js"></script>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="js/hub.js"></script>
</body>
</html>
