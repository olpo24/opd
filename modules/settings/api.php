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
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    // 1. BENUTZERPROFIL ABRUFEN
    if ($action === 'get_profile') {
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $user]);
        exit;
    }

    // 2. BENUTZERPROFIL / PASSWORT AKTUALISIEREN
    if ($action === 'update_profile') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Gültige E-Mail Adresse ist erforderlich.");
        }

        if (!empty($password)) {
            if (strlen($password) < 8) {
                throw new Exception("Das Passwort muss mindestens 8 Zeichen lang sein.");
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$email, $hash, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $userId]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Profil erfolgreich aktualisiert.']);
        exit;
    }

    // 3. MODULE LISTEN
    if ($action === 'get_modules') {
        $modulesDir = __DIR__ . '/..';
        $dirs = glob($modulesDir . '/*', GLOB_ONLYDIR);
        $modules = [];

        foreach ($dirs as $d) {
            $folder = basename($d);
            $manifestPath = $d . '/module.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
                $manifest['folder'] = $folder;
                $manifest['enabled'] = file_exists($d . '/.disabled') ? false : true;
                $modules[] = $manifest;
            }
        }

        echo json_encode(['status' => 'success', 'data' => $modules]);
        exit;
    }

    // 4. MODUL TOGGLE (AKTIVIEREN / DEAKTIVIEREN)
    if ($action === 'toggle_module') {
    $folder = basename($_GET['folder'] ?? '');
    $targetDir = __DIR__ . '/../' . $folder;

    if (!is_dir($targetDir) || $folder === 'settings') {
        throw new Exception("Dieses Modul kann nicht deaktiviert werden.");
    }

    $disabledFlag = $targetDir . '/.disabled';
    if (file_exists($disabledFlag)) {
        @unlink($disabledFlag);
        $status = 'enabled';
    } else {
        file_put_contents($disabledFlag, 'disabled');
        $status = 'disabled';
    }

    echo json_encode(['status' => 'success', 'module_status' => $status]);
    exit;
}
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
