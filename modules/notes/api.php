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
    // 1. SUMMARY FÜR DAS WIDGET (Scratchpad + To-Dos)
    if ($action === 'get_summary' || $action === 'get_summary_internal') {
        // Scratchpad holen (falls nicht existiert, anlegen)
        $stmtPad = $pdo->prepare("SELECT content FROM module_notes WHERE user_id = ? AND type = 'note' AND title = 'Scratchpad' LIMIT 1");
        $stmtPad->execute([$userId]);
        $pad = $stmtPad->fetch(PDO::FETCH_ASSOC);

        // To-Dos holen
        $stmtTodos = $pdo->prepare("SELECT * FROM module_notes WHERE user_id = ? AND type = 'todo' ORDER BY is_completed ASC, id DESC LIMIT 10");
        $stmtTodos->execute([$userId]);
        $todos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data'   => [
                'scratchpad' => $pad['content'] ?? '',
                'todos'      => $todos
            ]
        ]);
        exit;
    }

    // 2. ALLE NOTIZEN & SNIPPETS FÜR DIE DETAILSEITE
    if ($action === 'get_all_notes') {
        $stmt = $pdo->prepare("SELECT * FROM module_notes WHERE user_id = ? AND title != 'Scratchpad' ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $notes]);
        exit;
    }

    // 3. SCRATCHPAD SPEICHERN (AUTO-SAVE)
    if ($action === 'save_scratchpad') {
        $data = json_decode(file_get_contents('php://input'), true);
        $content = (string)($data['content'] ?? '');

        $stmtCheck = $pdo->prepare("SELECT id FROM module_notes WHERE user_id = ? AND type = 'note' AND title = 'Scratchpad' LIMIT 1");
        $stmtCheck->execute([$userId]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE module_notes SET content = ? WHERE id = ?");
            $stmt->execute([$content, $exists]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO module_notes (user_id, title, content, type, category) VALUES (?, 'Scratchpad', ?, 'note', 'General')");
            $stmt->execute([$userId, $content]);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    // 4. NEUE NOTIZ / SNIPPET / TODO ERSTELLEN ODER BEARBEITEN
    if ($action === 'save_note') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $category = trim($data['category'] ?? 'Allgemein');
        $type = in_array($data['type'] ?? '', ['note', 'todo', 'snippet'], true) ? $data['type'] : 'note';

        if (empty($title)) {
            throw new Exception("Titel ist erforderlich.");
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE module_notes SET title = ?, content = ?, category = ?, type = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $category, $type, $id, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO module_notes (user_id, title, content, category, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $content, $category, $type]);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    // 5. TODO STATUS UMSCHALTEN (CHECKBOX)
    if ($action === 'toggle_todo') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE module_notes SET is_completed = NOT is_completed WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 6. NOTIZ LÖSCHEN
    if ($action === 'delete_note') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM module_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
