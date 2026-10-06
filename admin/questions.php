<?php
/**
 * Manage MCQ Questions Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Manage Questions';
$extraCss = ['dashboard.css'];

$selectedQuizId = (int)($_GET['quiz_id'] ?? 0);
$quizzes = [];
$selectedQuiz = null;
$questions = [];
$groupedOptions = [];

try {
    $pdo = get_db_connection();

    // Fetch all quizzes for selector
    $qStmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY id ASC");
    $quizzes = $qStmt->fetchAll();

    if ($selectedQuizId <= 0 && !empty($quizzes)) {
        $selectedQuizId = (int)$quizzes[0]['id'];
    }

    if ($selectedQuizId > 0) {
        $quizStmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $quizStmt->execute([$selectedQuizId]);
        $selectedQuiz = $quizStmt->fetch();

        // Fetch questions
        $questionsStmt = $pdo->prepare("
            SELECT * FROM questions 
            WHERE quiz_id = ? 
            ORDER BY question_order ASC, id ASC
        ");
        $questionsStmt->execute([$selectedQuizId]);
        $questions = $questionsStmt->fetchAll();

        if (!empty($questions)) {
            $qIds = array_column($questions, 'id');
            $placeholders = implode(',', array_fill(0, count($qIds), '?'));
            $optStmt = $pdo->prepare("
                SELECT * FROM options 
                WHERE question_id IN ($placeholders) 
                ORDER BY id ASC
            ");
            $optStmt->execute($qIds);
            $allOptions = $optStmt->fetchAll();

            foreach ($allOptions as $opt) {
                $groupedOptions[$opt['question_id']][] = $opt;
            }
        }
    }

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading questions: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>MCQ Question Management</h1>
        <p style="margin: 0; color: var(--text-secondary);">Add, edit, reorder multiple-choice questions and designate correct options.</p>
    </div>
    <?php if ($selectedQuiz): ?>
        <a href="<?= url('admin/question-form.php?quiz_id=' . $selectedQuiz['id']) ?>" class="btn btn-primary">
            + Add New Question
        </a>
    <?php endif; ?>
</div>

<!-- Quiz Selector Filter -->
<div class="filter-bar">
    <form method="GET" action="<?= url('admin/questions.php') ?>" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <label class="filter-label" for="quiz_id">Active Quiz:</label>
        <select name="quiz_id" id="quiz_id" class="filter-select" onchange="this.form.submit()">
            <?php foreach ($quizzes as $qz): ?>
                <option value="<?= $qz['id'] ?>" <?= $selectedQuizId === (int)$qz['id'] ? 'selected' : '' ?>>
                    <?= e($qz['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selectedQuiz): ?>
        <div style="font-size: 0.88rem; color: var(--text-secondary);">
            Showing <?= count($questions) ?> question<?= count($questions) !== 1 ? 's' : '' ?> for <strong><?= e($selectedQuiz['title']) ?></strong>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($questions)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
        <div style="font-size: 2.5rem; margin-bottom: 12px; color: var(--text-muted);">&#x2753;</div>
        <h3>No Questions Added Yet</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px;">This quiz doesn't have any MCQ questions yet. Add questions with 4 options to allow students to take the quiz.</p>
        <?php if ($selectedQuiz): ?>
            <a href="<?= url('admin/question-form.php?quiz_id=' . $selectedQuiz['id']) ?>" class="btn btn-primary">+ Add First Question</a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($questions as $idx => $q): ?>
            <?php 
                $opts = $groupedOptions[$q['id']] ?? [];
                $qNum = $idx + 1;
                $letters = ['A', 'B', 'C', 'D'];
            ?>
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header" style="margin-bottom: 16px; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-weight: 800; font-size: 1.1rem; color: var(--primary-light);">
                            #<?= $qNum ?>
                        </span>
                        <span class="badge badge-status badge-secondary">Order: <?= (int)$q['question_order'] ?></span>
                        <span class="badge badge-status badge-info"><?= (int)$q['marks'] ?> Mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <a href="<?= url('admin/question-form.php?id=' . $q['id']) ?>" class="btn btn-secondary btn-sm">
                            Edit Question
                        </a>
                        <form method="POST" action="<?= url('admin/delete-question.php') ?>" onsubmit="return confirm('Are you sure you want to delete this question and its options?');" style="margin: 0;">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="quiz_id" value="<?= $selectedQuizId ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 18px; color: var(--text-primary);">
                    <?= nl2br(e($q['question_text'])) ?>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 10px;">
                    <?php foreach ($opts as $oIdx => $opt): ?>
                        <?php $isCorrect = ($opt['is_correct'] == 1); ?>
                        <div style="padding: 10px 14px; background: <?= $isCorrect ? 'rgba(16, 185, 129, 0.12)' : 'var(--bg-main)' ?>; border: 1px solid <?= $isCorrect ? 'var(--success)' : 'var(--border-color)' ?>; border-radius: var(--radius-sm); font-size: 0.92rem; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span>
                                <strong style="color: <?= $isCorrect ? '#34d399' : 'var(--text-muted)' ?>; margin-right: 6px;">
                                    <?= $letters[$oIdx] ?? ($oIdx + 1) ?>.
                                </strong>
                                <?= e($opt['option_text']) ?>
                            </span>
                            <?php if ($isCorrect): ?>
                                <span class="badge badge-status badge-success" style="font-size: 0.72rem;">✓ Correct</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
