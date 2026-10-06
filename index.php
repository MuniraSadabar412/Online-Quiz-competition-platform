<?php
/**
 * Landing Page / Public Home
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home - Online Quiz Competition Platform';
$extraCss = ['dashboard.css'];

// Fetch platform statistics
$totalQuizzes = 0;
$totalQuestions = 0;
$totalStudents = 0;
$topPerformers = [];

try {
    $pdo = get_db_connection();
    $totalQuizzes = (int)$pdo->query("SELECT COUNT(*) FROM quizzes WHERE status = 'published'")->fetchColumn();
    $totalQuestions = (int)$pdo->query("SELECT COUNT(*) FROM questions q JOIN quizzes z ON q.quiz_id = z.id WHERE z.status = 'published'")->fetchColumn();
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

    // Fetch top 3 completed attempts for preview
    $topStmt = $pdo->query("
        SELECT a.score, a.percentage, a.time_taken_seconds, u.full_name, q.title as quiz_title
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.status = 'completed'
        ORDER BY a.score DESC, a.time_taken_seconds ASC
        LIMIT 3
    ");
    $topPerformers = $topStmt->fetchAll();
} catch (Exception $e) {
    // Database connection error handled gracefully
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section style="text-align: center; padding: 48px 0 60px; max-width: 820px; margin: 0 auto;">
    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); padding: 6px 16px; border-radius: 9999px; font-size: 0.85rem; color: #818cf8; font-weight: 600; margin-bottom: 24px;">
        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></span>
        College Competitive Examination &amp; Viva Ready
    </div>
    <h1 style="font-size: 3rem; font-weight: 800; letter-spacing: -0.03em; line-height: 1.15; margin-bottom: 20px;">
        Master Core Concepts with <span style="background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Timed Competitive Quizzes</span>
    </h1>
    <p style="font-size: 1.2rem; color: var(--text-secondary); max-width: 680px; margin: 0 auto 36px;">
        Engage in real-time academic assessments, test your coding and analytical acumen under time pressure, and rise through the live leaderboard ranks.
    </p>

    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <?php if (!is_logged_in()): ?>
            <a href="<?= url('register.php') ?>" class="btn btn-primary btn-lg">
                Get Started Free &rarr;
            </a>
            <a href="<?= url('login.php') ?>" class="btn btn-secondary btn-lg">
                Student &amp; Admin Login
            </a>
        <?php elseif (is_admin()): ?>
            <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-primary btn-lg">
                Access Admin Dashboard &rarr;
            </a>
        <?php else: ?>
            <a href="<?= url('student/quizzes.php') ?>" class="btn btn-primary btn-lg">
                Explore Available Quizzes &rarr;
            </a>
            <a href="<?= url('student/dashboard.php') ?>" class="btn btn-secondary btn-lg">
                My Dashboard
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalQuizzes ?></div>
            <div class="stat-label">Published Quizzes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalQuestions ?>+</div>
            <div class="stat-label">Curated MCQ Questions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalStudents ?></div>
            <div class="stat-label">Enrolled Students</div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section style="margin-top: 40px; margin-bottom: 60px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2>Engineered for College Competitions</h2>
        <p>Built with robust server-side security, synchronized countdown limits, and real-time evaluation.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
        <div class="card" style="margin: 0;">
            <div style="font-size: 1.8rem; margin-bottom: 12px; color: var(--primary-light);">&#x23F1;</div>
            <h3 style="font-size: 1.2rem;">Strict Server Deadlines</h3>
            <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 0;">
                Timing is verified directly on the backend PHP server. Refreshing or tampering with client scripts cannot bypass the allocated exam duration.
            </p>
        </div>

        <div class="card" style="margin: 0;">
            <div style="font-size: 1.8rem; margin-bottom: 12px; color: var(--success);">&#x1F4CA;</div>
            <h3 style="font-size: 1.2rem;">Dynamic Leaderboard Podium</h3>
            <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 0;">
                Calculated dynamically via MySQL. Top 3 competitors earn Gold, Silver, and Bronze badges based on maximum score and shortest completion time.
            </p>
        </div>

        <div class="card" style="margin: 0;">
            <div style="font-size: 1.8rem; margin-bottom: 12px; color: #38bdf8;">&#x1F6E1;</div>
            <h3 style="font-size: 1.2rem;">Secure One-Attempt Policy</h3>
            <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 0;">
                Enforced by database unique constraints and PHP session validation. Prevents duplicate attempts, multi-tab exploitation, and race conditions.
            </p>
        </div>
    </div>
</section>

<!-- Leaderboard Preview Section -->
<?php if (!empty($topPerformers)): ?>
<section style="margin-bottom: 60px;">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Top Champions Preview</h3>
                <p style="margin: 4px 0 0; font-size: 0.86rem; color: var(--text-muted);">Current top performing students across all competitive challenges.</p>
            </div>
            <a href="<?= url('login.php') ?>" class="btn btn-outline btn-sm">View Full Leaderboard &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student</th>
                        <th>Quiz Title</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Time Elapsed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $ranks = ['🥇 1st', '🥈 2nd', '🥉 3rd'];
                    foreach ($topPerformers as $idx => $top): 
                    ?>
                    <tr>
                        <td style="font-weight: 700; color: #f59e0b;"><?= $ranks[$idx] ?? '#' . ($idx + 1) ?></td>
                        <td style="font-weight: 600;"><?= e($top['full_name']) ?></td>
                        <td><?= e($top['quiz_title']) ?></td>
                        <td style="font-weight: 700; color: var(--primary-light);"><?= number_format($top['score'], 0) ?> pts</td>
                        <td><span class="badge badge-status badge-success"><?= number_format($top['percentage'], 1) ?>%</span></td>
                        <td><?= format_duration((int)$top['time_taken_seconds']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
