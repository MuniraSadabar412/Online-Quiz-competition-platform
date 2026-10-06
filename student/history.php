<?php
/**
 * Student Quiz History Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$pageTitle = 'My Quiz History';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];
$attempts = [];

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            q.title as quiz_title,
            q.category,
            q.difficulty
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.user_id = ? AND a.status IN ('completed', 'expired')
        ORDER BY a.submitted_at DESC
    ");
    $stmt->execute([$userId]);
    $attempts = $stmt->fetchAll();
} catch (Exception $e) {
    set_flash_message('danger', 'Error loading history: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
    <h1>My Assessment History</h1>
    <p>Track your past quiz attempts, historical accuracy ratings, and performance breakdowns.</p>
</div>

<div class="card">
    <?php if (empty($attempts)): ?>
        <div style="text-align: center; padding: 48px 20px;">
            <div style="font-size: 2.5rem; margin-bottom: 12px; color: var(--text-muted);">&#x1F4CB;</div>
            <h3 style="margin-bottom: 8px;">No Quiz Attempts Recorded Yet</h3>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">You haven't participated in any quizzes yet. Start your first competition now!</p>
            <a href="<?= url('student/quizzes.php') ?>" class="btn btn-primary">Browse Available Quizzes &rarr;</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quiz Title</th>
                        <th>Category</th>
                        <th>Difficulty</th>
                        <th>Score Obtained</th>
                        <th>Percentage</th>
                        <th>Time Elapsed</th>
                        <th>Date Completed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $idx => $att): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $idx + 1 ?></td>
                        <td style="font-weight: 600; color: var(--text-primary);"><?= e($att['quiz_title']) ?></td>
                        <td><span class="quiz-category"><?= e($att['category']) ?></span></td>
                        <td><?= get_difficulty_badge($att['difficulty']) ?></td>
                        <td style="font-weight: 700; color: var(--primary-light);">
                            <?= number_format($att['score'], 0) ?> / <?= number_format($att['total_marks'], 0) ?>
                        </td>
                        <td>
                            <span class="badge badge-status <?= $att['percentage'] >= 50 ? 'badge-success' : 'badge-danger' ?>">
                                <?= number_format($att['percentage'], 1) ?>%
                            </span>
                        </td>
                        <td><?= format_duration((int)$att['time_taken_seconds']) ?></td>
                        <td><?= format_date($att['submitted_at']) ?></td>
                        <td>
                            <a href="<?= url('student/result.php?attempt_id=' . $att['id']) ?>" class="btn btn-outline btn-sm">
                                View Result &rarr;
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
