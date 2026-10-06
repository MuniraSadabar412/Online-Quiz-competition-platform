<?php
/**
 * Quiz Submission & Server-Side Evaluation Engine
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$attemptId = (int)($_POST['attempt_id'] ?? $_GET['attempt_id'] ?? 0);
$isAutoSubmitted = !empty($_POST['auto_submitted']) || !empty($_GET['auto_submitted']);

if ($attemptId <= 0) {
    set_flash_message('danger', 'Invalid attempt identifier.');
    header("Location: " . url('student/quizzes.php'));
    exit;
}

// If submitted manually via POST, enforce CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAutoSubmitted) {
    require_csrf_post();
}

$user = current_user();
$userId = $user['id'];

try {
    $pdo = get_db_connection();

    // Fetch attempt details
    $attemptStmt = $pdo->prepare("
        SELECT 
            a.*,
            q.duration_minutes
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $attemptStmt->execute([$attemptId, $userId]);
    $attempt = $attemptStmt->fetch();

    if (!$attempt) {
        set_flash_message('danger', 'Quiz attempt not found or unauthorized.');
        header("Location: " . url('student/quizzes.php'));
        exit;
    }

    // Idempotency: if already completed, redirect to result
    if ($attempt['status'] === 'completed' || $attempt['status'] === 'expired') {
        header("Location: " . url('student/result.php?attempt_id=' . $attemptId));
        exit;
    }

    // Fetch all questions and their correct options
    $questionsStmt = $pdo->prepare("
        SELECT 
            q.id as question_id,
            q.marks,
            o.id as correct_option_id
        FROM questions q
        JOIN options o ON (q.id = o.question_id AND o.is_correct = 1)
        WHERE q.quiz_id = ?
    ");
    $questionsStmt->execute([$attempt['quiz_id']]);
    $quizQuestions = $questionsStmt->fetchAll();

    // Fetch student's selected answers
    $studentAnsStmt = $pdo->prepare("
        SELECT question_id, selected_option_id 
        FROM attempt_answers 
        WHERE attempt_id = ?
    ");
    $studentAnsStmt->execute([$attemptId]);
    $studentAnswers = $studentAnsStmt->fetchAll(PDO::FETCH_KEY_PAIR); // question_id => selected_option_id

    // Begin evaluation in a secure database transaction
    $pdo->beginTransaction();

    $totalScore = 0.00;
    $totalPossibleMarks = 0.00;
    $correctCount = 0;
    $incorrectCount = 0;
    $unansweredCount = 0;

    $updateAnswerStmt = $pdo->prepare("
        INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id, is_correct, marks_awarded)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            selected_option_id = VALUES(selected_option_id),
            is_correct = VALUES(is_correct),
            marks_awarded = VALUES(marks_awarded),
            updated_at = NOW()
    ");

    foreach ($quizQuestions as $q) {
        $qId = (int)$q['question_id'];
        $marks = (float)$q['marks'];
        $correctOptId = (int)$q['correct_option_id'];
        $totalPossibleMarks += $marks;

        $selectedOptId = isset($studentAnswers[$qId]) ? (int)$studentAnswers[$qId] : null;

        if ($selectedOptId !== null && $selectedOptId > 0) {
            if ($selectedOptId === $correctOptId) {
                $isCorrect = 1;
                $awarded = $marks;
                $totalScore += $marks;
                $correctCount++;
            } else {
                $isCorrect = 0;
                $awarded = 0.00;
                $incorrectCount++;
            }
        } else {
            $isCorrect = 0;
            $awarded = 0.00;
            $unansweredCount++;
            $selectedOptId = null;
        }

        $updateAnswerStmt->execute([$attemptId, $qId, $selectedOptId, $isCorrect, $awarded]);
    }

    // Calculate percentage safely
    $percentage = ($totalPossibleMarks > 0) ? round(($totalScore / $totalPossibleMarks) * 100, 2) : 0.00;

    // Calculate completion time
    $now = time();
    $startTime = strtotime($attempt['started_at']);
    $maxDurationSeconds = (int)$attempt['duration_minutes'] * 60;
    $actualElapsed = max(1, $now - $startTime);
    $timeTakenSeconds = min($actualElapsed, $maxDurationSeconds);

    $finalStatus = $isAutoSubmitted ? 'expired' : 'completed';

    // Update attempt record
    $updateAttemptStmt = $pdo->prepare("
        UPDATE attempts 
        SET 
            status = 'completed',
            submitted_at = NOW(),
            score = ?,
            total_marks = ?,
            percentage = ?,
            time_taken_seconds = ?
        WHERE id = ?
    ");
    $updateAttemptStmt->execute([$totalScore, $totalPossibleMarks, $percentage, $timeTakenSeconds, $attemptId]);

    $pdo->commit();

    set_flash_message('success', 'Your quiz has been evaluated and your results are recorded successfully!');
    header("Location: " . url('student/result.php?attempt_id=' . $attemptId));
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('danger', 'Error submitting quiz: ' . $e->getMessage());
    header("Location: " . url('student/quizzes.php'));
    exit;
}
