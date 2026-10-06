<?php
/**
 * Delete Quiz Action
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('admin/quizzes.php'));
    exit;
}

require_csrf_post();

$quizId = (int)($_POST['quiz_id'] ?? 0);
if ($quizId <= 0) {
    set_flash_message('danger', 'Invalid quiz identifier.');
    header("Location: " . url('admin/quizzes.php'));
    exit;
}

try {
    $pdo = get_db_connection();

    // Fetch quiz title for flash message
    $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
    $stmt->execute([$quizId]);
    $title = $stmt->fetchColumn();

    if ($title) {
        $delStmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $delStmt->execute([$quizId]);
        set_flash_message('success', "Quiz '{$title}' and its associated questions/attempts were deleted successfully.");
    } else {
        set_flash_message('warning', 'Quiz not found.');
    }
} catch (Exception $e) {
    set_flash_message('danger', 'Error deleting quiz: ' . $e->getMessage());
}

header("Location: " . url('admin/quizzes.php'));
exit;
