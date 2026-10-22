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

// URL von https://support.olpo.de/api/v1 auf https://support.olpo.de/api bereinigen
$stmt = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
$config = $stmt->fetch();

$rawUrl = $config['api_url'] ?? 'https://support.olpo.de/api';
$cleanUrl = preg_replace('/\/v1\/?$/', '', rtrim($rawUrl, '/'));
if (!str_ends_with($cleanUrl, '/api')) {
    $cleanUrl = rtrim($cleanUrl, '/') . '/api';
}

$apiKey = $config['api_key'] ?? '';

// Test A: API Key über Header (X-FreeScout-API-Key) auf /api/mailboxes
$chA = curl_init($cleanUrl . '/mailboxes');
curl_setopt_array($chA, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'X-FreeScout-API-Key: ' . $apiKey,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$resA = curl_exec($chA);
$codeA = curl_getinfo($chA, CURLINFO_HTTP_CODE);

// Test B: API Key als GET-Parameter ?api_key=... auf /api/mailboxes
$chB = curl_init($cleanUrl . '/mailboxes?api_key=' . urlencode($apiKey));
curl_setopt_array($chB, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$resB = curl_exec($chB);
$codeB = curl_getinfo($chB, CURLINFO_HTTP_CODE);

// Test C: Conversations abrufen mit ?api_key=...
$chC = curl_init($cleanUrl . '/conversations?api_key=' . urlencode($apiKey));
curl_setopt_array($chC, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$resC = curl_exec($chC);
$codeC = curl_getinfo($chC, CURLINFO_HTTP_CODE);

echo json_encode([
    'url_tested' => $cleanUrl,
    'test_header' => [
        'http_code' => $codeA,
        'response'  => json_decode((string)$resA, true) ?? $resA
    ],
    'test_get_param_mailboxes' => [
        'http_code' => $codeB,
        'response'  => json_decode((string)$resB, true) ?? $resB
    ],
    'test_get_param_conversations' => [
        'http_code' => $codeC,
        'response'  => json_decode((string)$resC, true) ?? $resC
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
