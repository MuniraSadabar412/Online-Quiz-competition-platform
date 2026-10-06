<?php
/**
 * Add / Edit MCQ Question Form
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$questionId = (int)($_GET['id'] ?? 0);
$quizId = (int)($_GET['quiz_id'] ?? 0);
$isEditing = ($questionId > 0);
$pageTitle = $isEditing ? 'Edit MCQ Question' : 'Add MCQ Question';
$extraCss = ['dashboard.css'];

$quizzes = [];
$errors = [];

// Form values
$questionText = '';
$marks = 1;
$questionOrder = 1;
$options = ['', '', '', ''];
$correctOptionIdx = 0; // 0 to 3

try {
    $pdo = get_db_connection();

    $qListStmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY id ASC");
    $quizzes = $qListStmt->fetchAll();

    if ($isEditing) {
        $qStmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $qStmt->execute([$questionId]);
        $question = $qStmt->fetch();

        if (!$question) {
            set_flash_message('danger', 'Question not found.');
            header("Location: " . url('admin/questions.php'));
            exit;
        }

        $quizId = (int)$question['quiz_id'];
        $questionText = $question['question_text'];
        $marks = (int)$question['marks'];
        $questionOrder = (int)$question['question_order'];

        // Fetch existing options
        $optStmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY id ASC");
        $optStmt->execute([$questionId]);
        $existingOpts = $optStmt->fetchAll();

        foreach ($existingOpts as $idx => $eo) {
            if ($idx < 4) {
                $options[$idx] = $eo['option_text'];
                if ($eo['is_correct'] == 1) {
                    $correctOptionIdx = $idx;
                }
            }
        }
    } elseif ($quizId > 0) {
        // Find next question order
        $maxOrderStmt = $pdo->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 FROM questions WHERE quiz_id = ?");
        $maxOrderStmt->execute([$quizId]);
        $questionOrder = (int)$maxOrderStmt->fetchColumn();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf_post();

        $quizId = (int)($_POST['quiz_id'] ?? 0);
        $questionText = trim($_POST['question_text'] ?? '');
        $marks = (int)($_POST['marks'] ?? 1);
        $questionOrder = (int)($_POST['question_order'] ?? 1);
        $correctOptionIdx = (int)($_POST['correct_option'] ?? 0);

        $postedOptions = $_POST['options'] ?? [];
        for ($i = 0; $i < 4; $i++) {
            $options[$i] = trim($postedOptions[$i] ?? '');
        }

        // Validation
        if ($quizId <= 0) {
            $errors[] = "Please select a valid quiz.";
        }
        if (empty($questionText)) {
            $errors[] = "Question text cannot be blank.";
        }
        if ($marks < 1) {
            $errors[] = "Question marks must be at least 1.";
        }
        for ($i = 0; $i < 4; $i++) {
            if (empty($options[$i])) {
                $optNum = $i + 1;
                $errors[] = "Option {$optNum} cannot be blank. All 4 options are required.";
            }
        }
        if ($correctOptionIdx < 0 || $correctOptionIdx > 3) {
            $errors[] = "Please designate one valid correct option.";
        }

        if (empty($errors)) {
            $pdo->beginTransaction();

            if ($isEditing) {
                // Update question
                $updateQ = $pdo->prepare("
                    UPDATE questions 
                    SET quiz_id = ?, question_text = ?, marks = ?, question_order = ?
                    WHERE id = ?
                ");
                $updateQ->execute([$quizId, $questionText, $marks, $questionOrder, $questionId]);

                // Clear old options and re-insert 4 clean options
                $delOpt = $pdo->prepare("DELETE FROM options WHERE question_id = ?");
                $delOpt->execute([$questionId]);

                $optInsert = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                for ($i = 0; $i < 4; $i++) {
                    $isCorrect = ($i === $correctOptionIdx) ? 1 : 0;
                    $optInsert->execute([$questionId, $options[$i], $isCorrect]);
                }

                $pdo->commit();
                set_flash_message('success', 'Question and options updated successfully.');
            } else {
                // Insert question
                $insertQ = $pdo->prepare("
                    INSERT INTO questions (quiz_id, question_text, marks, question_order)
                    VALUES (?, ?, ?, ?)
                ");
                $insertQ->execute([$quizId, $questionText, $marks, $questionOrder]);
                $questionId = (int)$pdo->lastInsertId();

                // Insert 4 options
                $optInsert = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                for ($i = 0; $i < 4; $i++) {
                    $isCorrect = ($i === $correctOptionIdx) ? 1 : 0;
                    $optInsert->execute([$questionId, $options[$i], $isCorrect]);
                }

                $pdo->commit();
                set_flash_message('success', 'New MCQ question added successfully!');
            }

            header("Location: " . url('admin/questions.php?quiz_id=' . $quizId));
            exit;
        }
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errors[] = "Error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="<?= url('admin/questions.php?quiz_id=' . $quizId) ?>" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; margin-bottom: 12px;">
            &larr; Back to Questions List
        </a>
        <h1><?= $isEditing ? 'Edit MCQ Question' : 'Add New MCQ Question' ?></h1>
        <p style="color: var(--text-secondary);">Provide the question statement, 4 candidate choices, and select the single correct answer.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 32px;">
        <form method="POST">
            <?php csrf_input(); ?>

            <div class="form-group">
                <label class="form-label" for="quiz_id">Target Quiz *</label>
                <select name="quiz_id" id="quiz_id" class="form-control" required>
                    <option value="">-- Choose Quiz --</option>
                    <?php foreach ($quizzes as $qz): ?>
                        <option value="<?= $qz['id'] ?>" <?= $quizId === (int)$qz['id'] ? 'selected' : '' ?>>
                            <?= e($qz['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="question_text">Question Statement *</label>
                <textarea 
                    id="question_text" 
                    name="question_text" 
                    class="form-control" 
                    rows="3" 
                    placeholder="Enter the question clearly..." 
                    required
                ><?= e($questionText) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="marks">Marks / Score Weight *</label>
                    <input 
                        type="number" 
                        id="marks" 
                        name="marks" 
                        class="form-control" 
                        min="1" 
                        max="20" 
                        value="<?= $marks ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="question_order">Display Order</label>
                    <input 
                        type="number" 
                        id="question_order" 
                        name="question_order" 
                        class="form-control" 
                        min="1" 
                        value="<?= $questionOrder ?>" 
                        required
                    >
                </div>
            </div>

            <div style="margin-top: 16px; margin-bottom: 24px;">
                <h4 style="margin-bottom: 6px;">Answer Options (Designate exactly one correct choice)</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                    Select the radio button beside the option that is the correct answer.
                </p>

                <?php 
                    $labels = ['Option A', 'Option B', 'Option C', 'Option D'];
                ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div style="display: flex; align-items: center; gap: 12px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 10px 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap;">
                                <input 
                                    type="radio" 
                                    name="correct_option" 
                                    value="<?= $i ?>" 
                                    <?= ($correctOptionIdx === $i) ? 'checked' : '' ?>
                                    required
                                >
                                <span style="font-weight: 700; color: var(--primary-light);"><?= $labels[$i] ?>:</span>
                            </label>
                            <input 
                                type="text" 
                                name="options[]" 
                                class="form-control" 
                                style="margin: 0; background: var(--bg-surface);"
                                placeholder="Enter text for <?= $labels[$i] ?>" 
                                value="<?= e($options[$i]) ?>" 
                                required
                            >
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="<?= url('admin/questions.php?quiz_id=' . $quizId) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEditing ? 'Save Question Changes' : 'Save Question &rarr;' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
