<?php
/**
 * All Quiz Submissions & Attempts Log
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'All Results & Attempts';
$extraCss = ['dashboard.css'];

$selectedQuizId = (int)($_GET['quiz_id'] ?? 0);
$selectedUserId = (int)($_GET['user_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$quizzes = [];
$attempts = [];

try {
    $pdo = get_db_connection();

    $qStmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY id ASC");
    $quizzes = $qStmt->fetchAll();

    $sql = "
        SELECT 
            a.*,
            u.full_name as student_name,
            u.email as student_email,
            q.title as quiz_title,
            q.category
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.status IN ('completed', 'expired')
    ";

    $params = [];

    if ($selectedQuizId > 0) {
        $sql .= " AND a.quiz_id = ?";
        $params[] = $selectedQuizId;
    }

    if ($selectedUserId > 0) {
        $sql .= " AND a.user_id = ?";
        $params[] = $selectedUserId;
    }

    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR q.title LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY a.submitted_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attempts = $stmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading exam results: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>All Submissions &amp; Results</h1>
        <p style="margin: 0; color: var(--text-secondary);">Comprehensive ledger of completed quiz attempts across all candidates.</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="<?= url('admin/results.php') ?>" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <label class="filter-label" for="quiz_id">Filter Quiz:</label>
        <select name="quiz_id" id="quiz_id" class="filter-select" onchange="this.form.submit()">
            <option value="0">All Quizzes</option>
            <?php foreach ($quizzes as $qz): ?>
                <option value="<?= $qz['id'] ?>" <?= $selectedQuizId === (int)$qz['id'] ? 'selected' : '' ?>>
                    <?= e($qz['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input 
            type="text" 
            name="search" 
            class="form-control" 
            style="width: 220px; padding: 7px 12px; font-size: 0.88rem;" 
            placeholder="Search student or quiz..." 
            value="<?= e($search) ?>"
        >

        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if ($selectedQuizId > 0 || $selectedUserId > 0 || !empty($search)): ?>
            <a href="<?= url('admin/results.php') ?>" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <div style="font-size: 0.88rem; color: var(--text-secondary);">
        Submissions: <strong><?= count($attempts) ?> records</strong>
    </div>
</div>

<div class="card">
    <?php if (empty($attempts)): ?>
        <div style="text-align: center; padding: 48px;">
            <p style="color: var(--text-secondary); margin: 0;">No completed quiz submissions match your filter criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Quiz Title</th>
                        <th>Score</th>
                        <th>Accuracy</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $idx => $att): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $idx + 1 ?></td>
                        <td>
                            <strong style="color: var(--text-primary);"><?= e($att['student_name']) ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($att['student_email']) ?></div>
                        </td>
                        <td><?= e($att['quiz_title']) ?></td>
                        <td style="font-weight: 700; color: var(--primary-light);">
                            <?= number_format($att['score'], 0) ?> / <?= number_format($att['total_marks'], 0) ?>
                        </td>
                        <td>
                            <span class="badge badge-status <?= $att['percentage'] >= 50 ? 'badge-success' : 'badge-danger' ?>">
                                <?= number_format($att['percentage'], 1) ?>%
                            </span>
                        </td>
                        <td><?= format_duration((int)$att['time_taken_seconds']) ?></td>
                        <td><?= get_status_badge($att['status']) ?></td>
                        <td><?= format_date($att['submitted_at']) ?></td>
                        <td>
                            <a href="<?= url('student/result.php?attempt_id=' . $att['id']) ?>" class="btn btn-outline btn-sm" title="Detailed Question Review">
                                Detailed Review &rarr;
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
