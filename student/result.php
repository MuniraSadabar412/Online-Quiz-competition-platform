<?php
/**
 * Detailed Quiz Result & Analytics
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    header("Location: " . url('student/quizzes.php'));
    exit;
}

$user = current_user();

try {
    $pdo = get_db_connection();

    // Fetch attempt details
    // If student, enforce user_id; if admin, allow viewing
    $sql = "
        SELECT 
            a.*,
            q.title as quiz_title,
            q.category,
            q.difficulty,
            u.full_name as student_name,
            u.email as student_email
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?
    ";
    $params = [$attemptId];

    if (!is_admin()) {
        $sql .= " AND a.user_id = ?";
        $params[] = $user['id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        set_flash_message('danger', 'Result not found or access denied.');
        header("Location: " . (is_admin() ? url('admin/results.php') : url('student/quizzes.php')));
        exit;
    }

    // Fetch questions and answer review
    $reviewStmt = $pdo->prepare("
        SELECT 
            q.id as question_id,
            q.question_text,
            q.marks,
            q.question_order,
            aa.selected_option_id,
            aa.is_correct as student_is_correct,
            aa.marks_awarded
        FROM questions q
        LEFT JOIN attempt_answers aa ON (q.id = aa.question_id AND aa.attempt_id = ?)
        WHERE q.quiz_id = ?
        ORDER BY q.question_order ASC, q.id ASC
    ");
    $reviewStmt->execute([$attemptId, $attempt['quiz_id']]);
    $reviewQuestions = $reviewStmt->fetchAll();

    // Fetch options for all questions
    $qIds = array_column($reviewQuestions, 'question_id');
    $groupedOptions = [];

    if (!empty($qIds)) {
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $optStmt = $pdo->prepare("
            SELECT id, question_id, option_text, is_correct 
            FROM options 
            WHERE question_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $optStmt->execute($qIds);
        $allOptions = $optStmt->fetchAll();

        foreach ($allOptions as $opt) {
            $groupedOptions[$opt['question_id']][] = $opt;
        }
    }

    // Counts
    $correctCount = 0;
    $incorrectCount = 0;
    $unansweredCount = 0;

    foreach ($reviewQuestions as $rq) {
        if ($rq['selected_option_id'] === null) {
            $unansweredCount++;
        } elseif ($rq['student_is_correct'] == 1) {
            $correctCount++;
        } else {
            $incorrectCount++;
        }
    }

    $feedback = get_performance_feedback((float)$attempt['percentage']);

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading result details: ' . $e->getMessage());
    header("Location: " . url('student/dashboard.php'));
    exit;
}

$pageTitle = 'Result: ' . $attempt['quiz_title'];
$extraCss = ['quiz.css', 'dashboard.css'];

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <span class="quiz-category"><?= e($attempt['category']) ?></span>
            <h1 style="font-size: 2.1rem; margin-top: 4px; margin-bottom: 2px;">
                Quiz Performance Analysis
            </h1>
            <p style="margin: 0; color: var(--text-secondary);">
                <?= e($attempt['quiz_title']) ?> &bull; Completed by <strong><?= e($attempt['student_name']) ?></strong> on <?= format_date($attempt['submitted_at']) ?>
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="<?= url('student/leaderboard.php?quiz_id=' . $attempt['quiz_id']) ?>" class="btn btn-outline btn-sm">
                View Leaderboard
            </a>
            <a href="<?= is_admin() ? url('admin/results.php') : url('student/dashboard.php') ?>" class="btn btn-secondary btn-sm">
                &larr; Dashboard
            </a>
        </div>
    </div>

    <!-- Hero Score Summary -->
    <div class="result-hero">
        <div class="score-circle">
            <span class="score-number"><?= number_format($attempt['score'], 0) ?></span>
            <span class="score-max">/ <?= number_format($attempt['total_marks'], 0) ?> Marks</span>
        </div>

        <div class="result-percentage"><?= number_format($attempt['percentage'], 1) ?>% Accuracy</div>
        <h2 style="font-size: 1.5rem; margin-bottom: 8px;"><?= e($feedback['title']) ?></h2>
        <p style="max-width: 540px; margin: 0 auto; color: var(--text-secondary);"><?= e($feedback['desc']) ?></p>
    </div>

    <!-- Breakdown Metric Grid -->
    <div class="breakdown-grid">
        <div class="breakdown-card correct">
            <div class="val"><?= $correctCount ?></div>
            <div class="lbl">Correct Answers</div>
        </div>
        <div class="breakdown-card incorrect">
            <div class="val"><?= $incorrectCount ?></div>
            <div class="lbl">Incorrect Answers</div>
        </div>
        <div class="breakdown-card unanswered">
            <div class="val"><?= $unansweredCount ?></div>
            <div class="lbl">Unanswered</div>
        </div>
        <div class="breakdown-card time">
            <div class="val"><?= format_duration((int)$attempt['time_taken_seconds']) ?></div>
            <div class="lbl">Time Elapsed</div>
        </div>
    </div>

    <!-- Question-by-Question Review -->
    <div class="card" style="padding: 32px;">
        <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            Comprehensive Question Review
        </h3>

        <?php foreach ($reviewQuestions as $idx => $q): ?>
            <?php 
                $qNum = $idx + 1;
                $opts = $groupedOptions[$q['question_id']] ?? [];
                $selOptId = (int)$q['selected_option_id'];
                $isCorrect = ($q['student_is_correct'] == 1);
                $isUnanswered = empty($selOptId);
            ?>
            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 22px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-weight: 700; color: var(--text-muted); font-size: 0.88rem;">
                        Question <?= $qNum ?>
                    </span>
                    <div>
                        <?php if ($isUnanswered): ?>
                            <span class="badge badge-status badge-warning">Unanswered (0 / <?= (int)$q['marks'] ?> pts)</span>
                        <?php elseif ($isCorrect): ?>
                            <span class="badge badge-status badge-success">+<?= (int)$q['marks_awarded'] ?> / <?= (int)$q['marks'] ?> pts</span>
                        <?php else: ?>
                            <span class="badge badge-status badge-danger">0 / <?= (int)$q['marks'] ?> pts</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="font-size: 1.05rem; font-weight: 600; margin-bottom: 18px; color: var(--text-primary);">
                    <?= nl2br(e($q['question_text'])) ?>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($opts as $opt): ?>
                        <?php 
                            $optId = (int)$opt['id'];
                            $isThisCorrect = ($opt['is_correct'] == 1);
                            $isThisSelected = ($selOptId === $optId);

                            $itemBg = 'var(--bg-surface)';
                            $itemBorder = 'var(--border-color)';
                            $textColor = 'var(--text-secondary)';
                            $badgeHtml = '';

                            if ($isThisCorrect) {
                                $itemBg = 'rgba(16, 185, 129, 0.12)';
                                $itemBorder = 'var(--success)';
                                $textColor = '#34d399';
                                $badgeHtml = '<span class="badge badge-status badge-success" style="margin-left: auto;">Correct Option</span>';
                            }
                            
                            if ($isThisSelected && !$isThisCorrect) {
                                $itemBg = 'rgba(239, 68, 68, 0.12)';
                                $itemBorder = 'var(--danger)';
                                $textColor = '#f87171';
                                $badgeHtml = '<span class="badge badge-status badge-danger" style="margin-left: auto;">Your Choice</span>';
                            } elseif ($isThisSelected && $isThisCorrect) {
                                $badgeHtml = '<span class="badge badge-status badge-success" style="margin-left: auto;">Your Choice (Correct)</span>';
                            }
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: <?= $itemBg ?>; border: 1px solid <?= $itemBorder ?>; border-radius: var(--radius-sm); font-size: 0.94rem; color: <?= $textColor ?>;">
                            <span style="font-weight: 700; width: 24px; text-align: center;">
                                <?= $isThisSelected ? '●' : '○' ?>
                            </span>
                            <span><?= e($opt['option_text']) ?></span>
                            <?= $badgeHtml ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 32px; display: flex; gap: 16px; justify-content: center;">
            <a href="<?= url('student/leaderboard.php?quiz_id=' . $attempt['quiz_id']) ?>" class="btn btn-primary btn-lg">
                Compare on Leaderboard &rarr;
            </a>
            <a href="<?= url('student/dashboard.php') ?>" class="btn btn-secondary btn-lg">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
