<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Nicht angemeldet']);
    exit;
}

$pdo = Database::getConnection();
$action = $_GET['action'] ?? '';
$apiFlashKey = 'fe8f4d9d960d4c7995103e54e2d3d1be';

try {
    // 1. LESEZEICHEN ABRUFEN
    if ($action === 'get_summary' || $action === 'get_summary_internal' || $action === 'get_bookmarks') {
        $stmt = $pdo->query("SELECT * FROM module_bookmarks ORDER BY category ASC, position_order ASC, title ASC");
        $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data'   => $bookmarks
        ]);
        exit;
    }

    // 2. LESEZEICHEN HINZUFÜGEN (Sofortige Antwort!)
    if ($action === 'add_bookmark') {
        $data = json_decode(file_get_contents('php://input'), true);
        $url = trim($data['url'] ?? '');
        $title = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? 'Allgemein');

        if (empty($url)) {
            throw new Exception("URL ist erforderlich.");
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        if (empty($title)) {
            $title = fetchPageTitle($url);
        }

        // 1. Eintrag SOFORT in die Datenbank schreiben
        $stmt = $pdo->prepare("INSERT INTO module_bookmarks (title, url, category, icon_url) VALUES (?, ?, ?, NULL)");
        $stmt->execute([
            $title,
            $url,
            empty($category) ? 'Allgemein' : $category
        ]);
        $bookmarkId = (int)$pdo->lastInsertId();

        // 2. Antwort an das Frontend SOFORT senden und Verbindung schließen
        echo json_encode(['status' => 'success', 'fetched_title' => $title, 'id' => $bookmarkId]);

        // Veranlasst PHP/FastCGI dazu, die HTTP-Antwort sofort an den Client zu senden
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            header('Content-Length: ' . ob_get_length());
            header('Connection: close');
            ob_end_flush();
            flush();
        }

        // 3. HINTERGRUND-VERARBEITUNG: ApiFlash Screenshot im Hintergrund herunterladen
        captureApiFlashScreenshotAsync($bookmarkId, $url, $apiFlashKey, $pdo);
        exit;
    }

    // 3. BACKGROUND-TRIGGER (Aktion zum Generieren des Screenshots)
    if ($action === 'generate_preview') {
        $id = (int)($_GET['id'] ?? 0);
        $url = trim($_GET['url'] ?? '');

        if ($id > 0 && !empty($url)) {
            captureApiFlashScreenshotAsync($id, $url, $apiFlashKey, $pdo);
        }
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // 4. LESEZEICHEN LÖSCHEN & LOKALE VORSCHAU ENTFERNEN
    if ($action === 'delete_bookmark') {
        $id = (int)($_GET['id'] ?? 0);

        $stmtSelect = $pdo->prepare("SELECT icon_url FROM module_bookmarks WHERE id = ?");
        $stmtSelect->execute([$id]);
        $bm = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if ($bm && !empty($bm['icon_url'])) {
            $filePath = __DIR__ . '/previews/' . basename($bm['icon_url']);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmtDelete = $pdo->prepare("DELETE FROM module_bookmarks WHERE id = ?");
        $stmtDelete->execute([$id]);

        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Laedt den Screenshot im Hintergrund und aktualisiert die DB
 */
function captureApiFlashScreenshotAsync(int $bookmarkId, string $targetUrl, string $apiKey, PDO $pdo): void
{
    $previewsDir = __DIR__ . '/previews';
    if (!is_dir($previewsDir)) {
        @mkdir($previewsDir, 0775, true);
    }

    $filename = 'bm_' . md5($targetUrl . $bookmarkId) . '.jpg';
    $savePath = $previewsDir . '/' . $filename;

    $params = http_build_query([
        'access_key' => $apiKey,
        'url'        => $targetUrl,
        'format'     => 'jpeg',
        'width'      => 800,
        'height'     => 600,
        'fresh'      => 'true'
    ]);

    $apiUrl = "https://api.apiflash.com/v1/urltoimage?" . $params;

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) CoreHub/1.0'
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200 && $imageData) {
        file_put_contents($savePath, $imageData);
        // Dateinamen in der Datenbank aktualisieren
        $stmt = $pdo->prepare("UPDATE module_bookmarks SET icon_url = ? WHERE id = ?");
        $stmt->execute([$filename, $bookmarkId]);
    }
}

function fetchPageTitle(string $url): string
{
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
