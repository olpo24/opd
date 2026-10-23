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
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        $totalSites = 0;
        $totalUpdates = 0;
        $totalVulns = 0;

        try {
            // Sites aus der Datenbank lesen
            $stmt = $pdo->query("SELECT * FROM module_wp_sites");
            $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalSites = count($sites);

            foreach ($sites as $site) {
                // Liest updates_json und vulnerabilities_json sicher aus
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
        } catch (Throwable $dbError) {
            // Falls Tabelle leer oder Struktur abweicht, Fallback auf 0 senden statt 500
            error_log("WP Maintenance DB Error: " . $dbError->getMessage());
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

    // ... Hier folgen deine weiteren Actions (get_sites, add_site, sync_site) ...

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
