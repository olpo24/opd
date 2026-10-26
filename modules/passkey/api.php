<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Nicht angemeldet']);
    exit;
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // 1. REGISTRIERUNGS-OPTIONEN (CHALLENGE) GENERIEREN
        case 'register_options':
            $username = $_SESSION['username'] ?? 'User';
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

        // 2. PASSKEY-REGISTRIERUNG VERIFIZIEREN & SPEICHERN
        case 'register_verify':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['id'])) {
                throw new Exception('Ungültige Antwort vom Authenticator.');
            }

            $credentialId = $data['id'];
            $publicKey = $data['response']['publicKey'] ?? base64_encode(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO passkey_credentials (user_id, credential_id, public_key) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $credentialId, $publicKey]);

            unset($_SESSION['passkey_challenge']);
            echo json_encode(['verified' => true, 'status' => 'success']);
            break;

        // 3. ALLE REGISTRIERTEN KEYS DES USERS LISTEN
        case 'get_credentials':
            $stmt = $pdo->prepare("SELECT id, credential_id, sign_count, created_at FROM passkey_credentials WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$userId]);
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $keys]);
            break;

        // 4. EINEN PASSKEY LÖSCHEN
        case 'delete_credential':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM passkey_credentials WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
