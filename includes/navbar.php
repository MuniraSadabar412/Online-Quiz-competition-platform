<?php
/**
 * Navigation Bar Component
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = current_user();
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<nav class="navbar">
    <div class="container navbar-inner">
        <a href="<?= url('index.php') ?>" class="brand">
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            </div>
            <span>QuizArena</span>
        </a>

        <button class="mobile-nav-toggle" aria-label="Toggle Navigation">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <ul class="nav-links">
            <?php if (!$user): ?>
                <li><a href="<?= url('index.php') ?>" class="<?= strpos($currentScript, 'index.php') !== false ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= url('login.php') ?>">Available Quizzes</a></li>
                <li><a href="<?= url('login.php') ?>">Leaderboard</a></li>
                <li><a href="<?= url('login.php') ?>" class="<?= strpos($currentScript, 'login.php') !== false ? 'active' : '' ?>">Login</a></li>
                <li><a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm <?= strpos($currentScript, 'register.php') !== false ? 'active' : '' ?>">Register</a></li>
            <?php elseif ($user['role'] === 'student'): ?>
                <li><a href="<?= url('student/dashboard.php') ?>" class="<?= strpos($currentScript, 'student/dashboard.php') !== false ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="<?= url('student/quizzes.php') ?>" class="<?= strpos($currentScript, 'student/quizzes.php') !== false ? 'active' : '' ?>">Available Quizzes</a></li>
                <li><a href="<?= url('student/leaderboard.php') ?>" class="<?= strpos($currentScript, 'student/leaderboard.php') !== false ? 'active' : '' ?>">Leaderboard</a></li>
                <li><a href="<?= url('student/history.php') ?>" class="<?= strpos($currentScript, 'student/history.php') !== false ? 'active' : '' ?>">My History</a></li>
                <li><a href="<?= url('student/profile.php') ?>" class="<?= strpos($currentScript, 'student/profile.php') !== false ? 'active' : '' ?>">Profile</a></li>
                <li class="nav-user">
                    <span class="user-badge">
                        <span class="role-tag">Student</span>
                        <span><?= e(format_display_name($user['full_name'])) ?></span>
                    </span>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-sm">Logout</a>
                </li>
            <?php elseif ($user['role'] === 'admin'): ?>
                <li><a href="<?= url('admin/dashboard.php') ?>" class="<?= strpos($currentScript, 'admin/dashboard.php') !== false ? 'active' : '' ?>">Admin Dashboard</a></li>
                <li><a href="<?= url('admin/quizzes.php') ?>" class="<?= strpos($currentScript, 'admin/quizzes.php') !== false ? 'active' : '' ?>">Quizzes</a></li>
                <li><a href="<?= url('admin/questions.php') ?>" class="<?= strpos($currentScript, 'admin/questions.php') !== false ? 'active' : '' ?>">Questions</a></li>
                <li><a href="<?= url('admin/students.php') ?>" class="<?= strpos($currentScript, 'admin/students.php') !== false ? 'active' : '' ?>">Students</a></li>
                <li><a href="<?= url('admin/results.php') ?>" class="<?= strpos($currentScript, 'admin/results.php') !== false ? 'active' : '' ?>">Results</a></li>
                <li><a href="<?= url('admin/profile.php') ?>" class="<?= strpos($currentScript, 'admin/profile.php') !== false ? 'active' : '' ?>">Account</a></li>
                <li class="nav-user">
                    <span class="user-badge">
                        <span class="role-tag admin">Admin</span>
                        <span><?= e(format_display_name($user['full_name'])) ?></span>
                    </span>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-sm">Logout</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
