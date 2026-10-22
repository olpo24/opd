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

$action = $_GET['action'] ?? '';
$pdo = Database::getConnection();

try {
    switch ($action) {
        case 'get_sites':
            $stmt = $pdo->query("SELECT * FROM sites ORDER BY name ASC");
            $sites = $stmt->fetchAll();

            $stmtUpdates = $pdo->query("SELECT * FROM site_updates");
            $allUpdates = $stmtUpdates->fetchAll();

            $stmtVulns = $pdo->query("SELECT * FROM site_vulnerabilities");
            $allVulns = $stmtVulns->fetchAll();

            $groupedUpdates = [];
            foreach ($allUpdates as $up) {
                $groupedUpdates[$up['site_id']][$up['category']][] = $up;
            }

            $groupedVulns = [];
            foreach ($allVulns as $v) {
                $groupedVulns[$v['site_id']][] = $v;
            }

            foreach ($sites as &$site) {
                $site['updates'] = $groupedUpdates[$site['id']] ?? [
                    'core' => [], 'plugin' => [], 'theme' => [], 'translation' => []
                ];
                $site['vulnerabilities'] = $groupedVulns[$site['id']] ?? [];
            }

            echo json_encode(['status' => 'success', 'data' => $sites]);
            break;

        case 'add_site':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO sites (name, url, api_key) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($data['name']),
                rtrim(trim($data['url']), '/'),
                trim($data['api_key'])
            ]);
            echo json_encode(['status' => 'success']);
            break;

        case 'edit_site':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            
            if (!$id) {
                throw new Exception("Ungültige Website-ID.");
            }

            $stmt = $pdo->prepare("UPDATE sites SET name = ?, url = ?, api_key = ? WHERE id = ?");
            $stmt->execute([
                trim($data['name']),
                rtrim(trim($data['url']), '/'),
                trim($data['api_key']),
                $id
            ]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete_site':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        case 'sync_site':
            $siteId = (int)($_GET['id'] ?? 0);
            syncSiteUpdates($pdo, $siteId);
            echo json_encode(['status' => 'success']);
            break;

        case 'generate_autologin':
            $siteId = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT url, api_key FROM sites WHERE id = ?");
            $stmt->execute([$siteId]);
            $site = $stmt->fetch();

            if (!$site) {
                throw new Exception("Website nicht gefunden.");
            }

            $baseUrl = rtrim($site['url'], '/');
            $apiUrl  = $baseUrl . '/wp-json/wpmc/v1/create-autologin-token';

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => '',
                CURLOPT_HTTPHEADER     => [
                    'X-WPMC-KEY: ' . $site['api_key'],
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            
            $res = curl_exec($ch);
            $curlErr = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($curlErr) {
                throw new Exception("cURL-Fehler: " . $curlErr);
            }

            if (str_starts_with(trim((string)$res), '<')) {
                throw new Exception("WordPress hat einen HTML/PHP-Fehler zurückgegeben: " . strip_tags((string)$res));
            }

            $json = json_decode((string)$res, true);
            if (isset($json['login_url'])) {
                echo json_encode(['status' => 'success', 'url' => $json['login_url']]);
            } else {
                throw new Exception($json['message'] ?? "Fehler beim Erzeugen des Login-Links (HTTP Code {$httpCode}).");
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function syncSiteUpdates(PDO $pdo, int $siteId): void
{
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();

    if (!$site) {
        throw new Exception("Website mit ID {$siteId} nicht gefunden.");
    }

    $baseUrl = rtrim($site['url'], '/');
    $apiUrl  = $baseUrl . '/wp-json/wpmc/v1/updates';

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-WPMC-KEY: ' . $site['api_key']],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($curlError) {
        throw new Exception("cURL-Fehler bei Verbindung zu {$apiUrl}: " . $curlError);
    }

    if ($httpCode !== 200 || !$response) {
        throw new Exception("WP REST-API antwortet nicht korrekt (HTTP Code: {$httpCode}). Prüfe Permalinks & API-Key.");
    }

    $data = json_decode((string)$response, true);
    if (($data['status'] ?? '') !== 'success') {
        throw new Exception("WP-Antwort ungültig: " . json_encode($data));
    }

    $stmt = $pdo->prepare("UPDATE sites SET wp_version = ?, php_version = ?, last_check = NOW() WHERE id = ?");
    $stmt->execute([$data['wp_version'], $data['php_version'], $siteId]);

    $pdo->beginTransaction();

    try {
        $foundIdentifiers = [];
        $updates = $data['updates'];

        $delVulnStmt = $pdo->prepare("DELETE FROM site_vulnerabilities WHERE site_id = ?");
        $delVulnStmt->execute([$siteId]);

        foreach ($updates['core'] as $up) {
            $identifier = 'core_update';
            $foundIdentifiers[] = ['category' => 'core', 'id' => $identifier];
            upsertUpdate($pdo, $siteId, 'core', $identifier, 'WordPress Core', $up['current_version'], $up['new_version']);
        }

        foreach ($updates['plugins'] as $up) {
            $identifier = $up['slug'];
            $foundIdentifiers[] = ['category' => 'plugin', 'id' => $identifier];
            $details = json_encode(['changelog_url' => $up['changelog_url'] ?? '']);
            upsertUpdate($pdo, $siteId, 'plugin', $identifier, $up['name'], $up['current_version'], $up['new_version'], $details);

            checkVulnerabilities($pdo, $siteId, 'plugin', $up['slug'], $up['current_version']);
        }

        foreach ($updates['themes'] as $up) {
            $identifier = $up['slug'];
            $foundIdentifiers[] = ['category' => 'theme', 'id' => $identifier];
            upsertUpdate($pdo, $siteId, 'theme', $identifier, $up['name'], $up['current_version'], $up['new_version']);

            checkVulnerabilities($pdo, $siteId, 'theme', $up['slug'], $up['current_version']);
        }

        foreach ($updates['translations'] as $up) {
            $identifier = $up['slug'] . '_' . $up['language'];
            $foundIdentifiers[] = ['category' => 'translation', 'id' => $identifier];
            $name = sprintf("Übersetzung (%s: %s)", $up['slug'], $up['language']);
            upsertUpdate($pdo, $siteId, 'translation', $identifier, $name, 'Alt', $up['version']);
        }

        $existingStmt = $pdo->prepare("SELECT category, item_identifier FROM site_updates WHERE site_id = ?");
        $existingStmt->execute([$siteId]);
        $dbUpdates = $existingStmt->fetchAll();

        foreach ($dbUpdates as $dbUp) {
            $stillOpen = false;
            foreach ($foundIdentifiers as $found) {
                if ($found['category'] === $dbUp['category'] && $found['id'] === $dbUp['item_identifier']) {
                    $stillOpen = true;
                    break;
                }
            }

            if (!$stillOpen) {
                $delStmt = $pdo->prepare("DELETE FROM site_updates WHERE site_id = ? AND category = ? AND item_identifier = ?");
                $delStmt->execute([$siteId, $dbUp['category'], $dbUp['item_identifier']]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function checkVulnerabilities(PDO $pdo, int $siteId, string $type, string $slug, string $version): void
{
    $endpointType = ($type === 'theme') ? 'theme' : 'plugin';
    $apiUrl = "https://api.wpvulnerability.com/{$endpointType}/" . urlencode($slug);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_USERAGENT      => 'WP-Maintenance-Dashboard/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200 || !$res) {
        return;
    }

    $data = json_decode((string)$res, true);
    
    if (empty($data['data']['vulnerability']) || !is_array($data['data']['vulnerability'])) {
        return;
    }

    foreach ($data['data']['vulnerability'] as $vuln) {
        $operator = $vuln['operator']['maxOperator'] ?? '<=';
        $vulnerableVersion = $vuln['operator']['maxVersion'] ?? '';

        if (!empty($vulnerableVersion) && version_compare($version, $vulnerableVersion, $operator)) {
            $stmt = $pdo->prepare("
                INSERT INTO site_vulnerabilities (site_id, item_type, item_slug, title, cve, cvss_score, fixed_in)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    title = VALUES(title),
                    cvss_score = VALUES(cvss_score),
                    fixed_in = VALUES(fixed_in)
            ");
            
            $stmt->execute([
                $siteId,
                $type,
                $slug,
                $vuln['name'] ?? 'Sicherheitslücke bekannt',
                $vuln['source']['cve']['id'] ?? 'CVE-Unbekannt',
                $vuln['impact']['cvss']['score'] ?? null,
                $vulnerableVersion
            ]);
        }
    }
}

function upsertUpdate(PDO $pdo, int $siteId, string $category, string $identifier, string $name, string $currentVer, string $newVer, ?string $details = null): void
{
    $sql = "INSERT INTO site_updates (site_id, category, item_identifier, item_name, current_version, new_version, details_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                item_name = VALUES(item_name),
                current_version = VALUES(current_version),
                new_version = VALUES(new_version),
                details_json = VALUES(details_json)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$siteId, $category, $identifier, $name, $currentVer, $newVer, $details]);
}