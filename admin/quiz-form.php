<?php
/**
 * Create / Edit Quiz Form
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$quizId = (int)($_GET['id'] ?? 0);
$isEditing = ($quizId > 0);
$pageTitle = $isEditing ? 'Edit Quiz' : 'Create New Quiz';
$extraCss = ['dashboard.css'];

$user = current_user();
$errors = [];

// Form values
$title = '';
$description = '';
$category = '';
$difficulty = 'medium';
$durationMinutes = 15;
$status = 'draft';

try {
    $pdo = get_db_connection();

    if ($isEditing) {
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$quizId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            set_flash_message('danger', 'Quiz not found.');
            header("Location: " . url('admin/quizzes.php'));
            exit;
        }

        $title = $quiz['title'];
        $description = $quiz['description'];
        $category = $quiz['category'];
        $difficulty = $quiz['difficulty'];
        $durationMinutes = (int)$quiz['duration_minutes'];
        $status = $quiz['status'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf_post();

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $durationMinutes = (int)($_POST['duration_minutes'] ?? 15);
        $status = $_POST['status'] ?? 'draft';

        // Validation
        if (empty($title) || mb_strlen($title) < 3) {
            $errors[] = "Quiz title must be at least 3 characters long.";
        }
        if (empty($category)) {
            $errors[] = "Please specify a subject or category (e.g., Programming, Web Development).";
        }
        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $errors[] = "Invalid difficulty rating.";
        }
        if ($durationMinutes < 1 || $durationMinutes > 300) {
            $errors[] = "Duration must be between 1 and 300 minutes.";
        }
        if (!in_array($status, ['draft', 'published', 'inactive'])) {
            $errors[] = "Invalid publication status.";
        }

        if (empty($errors)) {
            if ($isEditing) {
                $updateStmt = $pdo->prepare("
                    UPDATE quizzes 
                    SET title = ?, description = ?, category = ?, difficulty = ?, duration_minutes = ?, status = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$title, $description, $category, $difficulty, $durationMinutes, $status, $quizId]);
                set_flash_message('success', "Quiz '{$title}' updated successfully.");
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO quizzes (title, description, category, difficulty, duration_minutes, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$title, $description, $category, $difficulty, $durationMinutes, $status, $user['id']]);
                $quizId = (int)$pdo->lastInsertId();
                set_flash_message('success', "Quiz '{$title}' created successfully! You can now add questions.");
            }

            header("Location: " . url('admin/questions.php?quiz_id=' . $quizId));
            exit;
        }
    }

} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 750px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <a href="<?= url('admin/quizzes.php') ?>" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; margin-bottom: 12px;">
            &larr; Back to Quizzes List
        </a>
        <h1><?= $isEditing ? 'Edit Quiz Settings' : 'Create New Competition Quiz' ?></h1>
        <p style="color: var(--text-secondary);">Configure the assessment parameters, timing constraints, and category.</p>
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
                <label class="form-label" for="title">Quiz Title *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="form-control" 
                    placeholder="e.g. Advanced Object-Oriented Programming" 
                    value="<?= e($title) ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category / Subject *</label>
                <input 
                    type="text" 
                    id="category" 
                    name="category" 
                    class="form-control" 
                    placeholder="e.g. Programming, Web Development, Database Management" 
                    value="<?= e($category) ?>" 
                    required
                >
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="difficulty">Difficulty Level</label>
                    <select name="difficulty" id="difficulty" class="form-control">
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="duration_minutes">Time Limit (Minutes) *</label>
                    <input 
                        type="number" 
                        id="duration_minutes" 
                        name="duration_minutes" 
                        class="form-control" 
                        min="1" 
                        max="300" 
                        value="<?= $durationMinutes ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Publication Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Active for Students)</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Archived)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Quiz Description / Syllabus Overview</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-control" 
                    rows="4" 
                    placeholder="Briefly describe the syllabus, question topics, and target audience..."
                ><?= e($description) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="<?= url('admin/quizzes.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEditing ? 'Save Changes' : 'Create &amp; Add Questions &rarr;' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
