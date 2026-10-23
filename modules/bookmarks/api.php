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

try {
    // 1. ZUSAMMENFASSUNG / LESEZEICHEN ABRUFEN
    if ($action === 'get_summary' || $action === 'get_summary_internal' || $action === 'get_bookmarks') {
        $stmt = $pdo->query("SELECT * FROM module_bookmarks ORDER BY category ASC, position_order ASC, title ASC");
        $bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data'   => $bookmarks
        ]);
        exit;
    }

    // 2. LESEZEICHEN HINZUFÜGEN
    if ($action === 'add_bookmark') {
        $data = json_decode(file_get_contents('php://input'), true);
        $url = trim($data['url'] ?? '');
        $title = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? 'Allgemein');

        if (empty($url)) {
            throw new Exception("URL ist erforderlich.");
        }

        if (empty($title)) {
            $title = fetchPageTitle($url);
        }

        $stmt = $pdo->prepare("INSERT INTO module_bookmarks (title, url, category) VALUES (?, ?, ?)");
        $stmt->execute([$title, $url, empty($category) ? 'Allgemein' : $category]);

        echo json_encode(['status' => 'success', 'fetched_title' => $title]);
        exit;
    }

    // 3. LESEZEICHEN LÖSCHEN
    if ($action === 'delete_bookmark') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM module_bookmarks WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success']);
        exit;
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
