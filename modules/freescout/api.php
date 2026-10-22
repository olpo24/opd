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

$stmt = $pdo->query("SELECT api_url, api_key FROM module_freescout LIMIT 1");
$config = $stmt->fetch();

$baseUrl = rtrim($config['api_url'], '/');
$apiKey  = $config['api_key'];

// Test 1: Standard GET
$ch1 = curl_init($baseUrl . '/mailboxes');
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$res1 = curl_exec($ch1);
$code1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);

// Test 2: Mit Slash am Ende
$ch2 = curl_init($baseUrl . '/mailboxes/');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$res2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

// Test 3: Explizit GET + Slash
$ch3 = curl_init($baseUrl . '/conversations/');
curl_setopt_array($ch3, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => ['X-FreeScout-API-KEY: ' . $apiKey, 'Accept: application/json'],
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true
]);
$res3 = curl_exec($ch3);
$code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);

echo json_encode([
    'test_without_slash' => [
        'http_code' => $code1,
        'response'  => json_decode((string)$res1, true) ?? $res1
    ],
    'test_with_slash' => [
        'http_code' => $code2,
        'response'  => json_decode((string)$res2, true) ?? $res2
    ],
    'test_conversations_with_slash' => [
        'http_code' => $code3,
        'response'  => json_decode((string)$res3, true) ?? $res3
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
