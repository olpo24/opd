<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
Auth::check();

$modules = [];
$moduleDirs = glob(__DIR__ . '/modules/*', GLOB_ONLYDIR);

foreach ($moduleDirs as $dir) {
    // Wenn das Modul deaktiviert ist (.disabled Datei existiert), komplett überspringen
    if (file_exists($dir . '/.disabled')) {
        continue;
    }

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
    <link rel="stylesheet" href="css/app.css" />

    <?php foreach ($modules as $m): ?>
        <?php 
        $cssFile = __DIR__ . '/modules/' . $m['dir'] . '/style.css';
        if (file_exists($cssFile)): 
        ?>
            <link rel="stylesheet" href="modules/<?= htmlspecialchars($m['dir']) ?>/style.css" />
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body>
    <div id="app-wrapper">
        <!-- PIXELCAVE COLLAPSIBLE SIDEBAR -->
        <aside class="app-sidebar">
            <div class="sidebar-header">
                <div class="brand-logo">C</div>
                <span class="brand-text">Core Hub</span>
            </div>

            <nav class="sidebar-nav">
    <div class="nav-section-title">Hauptmenü</div>
    
    <button class="nav-item active" id="nav-btn-hub" onclick="switchView('hub')">
        <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </span>
        <span>Startseite</span>
    </button>

    <div class="nav-section-title">Module</div>

    <?php foreach ($modules as $m): ?>
        <!-- 'settings' aus der regulären Sidebar-Liste ausschließen -->
        <?php if (!empty($m['has_view']) && $m['id'] !== 'settings'): ?>
            <button class="nav-item" id="nav-btn-<?= htmlspecialchars($m['id']) ?>" onclick="switchView('<?= htmlspecialchars($m['id']) ?>')">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </span>
                <span><?= htmlspecialchars($m['name']) ?></span>
            </button>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
        </aside>

        <!-- MAIN CONTENT AREA WITH TOPBAR -->
        <main class="app-main">
            <header class="app-header">
                <div class="header-left">
                    <button class="sidebar-toggle-btn" id="sidebar-toggle" title="Sidebar einklappen">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <h1 class="page-title" id="current-page-title">Startseite</h1>
                </div>

                <div class="header-actions">
    <!-- EINSTELLUNGEN BUTTON (Zahnrad Icon) -->
    <button id="nav-btn-settings" class="header-icon-btn" onclick="switchView('settings')" title="System-Einstellungen">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </button>

    <button id="theme-toggle" class="theme-toggle-btn" title="Farbschema wechseln"></button>
    <!-- Schnellzugriff auf das Passkey-Modul -->
<button class="header-icon-btn" onclick="switchView('passkey')" title="Passkeys & Biometrie">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-2-2l2 2m2 0l-3 3m-3-3l3 3M3 21l9-9m0 0l-2-2m2 2l2 2"/></svg>
</button>
    <a href="logout.php" class="btn btn-secondary btn-sm btn-danger">Abmelden</a>
</div>
            </header>

            <div class="app-content">
                <!-- VIEW 1: HUB STARTSEITE -->
                <div id="view-hub" class="tab-content active">
                    <div class="grid-stack">
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
        </main>
    </div>

    <!-- CLIENT SKRIPTE -->
    <script src="js/simplewebauthn.min.js"></script>
    <script src="js/gridstack-all.uamd.min.js"></script>
    <script src="js/passkey.js"></script>
    <script src="js/hub.js"></script>

    <?php foreach ($modules as $m): ?>
        <?php 
        $scriptFile = __DIR__ . '/modules/' . $m['dir'] . '/app.js';
        if (file_exists($scriptFile)): 
        ?>
            <script src="modules/<?= htmlspecialchars($m['dir']) ?>/app.js"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
