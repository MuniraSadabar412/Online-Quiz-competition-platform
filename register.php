<?php
/**
 * Student Registration Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    header("Location: " . (is_admin() ? url('admin/dashboard.php') : url('student/dashboard.php')));
    exit;
}

$pageTitle = 'Student Registration';
$extraCss = ['auth.css'];

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($fullName) || mb_strlen($fullName) < 2) {
        $errors[] = "Please provide your full legal or college name (at least 2 characters).";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            $pdo = get_db_connection();

            // Check duplicate email
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = "An account with email address '{$email}' already exists. Please log in.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
                $stmt->execute([$fullName, $email, $hash]);

                $newUserId = (int)$pdo->lastInsertId();

                // Auto-login registered student
                login_user([
                    'id' => $newUserId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => 'student'
                ]);

                set_flash_message('success', "Welcome to QuizArena, {$fullName}! Your student account was successfully registered.");
                header("Location: " . url('student/dashboard.php'));
                exit;
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Student Account</h1>
            <p>Join competitive quizzes, track analytics, and earn leaderboard ranks.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('register.php') ?>" novalidate>
            <?php csrf_input(); ?>

            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    class="form-control" 
                    placeholder="e.g. Aarav Sharma" 
                    value="<?= e($fullName) ?>" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">College or Personal Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="name@college.edu" 
                    value="<?= e($email) ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="At least 6 characters" 
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control" 
                    placeholder="Repeat your password" 
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                Complete Registration &rarr;
            </button>
        </form>

        <div class="auth-footer">
            Already registered? <a href="<?= url('login.php') ?>">Sign in here</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
