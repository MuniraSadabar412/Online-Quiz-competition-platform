<?php
/**
 * Quiz Instructions & Pre-Flight Confirmation
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$quizId = (int)($_GET['quiz_id'] ?? 0);
if ($quizId <= 0) {
    header("Location: " . url('student/quizzes.php'));
    exit;
}

$pageTitle = 'Quiz Instructions';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];

try {
    $pdo = get_db_connection();

    // Fetch quiz info
    $stmt = $pdo->prepare("
        SELECT 
            q.*, 
            COUNT(qu.id) as question_count,
            COALESCE(SUM(qu.marks), 0) as total_marks,
            a.id as attempt_id,
            a.status as attempt_status
        FROM quizzes q
        LEFT JOIN questions qu ON q.id = qu.quiz_id
        LEFT JOIN attempts a ON (q.id = a.quiz_id AND a.user_id = ?)
        WHERE q.id = ? AND q.status = 'published'
        GROUP BY q.id, a.id
    ");
    $stmt->execute([$userId, $quizId]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        set_flash_message('danger', 'Quiz not found or is currently not available.');
        header("Location: " . url('student/quizzes.php'));
        exit;
    }

    // If attempt already completed, redirect directly to result
    if ($quiz['attempt_status'] === 'completed' || $quiz['attempt_status'] === 'expired') {
        set_flash_message('info', 'You have already completed this quiz. Here is your result breakdown.');
        header("Location: " . url('student/result.php?attempt_id=' . $quiz['attempt_id']));
        exit;
    }

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading quiz details: ' . $e->getMessage());
    header("Location: " . url('student/quizzes.php'));
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 20px auto;">
    <div style="margin-bottom: 24px;">
        <a href="<?= url('student/quizzes.php') ?>" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; margin-bottom: 12px;">
            &larr; Back to Available Quizzes
        </a>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
            <span class="quiz-category"><?= e($quiz['category']) ?></span>
            <?= get_difficulty_badge($quiz['difficulty']) ?>
        </div>
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;"><?= e($quiz['title']) ?></h1>
        <p style="color: var(--text-secondary);"><?= e($quiz['description']) ?></p>
    </div>

    <!-- Overview Stats Card -->
    <div class="card" style="padding: 24px; margin-bottom: 28px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; text-align: center;">
            <div style="padding: 12px; background: var(--bg-main); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 1.6rem; font-weight: 800; color: var(--primary-light);"><?= (int)$quiz['duration_minutes'] ?> Mins</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-top: 2px;">Duration</div>
            </div>
            <div style="padding: 12px; background: var(--bg-main); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 1.6rem; font-weight: 800; color: #38bdf8;"><?= (int)$quiz['question_count'] ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-top: 2px;">Questions</div>
            </div>
            <div style="padding: 12px; background: var(--bg-main); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 1.6rem; font-weight: 800; color: var(--success);"><?= (int)$quiz['total_marks'] ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-top: 2px;">Total Marks</div>
            </div>
            <div style="padding: 12px; background: var(--bg-main); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 1.6rem; font-weight: 800; color: #f59e0b;">1</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-top: 2px;">Allowed Attempt</div>
            </div>
        </div>
    </div>

    <!-- Official Rules & Instructions -->
    <div class="card" style="padding: 32px; margin-bottom: 28px;">
        <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Examination Guidelines &amp; Rules
        </h3>

        <ul style="color: var(--text-secondary); font-size: 0.96rem; line-height: 1.7; padding-left: 20px; display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px;">
            <li><strong>Strict Server Timing:</strong> Once you click start, the server begins counting down your <?= (int)$quiz['duration_minutes'] ?> minutes deadline. Refreshing or navigating away will <em>not</em> reset your time limit.</li>
            <li><strong>Live Palette Navigation:</strong> You can move forward, backward, or jump directly between questions using the Question Navigation Palette.</li>
            <li><strong>Answer Autosave:</strong> Your chosen answers are automatically saved to MySQL in real time as you select each radio button.</li>
            <li><strong>Single Attempt Rule:</strong> Only one attempt is permitted per student for this competitive quiz. Once submitted, answers are final.</li>
            <li><strong>Automatic Submission:</strong> If the timer expires before you click Submit, your attempt will automatically be finalized and evaluated on the server.</li>
            <li><strong>Scoring Formula:</strong> Correct answers yield the configured marks. Incorrect or unanswered questions yield 0 marks (no negative marking).</li>
        </ul>

        <form method="POST" action="<?= url('student/start-quiz.php') ?>">
            <?php csrf_input(); ?>
            <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">

            <div style="display: flex; gap: 16px; justify-content: flex-end; align-items: center;">
                <a href="<?= url('student/quizzes.php') ?>" class="btn btn-secondary">
                    Cancel
                </a>

                <?php if ($quiz['attempt_status'] === 'in_progress'): ?>
                    <a href="<?= url('student/take-quiz.php?attempt_id=' . $quiz['attempt_id']) ?>" class="btn btn-warning btn-lg">
                        Resume In-Progress Quiz &rarr;
                    </a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        I Understand &amp; Start Quiz &rarr;
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
