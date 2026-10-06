<?php
/**
 * Student Dashboard
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$pageTitle = 'Student Dashboard';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];

$completedCount = 0;
$avgPercentage = 0.0;
$bestScore = 0.0;
$availableQuizzes = [];
$recentAttempts = [];

try {
    $pdo = get_db_connection();

    // Summary stats
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
            COALESCE(AVG(CASE WHEN status = 'completed' THEN percentage END), 0) as avg_percentage,
            COALESCE(MAX(CASE WHEN status = 'completed' THEN percentage END), 0) as best_score
        FROM attempts 
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch();

    $completedCount = (int)$stats['completed_count'];
    $avgPercentage = round((float)$stats['avg_percentage'], 1);
    $bestScore = round((float)$stats['best_score'], 1);

    // Fetch published quizzes along with attempt status for this student
    $quizzesStmt = $pdo->prepare("
        SELECT 
            q.id, q.title, q.description, q.category, q.difficulty, q.duration_minutes,
            COUNT(DISTINCT qu.id) as question_count,
            COALESCE(SUM(qu.marks), 0) as total_marks,
            a.id as attempt_id,
            a.status as attempt_status,
            a.score as attempt_score,
            a.percentage as attempt_percentage
        FROM quizzes q
        LEFT JOIN questions qu ON q.id = qu.quiz_id
        LEFT JOIN attempts a ON (q.id = a.quiz_id AND a.user_id = ?)
        WHERE q.status = 'published'
        GROUP BY q.id, a.id
        ORDER BY q.id ASC
    ");
    $quizzesStmt->execute([$userId]);
    $availableQuizzes = $quizzesStmt->fetchAll();

    // Fetch recent attempts
    $recentStmt = $pdo->prepare("
        SELECT 
            a.id, a.score, a.total_marks, a.percentage, a.time_taken_seconds, a.submitted_at, a.status,
            q.title as quiz_title, q.category
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.user_id = ? AND a.status IN ('completed', 'expired')
        ORDER BY a.submitted_at DESC
        LIMIT 5
    ");
    $recentStmt->execute([$userId]);
    $recentAttempts = $recentStmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading dashboard data: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div style="margin-bottom: 28px;">
    <h1 style="font-size: 2rem; margin-bottom: 4px;">
        Welcome back, <?= e(format_display_name($user['full_name'])) ?>! 👋
    </h1>
    <p style="color: var(--text-secondary); margin-bottom: 0;">
        Track your progress, explore new competitive assessments, and review past achievements.
    </p>
</div>

<!-- Performance Metric Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $completedCount ?></div>
            <div class="stat-label">Quizzes Completed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $avgPercentage ?>%</div>
            <div class="stat-label">Average Accuracy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $bestScore ?>%</div>
            <div class="stat-label">Personal Best Score</div>
        </div>
    </div>
</div>

<!-- Published Quizzes Section -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <div>
        <h2 style="font-size: 1.5rem; margin-bottom: 4px;">Available Quizzes</h2>
        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">Timed academic quizzes ready for attempt.</p>
    </div>
    <a href="<?= url('student/quizzes.php') ?>" class="btn btn-outline btn-sm">Filter &amp; Search All &rarr;</a>
</div>

<div class="quiz-grid">
    <?php if (empty($availableQuizzes)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <p style="color: var(--text-secondary); margin: 0;">No active quizzes are published at this moment. Please check back soon!</p>
        </div>
    <?php else: ?>
        <?php foreach ($availableQuizzes as $q): ?>
            <div class="quiz-card">
                <div>
                    <div class="quiz-card-header">
                        <span class="quiz-category"><?= e($q['category']) ?></span>
                        <?= get_difficulty_badge($q['difficulty']) ?>
                    </div>
                    <h3 class="quiz-card-title"><?= e($q['title']) ?></h3>
                    <p class="quiz-card-desc"><?= e($q['description']) ?></p>
                </div>

                <div>
                    <div class="quiz-meta">
                        <div class="quiz-meta-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                            <span><?= (int)$q['duration_minutes'] ?> mins</span>
                        </div>
                        <div class="quiz-meta-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                            <span><?= (int)$q['question_count'] ?> Questions</span>
                        </div>
                        <div class="quiz-meta-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <span><?= (int)$q['total_marks'] ?> Marks</span>
                        </div>
                    </div>

                    <?php if ($q['attempt_status'] === 'completed' || $q['attempt_status'] === 'expired'): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="badge badge-status badge-success">
                                Score: <?= number_format($q['attempt_score'], 0) ?>/<?= (int)$q['total_marks'] ?> (<?= number_format($q['attempt_percentage'], 0) ?>%)
                            </span>
                            <a href="<?= url('student/result.php?attempt_id=' . $q['attempt_id']) ?>" class="btn btn-outline btn-sm">
                                View Result &rarr;
                            </a>
                        </div>
                    <?php elseif ($q['attempt_status'] === 'in_progress'): ?>
                        <a href="<?= url('student/take-quiz.php?attempt_id=' . $q['attempt_id']) ?>" class="btn btn-warning btn-block">
                            Resume Quiz &rarr;
                        </a>
                    <?php else: ?>
                        <a href="<?= url('student/instructions.php?quiz_id=' . $q['id']) ?>" class="btn btn-primary btn-block">
                            Take Quiz &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Recent Attempts -->
<?php if (!empty($recentAttempts)): ?>
<div class="card" style="margin-top: 32px;">
    <div class="card-header">
        <h3 class="card-title">Recent Quiz Attempts</h3>
        <a href="<?= url('student/history.php') ?>" class="btn btn-outline btn-sm">View Complete History &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Quiz Title</th>
                    <th>Category</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Time Taken</th>
                    <th>Submitted On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAttempts as $att): ?>
                <tr>
                    <td style="font-weight: 600;"><?= e($att['quiz_title']) ?></td>
                    <td><?= e($att['category']) ?></td>
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
                            Result
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
