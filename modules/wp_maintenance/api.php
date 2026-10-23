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
    // A) MODUL-SUMMARY FÜR DAS DASHBOARD-WIDGET
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        $stmt = $pdo->query("SELECT id, updates_json, vulnerabilities_json FROM module_wp_sites");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSites = count($sites);
        $totalUpdates = 0;
        $totalVulns = 0;

        foreach ($sites as $site) {
            $updates = json_decode($site['updates_json'] ?? '[]', true) ?: [];
            $vulns = json_decode($site['vulnerabilities_json'] ?? '[]', true) ?: [];

            $totalUpdates += count($updates['core'] ?? []);
            $totalUpdates += count($updates['plugin'] ?? []);
            $totalUpdates += count($updates['theme'] ?? []);
            $totalUpdates += count($updates['translation'] ?? []);

            $totalVulns += count($vulns);
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

    // B) SITES LISTE FÜR DIE DETAILSEITE
    if ($action === 'get_sites') {
        $stmt = $pdo->query("SELECT * FROM module_wp_sites ORDER BY name ASC");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($sites as $site) {
            $site['updates'] = json_decode($site['updates_json'] ?? '[]', true) ?: [];
            $site['vulnerabilities'] = json_decode($site['vulnerabilities_json'] ?? '[]', true) ?: [];
            unset($site['updates_json'], $site['vulnerabilities_json']);
            $result[] = $site;
        }

        echo json_encode(['status' => 'success', 'data' => $result]);
        exit;
    }

    // ... weitere Aktionen (add_site, edit_site, delete_site, sync_site) bleiben unverändert ...

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
