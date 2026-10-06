<?php
/**
 * Delete MCQ Question Action
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('admin/questions.php'));
    exit;
}

require_csrf_post();

$questionId = (int)($_POST['question_id'] ?? 0);
$quizId = (int)($_POST['quiz_id'] ?? 0);

if ($questionId <= 0) {
    set_flash_message('danger', 'Invalid question identifier.');
    header("Location: " . url('admin/questions.php'));
    exit;
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$questionId]);

    set_flash_message('success', 'Question and its answer options were deleted successfully.');
} catch (Exception $e) {
    set_flash_message('danger', 'Error deleting question: ' . $e->getMessage());
}

$redirectUrl = ($quizId > 0) ? url('admin/questions.php?quiz_id=' . $quizId) : url('admin/questions.php');
header("Location: " . $redirectUrl);
exit;
