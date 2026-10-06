<?php
/**
 * AJAX Answer Autosave Handler
 * Online Quiz Competition Platform
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!is_student()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

$attemptId = (int)($_POST['attempt_id'] ?? 0);
$questionId = (int)($_POST['question_id'] ?? 0);
$selectedOptionId = (int)($_POST['selected_option_id'] ?? 0);

if ($attemptId <= 0 || $questionId <= 0 || $selectedOptionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

$user = current_user();
$userId = $user['id'];

try {
    $pdo = get_db_connection();

    // Verify attempt ownership, status, and deadline
    $attemptStmt = $pdo->prepare("SELECT id, deadline_at, status FROM attempts WHERE id = ? AND user_id = ?");
    $attemptStmt->execute([$attemptId, $userId]);
    $attempt = $attemptStmt->fetch();

    if (!$attempt || $attempt['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Attempt is not active']);
        exit;
    }

    if (time() > (strtotime($attempt['deadline_at']) + 15)) {
        echo json_encode(['success' => false, 'message' => 'Time limit expired']);
        exit;
    }

    // Verify selected option belongs to the question
    $optStmt = $pdo->prepare("SELECT id FROM options WHERE id = ? AND question_id = ?");
    $optStmt->execute([$selectedOptionId, $questionId]);
    if (!$optStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Option does not belong to question']);
        exit;
    }

    // Upsert into attempt_answers
    $saveStmt = $pdo->prepare("
        INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id), updated_at = NOW()
    ");
    $saveStmt->execute([$attemptId, $questionId, $selectedOptionId]);

    echo json_encode(['success' => true, 'message' => 'Answer saved successfully']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
