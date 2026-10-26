<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Nicht angemeldet']);
    exit;
}

$action = $_GET['action'] ?? '';
$pdo = Database::getConnection();

try {
    switch ($action) {
        case 'get_widgets_summary':
            $moduleSummaries = [];

            // Alle Modul-APIs automatisch aufrufen
           $moduleDirs = glob(__DIR__ . '/modules/*', GLOB_ONLYDIR);
            $moduleSummaries = [];

            foreach ($moduleDirs as $dir) {
            if (file_exists($dir . '/.disabled')) {
        continue; // Deaktiviertes Modul ignoriere
    }
                $modName = basename($dir);
                $apiFile = $dir . '/api.php';
                
                if (file_exists($apiFile)) {
                    $_GET['action'] = 'get_summary_internal';
                    ob_start();
                    include $apiFile;
                    $rawJson = ob_get_clean();
                    
                    $data = json_decode((string)$rawJson, true);
                    if (isset($data['status']) && $data['status'] === 'success') {
                        $moduleSummaries[$modName] = $data['data'];
                    }
                }
            }

            // Dashboard Grid Layouts
            $userId = (int)$_SESSION['user_id'];
            $layouts = [];
            try {
                $stmtL = $pdo->prepare("SELECT widget_id, x, y, w, h FROM dashboard_widget_layouts WHERE user_id = ?");
                $stmtL->execute([$userId]);
                $rawLayouts = $stmtL->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rawLayouts as $l) {
                    $layouts[$l['widget_id']] = $l;
                }
            } catch (Throwable $e) {}

            echo json_encode([
                'status'  => 'success',
                'modules' => $moduleSummaries,
                'layouts' => $layouts
            ]);
            break;

        case 'save_widget_layout':
            $userId = (int)$_SESSION['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);

            if (!is_array($data)) {
                throw new Exception("Ungültiges Layout-Format.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO dashboard_widget_layouts (user_id, widget_id, x, y, w, h)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE x = VALUES(x), y = VALUES(y), w = VALUES(w), h = VALUES(h)
            ");

            foreach ($data as $item) {
                if (isset($item['id'], $item['x'], $item['y'], $item['w'], $item['h'])) {
                    $stmt->execute([
                        $userId,
                        (string)$item['id'],
                        (int)$item['x'],
                        (int)$item['y'],
                        (int)$item['w'],
                        (int)$item['h']
                    ]);
                }
            }

            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
