<?php
/**
 * Start Quiz Attempt Handler
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . url('student/quizzes.php'));
    exit;
}

require_csrf_post();

$quizId = (int)($_POST['quiz_id'] ?? 0);
if ($quizId <= 0) {
    set_flash_message('danger', 'Invalid quiz identifier.');
    header("Location: " . url('student/quizzes.php'));
    exit;
}

$user = current_user();
$userId = $user['id'];

try {
    $pdo = get_db_connection();

    // Verify quiz exists and is published
    $quizStmt = $pdo->prepare("SELECT id, title, duration_minutes, status FROM quizzes WHERE id = ?");
    $quizStmt->execute([$quizId]);
    $quiz = $quizStmt->fetch();

    if (!$quiz || $quiz['status'] !== 'published') {
        set_flash_message('danger', 'This quiz is not available for participation.');
        header("Location: " . url('student/quizzes.php'));
        exit;
    }

    // Calculate total question marks
    $marksStmt = $pdo->prepare("SELECT COALESCE(SUM(marks), 0) as total_marks FROM questions WHERE quiz_id = ?");
    $marksStmt->execute([$quizId]);
    $totalMarks = (float)$marksStmt->fetchColumn();

    // Check if attempt already exists for this student and quiz
    $checkStmt = $pdo->prepare("SELECT id, status FROM attempts WHERE user_id = ? AND quiz_id = ?");
    $checkStmt->execute([$userId, $quizId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'completed' || $existing['status'] === 'expired') {
            set_flash_message('info', 'You have already completed this quiz.');
            header("Location: " . url('student/result.php?attempt_id=' . $existing['id']));
            exit;
        } else {
            // Resume ongoing in-progress attempt
            header("Location: " . url('student/take-quiz.php?attempt_id=' . $existing['id']));
            exit;
        }
    }

    // Create new attempt
    $duration = (int)$quiz['duration_minutes'];
    $now = time();
    $startedAt = date('Y-m-d H:i:s', $now);
    $deadlineAt = date('Y-m-d H:i:s', $now + ($duration * 60));

    $insertStmt = $pdo->prepare("
        INSERT INTO attempts (user_id, quiz_id, started_at, deadline_at, status, total_marks)
        VALUES (?, ?, ?, ?, 'in_progress', ?)
    ");
    $insertStmt->execute([$userId, $quizId, $startedAt, $deadlineAt, $totalMarks]);
    $attemptId = (int)$pdo->lastInsertId();

    header("Location: " . url('student/take-quiz.php?attempt_id=' . $attemptId));
    exit;

} catch (Exception $e) {
    set_flash_message('danger', 'Unable to initiate quiz attempt: ' . $e->getMessage());
    header("Location: " . url('student/instructions.php?quiz_id=' . $quizId));
    exit;
}
