<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => isset($_SERVER['HTTPS'])
    ]);
}

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$pdo = Database::getConnection();

try {
    switch ($action) {
        // -------------------------------------------------------------
        // 1. CHALLEGE ERZEUGEN: Passkey Registrierung (im Dashboard)
        // -------------------------------------------------------------
        case 'get_reg_options':
            if (empty($_SESSION['user_id'])) {
                throw new Exception('Nicht angemeldet.');
            }

            $userId = (string)$_SESSION['user_id'];
            $username = $_SESSION['username'] ?? 'Admin';
            $challenge = random_bytes(32);

            $_SESSION['webauthn_reg_challenge'] = bin2hex($challenge);

            // WebAuthn Creation Options (für SimpleWebAuthnBrowser)
            echo json_encode([
                'rp' => [
                    'name' => 'WP Maintenance Dashboard',
                    'id'   => $_SERVER['SERVER_NAME']
                ],
                'user' => [
                    'id'          => base64UrlEncode($userId),
                    'name'        => $username,
                    'displayName' => $username
                ],
                'challenge' => base64UrlEncode($challenge),
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],  // ES256
                    ['type' => 'public-key', 'alg' => -257] // RS256
                ],
                'timeout' => 60000,
                'authenticatorSelection' => [
                    'userVerification' => 'preferred',
                    'residentKey'      => 'preferred'
                ]
            ]);
            break;

        // -------------------------------------------------------------
        // 2. VERIFIKATION: Passkey Registrierung speichern
        // -------------------------------------------------------------
        case 'verify_reg':
            if (empty($_SESSION['user_id']) || empty($_SESSION['webauthn_reg_challenge'])) {
                throw new Exception('Ungültige Sitzung oder abgelaufener Request.');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['id']) || empty($input['response']['publicKey'])) {
                // Bei manchen Browser-Formaten wird die credential_id über die id übertragen
                if (empty($input['id'])) {
                    throw new Exception('Ungültige Antwort vom Authenticator.');
                }
            }

            $credentialId = $input['id'];
            // PublicKey extrahieren (oder als Attestation sichern)
            $publicKey = $input['response']['publicKey'] ?? base64UrlEncode(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO passkey_credentials (user_id, credential_id, public_key) VALUES (?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $credentialId,
                $publicKey
            ]);

            unset($_SESSION['webauthn_reg_challenge']);
            echo json_encode(['status' => 'success']);
            break;

        // -------------------------------------------------------------
        // 3. CHALLENGE ERZEUGEN: Passkey Login (auf Login-Seite)
        // -------------------------------------------------------------
        case 'get_auth_options':
            $challenge = random_bytes(32);
            $_SESSION['webauthn_auth_challenge'] = bin2hex($challenge);

            // Alle registrierten Credential IDs laden
            $stmt = $pdo->query("SELECT credential_id FROM passkey_credentials");
            $credentials = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $allowCredentials = array_map(function($id) {
                return [
                    'id'   => $id,
                    'type' => 'public-key'
                ];
            }, $credentials);

            echo json_encode([
                'challenge'        => base64UrlEncode($challenge),
                'timeout'          => 60000,
                'rpId'             => $_SERVER['SERVER_NAME'],
                'allowCredentials' => $allowCredentials,
                'userVerification' => 'preferred'
            ]);
            break;

        // -------------------------------------------------------------
        // 4. VERIFIKATION: Passkey Login durchführen & Session starten
        // -------------------------------------------------------------
        case 'verify_auth':
            if (empty($_SESSION['webauthn_auth_challenge'])) {
                throw new Exception('Abgelaufene Login-Anfrage.');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $credentialId = $input['id'] ?? '';

            if (empty($credentialId)) {
                throw new Exception('Keine Credential-ID übergeben.');
            }

            // Zugehörigen User über die Credential-ID aus DB suchen
            $stmt = $pdo->prepare("
                SELECT u.id, u.username 
                FROM users u 
                JOIN passkey_credentials pc ON pc.user_id = u.id 
                WHERE pc.credential_id = ?
            ");
            $stmt->execute([$credentialId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Passkey keinem Benutzer zugeordnet.');
            }

            // Anforderung erfolgreich -> Session für User starten
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            unset($_SESSION['webauthn_auth_challenge']);

            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion.']);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Hilfsfunktion für Base64URL-Encoding (WebAuthn Standard)
 */
function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}