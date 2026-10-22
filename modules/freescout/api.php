<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Nicht angemeldet']);
    exit;
}

$pdo = Database::getConnection();
$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        $stmt = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
        $config = $stmt->fetch();

        if (!$config || empty($config['api_key'])) {
            $response = ['status' => 'success', 'data' => ['total_open' => 0, 'mailboxes' => []]];
            echo json_encode($response);
            exit;
        }

        $baseUrl = preg_replace('/\/v1\/?$/', '', rtrim($config['api_url'], '/'));
        if (!str_ends_with($baseUrl, '/api')) {
            $baseUrl = rtrim($baseUrl, '/') . '/api';
        }
        $apiKey = $config['api_key'];

        $mailboxesData = freescoutRequest($baseUrl, $apiKey, '/mailboxes');
        $mailboxes = $mailboxesData['_embedded']['mailboxes'] ?? [];

        $convData = freescoutRequest($baseUrl, $apiKey, '/conversations?status=active,pending');
        $allConvs = $convData['_embedded']['conversations'] ?? [];

        $groupedByMailbox = [];
        $totalOpen = 0;

        foreach ($allConvs as $c) {
            if (($c['state'] ?? '') === 'published') {
                $mbId = $c['mailboxId'] ?? 0;
                $groupedByMailbox[$mbId][] = $c;
                $totalOpen++;
            }
        }

        $mailboxSummaries = [];
        foreach ($mailboxes as $mb) {
            $mbId = $mb['id'];
            $mbConvs = $groupedByMailbox[$mbId] ?? [];

            $mailboxSummaries[] = [
                'id'            => $mbId,
                'name'          => $mb['name'],
                'open_tickets'  => count($mbConvs),
                'conversations' => array_slice($mbConvs, 0, 5)
            ];
        }

        $response = [
            'status' => 'success',
            'data'   => [
                'total_open' => $totalOpen,
                'mailboxes'  => $mailboxSummaries
            ]
        ];

        echo json_encode($response);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function freescoutRequest(string $baseUrl, string $apiKey, string $endpoint): array
{
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'X-FreeScout-API-Key: ' . $apiKey,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $res = curl_exec($ch);
    if (curl_errno($ch)) return [];
    return json_decode((string)$res, true) ?? [];
}
