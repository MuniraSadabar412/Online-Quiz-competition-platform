<?php
/**
 * Manage Quizzes Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Manage Quizzes';
$extraCss = ['dashboard.css'];

$statusFilter = trim($_GET['status'] ?? '');
$quizzes = [];

try {
    $pdo = get_db_connection();

    $sql = "
        SELECT 
            q.*,
            COUNT(DISTINCT qu.id) as question_count,
            COALESCE(SUM(qu.marks), 0) as total_marks,
            COUNT(DISTINCT a.id) as attempt_count
        FROM quizzes q
        LEFT JOIN questions qu ON q.id = qu.quiz_id
        LEFT JOIN attempts a ON (q.id = a.quiz_id AND a.status IN ('completed', 'expired'))
    ";

    $params = [];
    if (!empty($statusFilter)) {
        $sql .= " WHERE q.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " GROUP BY q.id ORDER BY q.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading quizzes: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Quiz Management</h1>
        <p style="margin: 0; color: var(--text-secondary);">Create, update, organize questions, and toggle publication status.</p>
    </div>
    <a href="<?= url('admin/quiz-form.php') ?>" class="btn btn-primary">
        + Create New Quiz
    </a>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="filter-group">
        <span class="filter-label">Filter Status:</span>
        <a href="<?= url('admin/quizzes.php') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline' ?>">All (<?= count($quizzes) ?>)</a>
        <a href="<?= url('admin/quizzes.php?status=published') ?>" class="btn btn-sm <?= $statusFilter === 'published' ? 'btn-primary' : 'btn-outline' ?>">Published</a>
        <a href="<?= url('admin/quizzes.php?status=draft') ?>" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-outline' ?>">Drafts</a>
        <a href="<?= url('admin/quizzes.php?status=inactive') ?>" class="btn btn-sm <?= $statusFilter === 'inactive' ? 'btn-primary' : 'btn-outline' ?>">Inactive</a>
    </div>
</div>

<div class="card">
    <?php if (empty($quizzes)): ?>
        <div style="text-align: center; padding: 48px;">
            <p style="color: var(--text-secondary); margin-bottom: 16px;">No quizzes found matching the selected criteria.</p>
            <a href="<?= url('admin/quiz-form.php') ?>" class="btn btn-primary">Create Your First Quiz &rarr;</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Quiz Title</th>
                        <th>Category</th>
                        <th>Difficulty</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Questions</th>
                        <th>Total Marks</th>
                        <th>Attempts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizzes as $q): ?>
                    <tr>
                        <td style="color: var(--text-muted);">#<?= $q['id'] ?></td>
                        <td>
                            <strong style="color: var(--text-primary);"><?= e($q['title']) ?></strong>
                        </td>
                        <td><span class="quiz-category"><?= e($q['category']) ?></span></td>
                        <td><?= get_difficulty_badge($q['difficulty']) ?></td>
                        <td><?= (int)$q['duration_minutes'] ?> mins</td>
                        <td><?= get_status_badge($q['status']) ?></td>
                        <td>
                            <a href="<?= url('admin/questions.php?quiz_id=' . $q['id']) ?>" style="font-weight: 700; text-decoration: underline;">
                                <?= (int)$q['question_count'] ?> MCQs
                            </a>
                        </td>
                        <td><?= (int)$q['total_marks'] ?> pts</td>
                        <td><?= (int)$q['attempt_count'] ?></td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="<?= url('admin/questions.php?quiz_id=' . $q['id']) ?>" class="btn btn-outline btn-sm" title="Manage MCQs">
                                    Questions
                                </a>
                                <a href="<?= url('admin/quiz-form.php?id=' . $q['id']) ?>" class="btn btn-secondary btn-sm" title="Edit Quiz Settings">
                                    Edit
                                </a>
                                <form method="POST" action="<?= url('admin/delete-quiz.php') ?>" onsubmit="return confirm('Are you sure you want to delete this quiz? All associated questions, options, and student attempts will be permanently deleted.');" style="margin: 0;">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="quiz_id" value="<?= $q['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Quiz">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
