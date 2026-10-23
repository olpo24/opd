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
    // 1. SUMMARY FÜR DAS DASHBOARD-WIDGET
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        $stmtSites = $pdo->query("SELECT COUNT(*) FROM module_wp_sites");
        $totalSites = (int)$stmtSites->fetchColumn();

        $stmtUpdates = $pdo->query("SELECT COUNT(*) FROM site_updates");
        $totalUpdates = (int)$stmtUpdates->fetchColumn();

        $stmtVulns = $pdo->query("SELECT COUNT(*) FROM site_vulnerabilities");
        $totalVulns = (int)$stmtVulns->fetchColumn();

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

    // 2. ALLE SITES MIT UPDATES UND LÜCKEN FÜR DIE DETAILSEITE
    if ($action === 'get_sites') {
        $stmtSites = $pdo->query("SELECT * FROM module_wp_sites ORDER BY name ASC");
        $sites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

        // Alle Updates aus site_updates laden und gruppieren
        $stmtUpdates = $pdo->query("SELECT * FROM site_updates");
        $rawUpdates = $stmtUpdates->fetchAll(PDO::FETCH_ASSOC);
        $updatesBySite = [];
        foreach ($rawUpdates as $up) {
            $siteId = (int)$up['site_id'];
            $cat = $up['category'] ?? 'plugin'; // core, plugin, theme, translation
            $updatesBySite[$siteId][$cat][] = [
                'item_identifier' => $up['item_identifier'],
                'item_name'       => $up['item_name'],
                'current_version' => $up['current_version'],
                'new_version'     => $up['new_version'],
                'details_json'    => $up['details_json']
            ];
        }

        // Alle Vulnerabilities aus site_vulnerabilities laden
        $stmtVulns = $pdo->query("SELECT * FROM site_vulnerabilities");
        $rawVulns = $stmtVulns->fetchAll(PDO::FETCH_ASSOC);
        $vulnsBySite = [];
        foreach ($rawVulns as $v) {
            $siteId = (int)$v['site_id'];
            $vulnsBySite[$siteId][] = [
                'item_type'  => $v['item_type'],
                'item_slug'  => $v['item_slug'],
                'title'      => $v['title'],
                'cve'        => $v['cve'],
                'cvss_score' => $v['cvss_score'],
                'fixed_in'   => $v['fixed_in']
            ];
        }

        $result = [];
        foreach ($sites as $site) {
            $sId = (int)$site['id'];
            
            $siteUpdates = $updatesBySite[$sId] ?? [];
            $site['updates'] = [
                'core'        => $siteUpdates['core'] ?? [],
                'plugin'      => $siteUpdates['plugin'] ?? [],
                'theme'       => $siteUpdates['theme'] ?? [],
                'translation' => $siteUpdates['translation'] ?? []
            ];

            $site['vulnerabilities'] = $vulnsBySite[$sId] ?? [];

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
