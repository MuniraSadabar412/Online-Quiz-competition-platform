<?php
/**
 * Manage Registered Students Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Manage Students';
$extraCss = ['dashboard.css'];

$search = trim($_GET['search'] ?? '');
$students = [];

try {
    $pdo = get_db_connection();

    $sql = "
        SELECT 
            u.id, u.full_name, u.email, u.created_at,
            COUNT(DISTINCT CASE WHEN a.status IN ('completed', 'expired') THEN a.id END) as completed_attempts,
            COALESCE(AVG(CASE WHEN a.status IN ('completed', 'expired') THEN a.percentage END), 0) as avg_score
        FROM users u
        LEFT JOIN attempts a ON u.id = a.user_id
        WHERE u.role = 'student'
    ";

    $params = [];
    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " GROUP BY u.id ORDER BY u.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading student accounts: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1>Registered Students Directory</h1>
        <p style="margin: 0; color: var(--text-secondary);">Review registered candidate profiles, exam engagement, and performance ratings.</p>
    </div>
</div>

<!-- Search Bar -->
<div class="filter-bar">
    <form method="GET" action="<?= url('admin/students.php') ?>" style="display: flex; gap: 10px; width: 100%; max-width: 450px;">
        <input 
            type="text" 
            name="search" 
            class="form-control" 
            placeholder="Search candidate by name or email..." 
            value="<?= e($search) ?>"
        >
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="<?= url('admin/students.php') ?>" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <div style="font-size: 0.88rem; color: var(--text-secondary);">
        Total Enrolled: <strong><?= count($students) ?> students</strong>
    </div>
</div>

<div class="card">
    <?php if (empty($students)): ?>
        <div style="text-align: center; padding: 48px;">
            <p style="color: var(--text-secondary); margin: 0;">No student accounts found matching your query.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Quizzes Attempted</th>
                        <th>Average Score</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                    <tr>
                        <td style="color: var(--text-muted);">#<?= $stu['id'] ?></td>
                        <td>
                            <strong style="color: var(--text-primary);"><?= e($stu['full_name']) ?></strong>
                        </td>
                        <td><?= e($stu['email']) ?></td>
                        <td>
                            <strong><?= (int)$stu['completed_attempts'] ?></strong> quiz<?= (int)$stu['completed_attempts'] !== 1 ? 'zes' : '' ?>
                        </td>
                        <td>
                            <span class="badge badge-status <?= $stu['avg_score'] >= 50 ? 'badge-success' : 'badge-secondary' ?>">
                                <?= number_format($stu['avg_score'], 1) ?>%
                            </span>
                        </td>
                        <td><?= format_date($stu['created_at']) ?></td>
                        <td>
                            <a href="<?= url('admin/results.php?user_id=' . $stu['id']) ?>" class="btn btn-outline btn-sm">
                                View Submissions &rarr;
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
