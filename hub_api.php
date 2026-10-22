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

            // Alle Module abfragen, die eine api.php bereitstellen
            $moduleDirs = glob(__DIR__ . '/modules/*', GLOB_ONLYDIR);
            foreach ($moduleDirs as $dir) {
                $modName = basename($dir);
                $apiFile = $dir . '/api.php';
                
                if (file_exists($apiFile)) {
                    // Interner Hook für Modul-Zusammenfassungen
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

            // Bookmarks & Dashboard-Layouts
            $bookmarks = [];
            try {
                $bookmarks = $pdo->query("SELECT * FROM module_bookmarks ORDER BY category ASC, position_order ASC, title ASC")->fetchAll();
            } catch (Throwable $e) {}

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
                'bookmarks' => $bookmarks,
                'layouts'   => $layouts
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

        case 'add_bookmark':
            $data = json_decode(file_get_contents('php://input'), true);
            $url = trim($data['url'] ?? '');
            $title = trim($data['title'] ?? '');

            if (empty($url)) {
                throw new Exception("URL ist erforderlich.");
            }

            if (empty($title)) {
                $title = fetchPageTitle($url);
            }

            $stmt = $pdo->prepare("INSERT INTO module_bookmarks (title, url, category) VALUES (?, ?, ?)");
            $stmt->execute([
                $title,
                $url,
                trim($data['category'] ?? 'Allgemein')
            ]);
            echo json_encode(['status' => 'success', 'fetched_title' => $title]);
            break;

        case 'delete_bookmark':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM module_bookmarks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function fetchPageTitle(string $url): string
{
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) CoreHub/1.0'
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200 && $html) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', (string)$html, $matches)) {
            $title = trim($matches[1]);
            $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = preg_replace('/\s+/', ' ', $title);
            if (!empty($title)) return $title;
        }
    }

    $parsed = parse_url($url);
    return $parsed['host'] ?? $url;
}
