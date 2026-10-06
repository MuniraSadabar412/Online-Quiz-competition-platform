<?php
/**
 * Competitive Leaderboard Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pageTitle = 'Leaderboard';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];

$selectedQuizId = (int)($_GET['quiz_id'] ?? 0);
$quizzes = [];
$leaderboardRows = [];
$topThree = [];
$remainingRows = [];

try {
    $pdo = get_db_connection();

    // Fetch list of published quizzes for filter dropdown
    $qStmt = $pdo->query("SELECT id, title FROM quizzes WHERE status = 'published' ORDER BY id ASC");
    $quizzes = $qStmt->fetchAll();

    // If no quiz selected, default to the first published quiz if exists
    if ($selectedQuizId <= 0 && !empty($quizzes)) {
        $selectedQuizId = (int)$quizzes[0]['id'];
    }

    if ($selectedQuizId > 0) {
        // Query rankings for this specific quiz
        $rankStmt = $pdo->prepare("
            SELECT 
                a.id as attempt_id,
                a.user_id,
                a.score,
                a.total_marks,
                a.percentage,
                a.time_taken_seconds,
                a.submitted_at,
                u.full_name as student_name,
                u.email as student_email,
                q.title as quiz_title
            FROM attempts a
            JOIN users u ON a.user_id = u.id
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.quiz_id = ? AND a.status IN ('completed', 'expired')
            ORDER BY a.score DESC, a.time_taken_seconds ASC, a.submitted_at ASC
        ");
        $rankStmt->execute([$selectedQuizId]);
        $leaderboardRows = $rankStmt->fetchAll();
    } else {
        // Overall global rankings across all quizzes
        $rankStmt = $pdo->query("
            SELECT 
                a.id as attempt_id,
                a.user_id,
                a.score,
                a.total_marks,
                a.percentage,
                a.time_taken_seconds,
                a.submitted_at,
                u.full_name as student_name,
                u.email as student_email,
                q.title as quiz_title
            FROM attempts a
            JOIN users u ON a.user_id = u.id
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.status IN ('completed', 'expired')
            ORDER BY a.percentage DESC, a.time_taken_seconds ASC, a.submitted_at ASC
            LIMIT 50
        ");
        $leaderboardRows = $rankStmt->fetchAll();
    }

    // Split top 3 and remaining
    $topThree = array_slice($leaderboardRows, 0, 3);
    $remainingRows = array_slice($leaderboardRows, 3);

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading leaderboard: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
    <h1>Competition Leaderboard</h1>
    <p>Honoring top academic performers based on maximum score and fastest verified completion speed.</p>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="<?= url('student/leaderboard.php') ?>" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <label class="filter-label" for="quiz_id">Select Competition / Quiz:</label>
        <select name="quiz_id" id="quiz_id" class="filter-select" onchange="this.form.submit()">
            <option value="0" <?= $selectedQuizId === 0 ? 'selected' : '' ?>>Overall Global Standings</option>
            <?php foreach ($quizzes as $qz): ?>
                <option value="<?= $qz['id'] ?>" <?= $selectedQuizId === (int)$qz['id'] ? 'selected' : '' ?>>
                    <?= e($qz['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($leaderboardRows)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
        <div style="font-size: 2.8rem; margin-bottom: 12px; color: var(--text-muted);">&#x1F3C6;</div>
        <h3>No Competitor Records Found</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px;">No students have completed this quiz yet. Be the first to claim the #1 spot!</p>
        <?php if ($selectedQuizId > 0 && is_student()): ?>
            <a href="<?= url('student/instructions.php?quiz_id=' . $selectedQuizId) ?>" class="btn btn-primary">Take This Quiz Now &rarr;</a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Podium Presentation for Top 3 -->
    <?php if (count($topThree) > 0): ?>
    <div class="podium-container">
        <!-- 2nd Place (Silver) -->
        <?php if (isset($topThree[1])): ?>
            <?php $silver = $topThree[1]; ?>
            <div class="podium-card silver">
                <div class="podium-rank">2</div>
                <div class="podium-name"><?= e($silver['student_name']) ?></div>
                <div class="podium-score"><?= number_format($silver['score'], 0) ?> pts</div>
                <div class="podium-meta">
                    <?= number_format($silver['percentage'], 1) ?>% &bull; <?= format_duration((int)$silver['time_taken_seconds']) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="podium-card silver" style="opacity: 0.3;">
                <div class="podium-rank">2</div>
                <div class="podium-name">Empty</div>
            </div>
        <?php endif; ?>

        <!-- 1st Place (Gold) -->
        <?php if (isset($topThree[0])): ?>
            <?php $gold = $topThree[0]; ?>
            <div class="podium-card gold">
                <div style="font-size: 1.8rem; margin-bottom: 4px;">👑</div>
                <div class="podium-rank">1</div>
                <div class="podium-name"><?= e($gold['student_name']) ?></div>
                <div class="podium-score"><?= number_format($gold['score'], 0) ?> pts</div>
                <div class="podium-meta">
                    <?= number_format($gold['percentage'], 1) ?>% &bull; <?= format_duration((int)$gold['time_taken_seconds']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3rd Place (Bronze) -->
        <?php if (isset($topThree[2])): ?>
            <?php $bronze = $topThree[2]; ?>
            <div class="podium-card bronze">
                <div class="podium-rank">3</div>
                <div class="podium-name"><?= e($bronze['student_name']) ?></div>
                <div class="podium-score"><?= number_format($bronze['score'], 0) ?> pts</div>
                <div class="podium-meta">
                    <?= number_format($bronze['percentage'], 1) ?>% &bull; <?= format_duration((int)$bronze['time_taken_seconds']) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="podium-card bronze" style="opacity: 0.3;">
                <div class="podium-rank">3</div>
                <div class="podium-name">Empty</div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Full Leaderboard Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Full Participant Standings</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?= count($leaderboardRows) ?> Competitor<?= count($leaderboardRows) > 1 ? 's' : '' ?></span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <?php if ($selectedQuizId === 0): ?>
                            <th>Quiz Attempted</th>
                        <?php endif; ?>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Time Taken</th>
                        <th>Completion Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $medalIcons = [
                        1 => '🥇 1st',
                        2 => '🥈 2nd',
                        3 => '🥉 3rd'
                    ];

                    foreach ($leaderboardRows as $idx => $row): 
                        $rank = $idx + 1;
                        $isCurrentUser = ($row['user_id'] == $userId);
                    ?>
                    <tr class="<?= $isCurrentUser ? 'user-highlight-row' : '' ?>">
                        <td style="font-weight: 700; color: <?= $rank <= 3 ? '#f59e0b' : 'var(--text-muted)' ?>;">
                            <?= $medalIcons[$rank] ?? '#' . $rank ?>
                        </td>
                        <td style="font-weight: 600;">
                            <?= e($row['student_name']) ?>
                            <?php if ($isCurrentUser): ?>
                                <span class="badge badge-status badge-info" style="margin-left: 6px;">You</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($selectedQuizId === 0): ?>
                            <td><?= e($row['quiz_title']) ?></td>
                        <?php endif; ?>
                        <td style="font-weight: 700; color: var(--primary-light);">
                            <?= number_format($row['score'], 0) ?> / <?= number_format($row['total_marks'], 0) ?>
                        </td>
                        <td>
                            <span class="badge badge-status <?= $row['percentage'] >= 50 ? 'badge-success' : 'badge-danger' ?>">
                                <?= number_format($row['percentage'], 1) ?>%
                            </span>
                        </td>
                        <td><?= format_duration((int)$row['time_taken_seconds']) ?></td>
                        <td><?= format_date($row['submitted_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
