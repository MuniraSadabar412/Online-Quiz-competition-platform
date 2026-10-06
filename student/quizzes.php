<?php
/**
 * Available Quizzes with Category & Difficulty Filtering
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$pageTitle = 'Available Quizzes';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];

// Filter parameters
$selectedCategory = trim($_GET['category'] ?? '');
$selectedDifficulty = trim($_GET['difficulty'] ?? '');
$searchTerm = trim($_GET['search'] ?? '');

$categories = [];
$quizzes = [];

try {
    $pdo = get_db_connection();

    // Get all distinct categories
    $catStmt = $pdo->query("SELECT DISTINCT category FROM quizzes WHERE status = 'published' ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    // Build filtered query
    $sql = "
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
        LEFT JOIN attempts a ON (q.id = a.quiz_id AND a.user_id = :user_id)
        WHERE q.status = 'published'
    ";

    $params = [':user_id' => $userId];

    if (!empty($selectedCategory)) {
        $sql .= " AND q.category = :category";
        $params[':category'] = $selectedCategory;
    }

    if (!empty($selectedDifficulty)) {
        $sql .= " AND q.difficulty = :difficulty";
        $params[':difficulty'] = $selectedDifficulty;
    }

    if (!empty($searchTerm)) {
        $sql .= " AND (q.title LIKE :search OR q.description LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }

    $sql .= " GROUP BY q.id, a.id ORDER BY q.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll();

} catch (Exception $e) {
    set_flash_message('danger', 'Error loading quizzes: ' . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
    <h1>Available Competitions &amp; Quizzes</h1>
    <p>Select a challenge to test your academic subject mastery under timed exam conditions.</p>
</div>

<!-- Filters Bar -->
<form method="GET" action="<?= url('student/quizzes.php') ?>" class="filter-bar">
    <div class="filter-group">
        <label class="filter-label" for="category">Category:</label>
        <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="filter-label" for="difficulty" style="margin-left: 12px;">Difficulty:</label>
        <select name="difficulty" id="difficulty" class="filter-select" onchange="this.form.submit()">
            <option value="">All Difficulties</option>
            <option value="easy" <?= $selectedDifficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
            <option value="medium" <?= $selectedDifficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="hard" <?= $selectedDifficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
        </select>
    </div>

    <div class="filter-group">
        <input 
            type="text" 
            name="search" 
            class="form-control" 
            style="width: 220px; padding: 7px 12px; font-size: 0.88rem;" 
            placeholder="Search quizzes..." 
            value="<?= e($searchTerm) ?>"
        >
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if (!empty($selectedCategory) || !empty($selectedDifficulty) || !empty($searchTerm)): ?>
            <a href="<?= url('student/quizzes.php') ?>" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Quizzes Grid -->
<div class="quiz-grid">
    <?php if (empty($quizzes)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 48px;">
            <div style="font-size: 2.5rem; margin-bottom: 12px; color: var(--text-muted);">&#x1F50D;</div>
            <h3>No Quizzes Matched Your Filter</h3>
            <p style="color: var(--text-secondary); margin-bottom: 16px;">Try adjusting your selected category, difficulty, or search keyword.</p>
            <a href="<?= url('student/quizzes.php') ?>" class="btn btn-secondary">Clear Filters</a>
        </div>
    <?php else: ?>
        <?php foreach ($quizzes as $q): ?>
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
                            View Instructions &amp; Start &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
