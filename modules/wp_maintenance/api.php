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
    // 1. ZUSAMMENFASSUNG FÜR DAS STARTSEITEN-WIDGET
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        $stmt = $pdo->query("SELECT * FROM module_wp_sites");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSites = count($sites);
        $totalUpdates = 0;
        $totalVulns = 0;

        foreach ($sites as $site) {
            $updatesRaw = $site['updates_json'] ?? $site['updates'] ?? '[]';
            $vulnsRaw = $site['vulnerabilities_json'] ?? $site['vulnerabilities'] ?? '[]';

            $updates = is_array($updatesRaw) ? $updatesRaw : (json_decode((string)$updatesRaw, true) ?: []);
            $vulns = is_array($vulnsRaw) ? $vulnsRaw : (json_decode((string)$vulnsRaw, true) ?: []);

            if (is_array($updates)) {
                $totalUpdates += count($updates['core'] ?? []);
                $totalUpdates += count($updates['plugin'] ?? []);
                $totalUpdates += count($updates['theme'] ?? []);
                $totalUpdates += count($updates['translation'] ?? []);
            }

            if (is_array($vulns)) {
                $totalVulns += count($vulns);
            }
        }

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'sites'           => $totalSites,
                'updates'         => $totalUpdates,
                'vulnerabilities' => $totalVulns
            ]
        ]);
        exit;
    }

    // 2. VOLLSTÄNDIGE SITES-LISTE FÜR DIE DETAILSEITE
    if ($action === 'get_sites') {
        $stmt = $pdo->query("SELECT * FROM module_wp_sites ORDER BY name ASC");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($sites as $site) {
            $updatesRaw = $site['updates_json'] ?? $site['updates'] ?? '[]';
            $vulnsRaw = $site['vulnerabilities_json'] ?? $site['vulnerabilities'] ?? '[]';

            $site['updates'] = is_array($updatesRaw) ? $updatesRaw : (json_decode((string)$updatesRaw, true) ?: []);
            $site['vulnerabilities'] = is_array($vulnsRaw) ? $vulnsRaw : (json_decode((string)$vulnsRaw, true) ?: []);

            // Struktur absichern
            if (!isset($site['updates']['core']) || !is_array($site['updates']['core'])) $site['updates']['core'] = [];
            if (!isset($site['updates']['plugin']) || !is_array($site['updates']['plugin'])) $site['updates']['plugin'] = [];
            if (!isset($site['updates']['theme']) || !is_array($site['updates']['theme'])) $site['updates']['theme'] = [];
            if (!isset($site['updates']['translation']) || !is_array($site['updates']['translation'])) $site['updates']['translation'] = [];

            $result[] = $site;
        }

        echo json_encode(['status' => 'success', 'data' => $result]);
        exit;
    }

    // 3. SEITE HINZUFÜGEN
    if ($action === 'add_site') {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $url = trim($data['url'] ?? '');
        $apiKey = trim($data['api_key'] ?? '');

        if (empty($name) || empty($url) || empty($apiKey)) {
            throw new Exception('Name, URL und API-Key sind erforderlich.');
        }

        $stmt = $pdo->prepare("INSERT INTO module_wp_sites (name, url, api_key) VALUES (?, ?, ?)");
        $stmt->execute([$name, $url, $apiKey]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 4. SEITE BEARBEITEN
    if ($action === 'edit_site') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $url = trim($data['url'] ?? '');
        $apiKey = trim($data['api_key'] ?? '');

        $stmt = $pdo->prepare("UPDATE module_wp_sites SET name = ?, url = ?, api_key = ? WHERE id = ?");
        $stmt->execute([$name, $url, $apiKey, $id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 5. SEITE LÖSCHEN
    if ($action === 'delete_site') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM module_wp_sites WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
