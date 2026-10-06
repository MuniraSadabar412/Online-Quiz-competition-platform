<?php
/**
 * Active Quiz Interface
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    header("Location: " . url('student/quizzes.php'));
    exit;
}

$user = current_user();
$userId = $user['id'];

try {
    $pdo = get_db_connection();

    // Fetch attempt details with quiz
    $attemptStmt = $pdo->prepare("
        SELECT 
            a.*,
            q.title as quiz_title,
            q.category,
            q.duration_minutes
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $attemptStmt->execute([$attemptId, $userId]);
    $attempt = $attemptStmt->fetch();

    if (!$attempt) {
        set_flash_message('danger', 'Quiz attempt not found or unauthorized access.');
        header("Location: " . url('student/quizzes.php'));
        exit;
    }

    // If attempt is already completed or expired, redirect to result
    if ($attempt['status'] === 'completed' || $attempt['status'] === 'expired') {
        header("Location: " . url('student/result.php?attempt_id=' . $attemptId));
        exit;
    }

    // Server-side deadline check (allow 10 seconds grace period for network jitter)
    $now = time();
    $deadlineTs = strtotime($attempt['deadline_at']);
    if ($now > ($deadlineTs + 10)) {
        // Auto-finalize expired attempt
        header("Location: " . url('student/submit-quiz.php?attempt_id=' . $attemptId . '&auto_submitted=1'));
        exit;
    }

    // Fetch all questions for this quiz
    $questionsStmt = $pdo->prepare("
        SELECT id, question_text, marks, question_order 
        FROM questions 
        WHERE quiz_id = ? 
        ORDER BY question_order ASC, id ASC
    ");
    $questionsStmt->execute([$attempt['quiz_id']]);
    $questions = $questionsStmt->fetchAll();

    if (empty($questions)) {
        set_flash_message('warning', 'This quiz does not have any questions yet.');
        header("Location: " . url('student/quizzes.php'));
        exit;
    }

    // Fetch all options for these questions (WITHOUT is_correct column for security!)
    $qIds = array_column($questions, 'id');
    $groupedOptions = [];

    if (!empty($qIds)) {
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $optionsStmt = $pdo->prepare("
            SELECT id, question_id, option_text 
            FROM options 
            WHERE question_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $optionsStmt->execute($qIds);
        $allOptions = $optionsStmt->fetchAll();

        // Group options by question_id
        foreach ($allOptions as $opt) {
            $groupedOptions[$opt['question_id']][] = $opt;
        }
    }

    // Fetch any saved answers for this attempt
    $answersStmt = $pdo->prepare("
        SELECT question_id, selected_option_id 
        FROM attempt_answers 
        WHERE attempt_id = ?
    ");
    $answersStmt->execute([$attemptId]);
    $savedAnswers = $answersStmt->fetchAll(PDO::FETCH_KEY_PAIR); // question_id => selected_option_id

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading quiz session: ' . $e->getMessage());
    header("Location: " . url('student/quizzes.php'));
    exit;
}

$pageTitle = 'Attempting: ' . $attempt['quiz_title'];
$extraCss = ['quiz.css'];
$extraJs = ['timer.js', 'quiz.js'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Quiz Top Header Bar -->
<div class="quiz-top-bar">
    <div class="quiz-title-area">
        <h2><?= e($attempt['quiz_title']) ?></h2>
        <span id="answeredCountDisplay">0 of <?= count($questions) ?> answered</span>
    </div>

    <div class="timer-box" id="timerBox">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 14 14"></polyline>
        </svg>
        <span id="timerDisplay">--:--</span>
    </div>
</div>

<!-- Main Quiz Layout (Question + Palette) -->
<div class="quiz-layout">
    <!-- Question Cards Area -->
    <div class="question-container">
        <?php foreach ($questions as $idx => $q): ?>
            <?php 
                $qNum = $idx + 1;
                $opts = $groupedOptions[$q['id']] ?? [];
                $savedOptId = $savedAnswers[$q['id']] ?? null;
                $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
            ?>
            <div class="question-card" id="question_card_<?= $idx ?>" style="<?= $idx === 0 ? 'display: block;' : 'display: none;' ?>">
                <div class="question-header">
                    <span class="question-num-tag">Question <?= $qNum ?> of <?= count($questions) ?></span>
                    <span class="question-marks"><?= (int)$q['marks'] ?> Mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                </div>

                <div class="question-text">
                    <?= nl2br(e($q['question_text'])) ?>
                </div>

                <div class="options-list">
                    <?php foreach ($opts as $oIdx => $opt): ?>
                        <label class="option-item">
                            <input 
                                type="radio" 
                                name="question_<?= $q['id'] ?>" 
                                value="<?= $opt['id'] ?>"
                                data-question-id="<?= $q['id'] ?>"
                                <?= ($savedOptId && (int)$savedOptId === (int)$opt['id']) ? 'checked' : '' ?>
                            >
                            <span class="option-label">
                                <span class="option-letter"><?= $letters[$oIdx] ?? ($oIdx + 1) ?></span>
                                <span><?= e($opt['option_text']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Nav Controls -->
        <div class="question-actions">
            <button type="button" id="prevQuestionBtn" class="btn btn-secondary">
                &larr; Previous
            </button>
            <div style="display: flex; gap: 12px;">
                <button type="button" id="nextQuestionBtn" class="btn btn-secondary">
                    Next &rarr;
                </button>
                <button type="button" id="reviewSubmitBtn" class="btn btn-primary">
                    Submit Quiz
                </button>
            </div>
        </div>
    </div>

    <!-- Right Sidebar / Question Palette -->
    <aside class="quiz-sidebar">
        <h3 class="palette-title">Question Palette</h3>
        <div class="palette-grid">
            <?php foreach ($questions as $idx => $q): ?>
                <button 
                    type="button" 
                    class="palette-btn <?= isset($savedAnswers[$q['id']]) ? 'answered' : '' ?> <?= $idx === 0 ? 'current' : '' ?>"
                    data-index="<?= $idx ?>"
                >
                    <?= $idx + 1 ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="palette-legend">
            <div class="legend-item">
                <span class="legend-dot answered"></span>
                <span>Answered</span>
            </div>
            <div class="legend-item">
                <span class="legend-dot unanswered"></span>
                <span>Unanswered</span>
            </div>
            <div class="legend-item">
                <span class="legend-dot current"></span>
                <span>Current Question</span>
            </div>
        </div>

        <button type="button" onclick="openModal('submissionModal')" class="btn btn-primary btn-block">
            Finalize &amp; Submit
        </button>
    </aside>
</div>

<!-- Submission Confirmation Modal -->
<div class="modal-overlay" id="submissionModal">
    <div class="modal-content">
        <h3 style="font-size: 1.35rem; margin-bottom: 12px;">Confirm Quiz Submission</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px;">
            Are you sure you are ready to finalize and submit your quiz attempt?
        </p>

        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px; display: flex; justify-content: space-around; text-align: center;">
            <div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--success);" id="modalAnsweredCount">0</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Answered</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--warning);" id="modalUnansweredCount">0</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Unanswered</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-light);"><?= count($questions) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Total</div>
            </div>
        </div>

        <p style="font-size: 0.86rem; color: var(--text-muted); margin-bottom: 24px;">
            <em>Note: Once submitted, your answers cannot be altered. Your score and performance ranking will be evaluated immediately.</em>
        </p>

        <form id="quizSubmitForm" method="POST" action="<?= url('student/submit-quiz.php') ?>">
            <?php csrf_input(); ?>
            <input type="hidden" name="attempt_id" value="<?= $attempt['id'] ?>">

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('submissionModal')">
                    Continue Quiz
                </button>
                <button type="submit" class="btn btn-primary">
                    Yes, Submit Attempt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Quiz Engine
    const deadlineEpochMs = <?= strtotime($attempt['deadline_at']) * 1000 ?>;
    
    window.quizApp = new QuizEngine({
        attemptId: <?= $attempt['id'] ?>,
        totalQuestions: <?= count($questions) ?>,
        csrfToken: '<?= generate_csrf_token() ?>',
        saveAnswerUrl: '<?= url('student/save-answer.php') ?>',
        submitQuizUrl: '<?= url('student/submit-quiz.php') ?>',
        deadlineEpochMs: deadlineEpochMs
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
