<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
Auth::check();

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
    
    <!-- ZENTRALES DASHBOARD DESIGN-SYSTEM -->
    <link rel="stylesheet" href="css/app.css" />

    <!-- DYNAMISCHES LADEN ALLER MODUL-STYLES -->
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
    <div class="container">
        <!-- HEADER -->
        <header class="app-header">
            <div class="brand-title">
                Core Hub
                <span class="brand-pill">Operations</span>
            </div>

            <div class="header-actions">
                <nav class="nav-tabs">
                    <button class="tab-btn active" id="nav-btn-hub" onclick="switchView('hub')">Startseite</button>
                    
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

    <!-- SKRIPTE -->
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
