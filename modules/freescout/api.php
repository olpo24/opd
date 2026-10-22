<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

header('Content-Type: application/json');

// Webhooks von Freescout ohne Session erlauben
$action = $_GET['action'] ?? '';

if ($action !== 'webhook' && empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Nicht angemeldet']);
    exit;
}

$pdo = Database::getConnection();

try {
    switch ($action) {
        case 'get_summary':
            $stmt = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
            $config = $stmt->fetch();

            if (!$config || empty($config['api_key'])) {
                echo json_encode(['status' => 'success', 'data' => ['total_open' => 0, 'mailboxes' => []]]);
                exit;
            }

            $mailboxes = freescoutApiRequest($config['api_url'], $config['api_key'], '/mailboxes');
            $mailboxesData = $mailboxes['_embedded']['mailboxes'] ?? [];

            $totalOpen = 0;
            $mailboxSummaries = [];

            foreach ($mailboxesData as $mb) {
                $mbId = $mb['id'];
                $mbName = $mb['name'];

                // Offene Tickets pro Postfach abrufen (status=open)
                $conversations = freescoutApiRequest($config['api_url'], $config['api_key'], "/conversations?mailboxId={$mbId}&status=open");
                $openCount = $conversations['page']['totalElements'] ?? 0;

                $totalOpen += $openCount;

                $mailboxSummaries[] = [
                    'id' => $mbId,
                    'name' => $mbName,
                    'open_tickets' => $openCount,
                    'conversations' => array_slice($conversations['_embedded']['conversations'] ?? [], 0, 5) // Die 5 neusten
                ];
            }

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_open' => $totalOpen,
                    'mailboxes' => $mailboxSummaries
                ]
            ]);
            break;

        case 'webhook':
            // Optionaler Webhook-Empfänger für Freescout Events
            $payload = file_get_contents('php://input');
            // Hier kann bei Bedarf ein Cache-Invalidation Event getriggert werden
            echo json_encode(['status' => 'ok']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function freescoutApiRequest(string $baseUrl, string $apiKey, string $endpoint): array
{
    $url = rtrim($baseUrl, '/') . $endpoint;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'X-FreeScout-API-KEY: ' . $apiKey,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch) || $httpCode !== 200) {
        return [];
    }

    return json_decode((string)$response, true) ?? [];
}
