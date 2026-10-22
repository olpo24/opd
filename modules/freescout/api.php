<?php
declare(strict_types=1);

ini_set('display_errors', '1');
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
$action = $_GET['action'] ?? 'debug';

if ($action === 'debug') {
    $stmt = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
    $config = $stmt->fetch();

    if (!$config) {
        echo json_encode(['error' => 'Keine Config in DB gefunden']);
        exit;
    }

    $baseUrl = rtrim($config['api_url'], '/');
    $apiKey  = $config['api_key'];

    // 1. Test: Mailboxen
    $chMb = curl_init($baseUrl . '/mailboxes');
    curl_setopt_array($chMb, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $resMb = curl_exec($chMb);
    $httpMb = curl_getinfo($chMb, CURLINFO_HTTP_CODE);
    $errMb = curl_error($chMb);

    // 2. Test: Conversations (ohne Filter)
    $chConv = curl_init($baseUrl . '/conversations');
    curl_setopt_array($chConv, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $resConv = curl_exec($chConv);
    $httpConv = curl_getinfo($chConv, CURLINFO_HTTP_CODE);
    $errConv = curl_error($chConv);

    echo json_encode([
        'config_used' => [
            'api_url' => $baseUrl,
            'api_key_length' => strlen($apiKey),
            'api_key_snippet' => substr($apiKey, 0, 6) . '...'
        ],
        'mailboxes_test' => [
            'http_code' => $httpMb,
            'curl_error' => $errMb,
            'response' => json_decode((string)$resMb, true) ?? $resMb
        ],
        'conversations_test' => [
            'http_code' => $httpConv,
            'curl_error' => $errConv,
            'response' => json_decode((string)$resConv, true) ?? $resConv
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
