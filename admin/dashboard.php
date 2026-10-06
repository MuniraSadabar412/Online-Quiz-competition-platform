<?php
/**
 * Administrator Dashboard
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Admin Dashboard';
$extraCss = ['dashboard.css'];

$user = current_user();

$totalStudents = 0;
$totalQuizzes = 0;
$publishedQuizzes = 0;
$totalAttempts = 0;
$platformAvgScore = 0.0;
$recentAttempts = [];
$quizzesSummary = [];

try {
    $pdo = get_db_connection();

    // Summary statistics
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $totalQuizzes = (int)$pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
    $publishedQuizzes = (int)$pdo->query("SELECT COUNT(*) FROM quizzes WHERE status = 'published'")->fetchColumn();
    $totalAttempts = (int)$pdo->query("SELECT COUNT(*) FROM attempts WHERE status IN ('completed', 'expired')")->fetchColumn();

    $avgStmt = $pdo->query("SELECT COALESCE(AVG(percentage), 0) FROM attempts WHERE status IN ('completed', 'expired')");
    $platformAvgScore = round((float)$avgStmt->fetchColumn(), 1);

    // Recent attempts across all students
    $recentStmt = $pdo->query("
        SELECT 
            a.id, a.score, a.total_marks, a.percentage, a.time_taken_seconds, a.submitted_at,
            u.full_name as student_name,
            q.title as quiz_title
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.status IN ('completed', 'expired')
        ORDER BY a.submitted_at DESC
        LIMIT 6
    ");
    $recentAttempts = $recentStmt->fetchAll();

    // Top active quizzes
    $quizStmt = $pdo->query("
        SELECT 
            q.id, q.title, q.category, q.difficulty, q.status,
            COUNT(DISTINCT qu.id) as question_count,
            COUNT(DISTINCT a.id) as attempt_count
        FROM quizzes q
        LEFT JOIN questions qu ON q.id = qu.quiz_id
        LEFT JOIN attempts a ON (q.id = a.quiz_id AND a.status IN ('completed', 'expired'))
        GROUP BY q.id
        ORDER BY attempt_count DESC
        LIMIT 5
    ");
    $quizzesSummary = $quizStmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading admin dashboard: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 2rem; margin-bottom: 4px;">
            Administrative Control Panel
        </h1>
        <p style="color: var(--text-secondary); margin-bottom: 0;">
            Manage academic competitions, MCQs, registered candidates, and exam performance metrics.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= url('admin/quiz-form.php') ?>" class="btn btn-primary">
            + Create New Quiz
        </a>
        <a href="<?= url('admin/questions.php') ?>" class="btn btn-secondary">
            Manage Questions
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalStudents ?></div>
            <div class="stat-label">Enrolled Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $publishedQuizzes ?> <span style="font-size: 1rem; color: var(--text-muted);">/ <?= $totalQuizzes ?></span></div>
            <div class="stat-label">Published Quizzes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalAttempts ?></div>
            <div class="stat-label">Completed Attempts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $platformAvgScore ?>%</div>
            <div class="stat-label">Platform Average Score</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
    <!-- Active Quizzes Summary -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">Quizzes Overview</h3>
            <a href="<?= url('admin/quizzes.php') ?>" class="btn btn-outline btn-sm">All Quizzes &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Questions</th>
                        <th>Attempts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizzesSummary as $qz): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);"><?= e($qz['title']) ?></td>
                        <td><?= e($qz['category']) ?></td>
                        <td><?= get_status_badge($qz['status']) ?></td>
                        <td><?= (int)$qz['question_count'] ?></td>
                        <td><strong><?= (int)$qz['attempt_count'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Submissions -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">Recent Quiz Submissions</h3>
            <a href="<?= url('admin/results.php') ?>" class="btn btn-outline btn-sm">All Results &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAttempts as $att): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= e($att['student_name']) ?></td>
                        <td><?= e($att['quiz_title']) ?></td>
                        <td style="font-weight: 700; color: var(--primary-light);">
                            <?= number_format($att['score'], 0) ?>/<?= number_format($att['total_marks'], 0) ?> (<?= number_format($att['percentage'], 0) ?>%)
                        </td>
                        <td><?= format_duration((int)$att['time_taken_seconds']) ?></td>
                        <td>
                            <a href="<?= url('student/result.php?attempt_id=' . $att['id']) ?>" class="btn btn-outline btn-sm">
                                Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
