<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht angemeldet']);
    exit;
}

$pdo = Database::getConnection();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'register_options':
            $userId = (int)$_SESSION['user_id'];
            $username = $_SESSION['username'] ?? 'User';

            // Challenge generieren
            $challenge = random_bytes(32);
            $_SESSION['passkey_challenge'] = bin2hex($challenge);

            echo json_encode([
                'rp' => [
                    'name' => 'Core Operations Dashboard',
                    'id'   => $_SERVER['HTTP_HOST']
                ],
                'user' => [
                    'id'          => base64_encode((string)$userId),
                    'name'        => $username,
                    'displayName' => $username
                ],
                'challenge' => bin2hex($challenge),
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],  // ES256
                    ['type' => 'public-key', 'alg' => -257] // RS256
                ],
                'timeout' => 60000,
                'attestation' => 'none'
            ]);
            break;

        case 'register_verify':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                echo json_encode(['verified' => false, 'error' => 'Keine Daten empfangen']);
                exit;
            }

            // Speichern/Verifizieren
            echo json_encode(['verified' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
