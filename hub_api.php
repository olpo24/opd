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
            try {
                $wpSitesCount   = (int)$pdo->query("SELECT COUNT(*) FROM sites")->fetchColumn();
                $wpUpdatesCount = (int)$pdo->query("SELECT COUNT(*) FROM site_updates")->fetchColumn();
                $wpVulnCount    = (int)$pdo->query("SELECT COUNT(*) FROM site_vulnerabilities")->fetchColumn();
            } catch (Throwable $e) {
                $wpSitesCount = 0; $wpUpdatesCount = 0; $wpVulnCount = 0;
            }

            // Freescout Quick Stats
            // Freescout Offene Tickets abfragen
            $fsOpenTickets = 0;
            try {
                $stmtFs = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
                $fsConfig = $stmtFs->fetch();
                if ($fsConfig && !empty($fsConfig['api_key'])) {
                    $baseUrl = rtrim($fsConfig['api_url'], '/');
                    $apiKey  = $fsConfig['api_key'];

                    // 1. Mailboxen abrufen
                    $ch = curl_init($baseUrl . '/mailboxes');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
                        CURLOPT_TIMEOUT        => 5,
                        CURLOPT_SSL_VERIFYPEER => false
                    ]);
                    $res = curl_exec($ch);
                    $mbData = json_decode((string)$res, true);
                    
                    if (!empty($mbData['_embedded']['mailboxes'])) {
                        foreach ($mbData['_embedded']['mailboxes'] as $mb) {
                            // Freescout nutzt state=open (oder status=active), um alle unerledigten Tickets zu erfassen
                            $chC = curl_init($baseUrl . "/conversations?mailboxId={$mb['id']}&state=open");
                            curl_setopt_array($chC, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
                                CURLOPT_TIMEOUT        => 5,
                                CURLOPT_SSL_VERIFYPEER => false
                            ]);
                            $cRes = curl_exec($chC);
                            $cData = json_decode((string)$cRes, true);

                            // Falls 'page' -> 'totalElements' vorhanden ist, nutzen
                            if (isset($cData['page']['totalElements'])) {
                                $fsOpenTickets += (int)$cData['page']['totalElements'];
                            } elseif (!empty($cData['_embedded']['conversations'])) {
                                $fsOpenTickets += count($cData['_embedded']['conversations']);
                            }
                        }
                    }
                }
            } catch (Throwable $e) {}
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
                'status' => 'success',
                'modules' => [
                    'wp_maintenance' => [
                        'sites'           => $wpSitesCount,
                        'updates'         => $wpUpdatesCount,
                        'vulnerabilities' => $wpVulnCount
                    ],
                    'freescout' => [
                        'open_tickets'    => $fsOpenTickets
                    ],
                    'bookmarks' => $bookmarks,
                    'layouts'   => $layouts
                ]
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

/**
 * Lädt den HTML-Header einer URL und extrahiert den Inhalt des <title>-Tags
 */
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
            
            if (!empty($title)) {
                return $title;
            }
        }
    }

    $parsed = parse_url($url);
    return $parsed['host'] ?? $url;
}
