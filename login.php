<?php
/**
 * User Login Page (Students & Admins)
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: " . (is_admin() ? url('admin/dashboard.php') : url('student/dashboard.php')));
    exit;
}

$pageTitle = 'Login';
$extraCss = ['auth.css'];

$errors = [];
$email = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_post();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (empty($email) || empty($password)) {
        $errors[] = "Please provide both email address and password.";
    } else {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Successful authentication
                login_user($user);

                set_flash_message('success', "Welcome back, " . $user['full_name'] . "!");

                // Handle redirect URL safely
                if (!empty($redirect) && strpos($redirect, '/') === 0 && strpos($redirect, '//') === false) {
                    header("Location: " . $redirect);
                    exit;
                }

                // Default role redirect
                if ($user['role'] === 'admin') {
                    header("Location: " . url('admin/dashboard.php'));
                } else {
                    header("Location: " . url('student/dashboard.php'));
                }
                exit;
            } else {
                $errors[] = "Invalid email address or password. Please try again.";
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
            <h1>Sign In</h1>
            <p>Access your student dashboard or administrative control panel.</p>
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

        <form method="POST" action="<?= url('login.php') ?>">
            <?php csrf_input(); ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="name@college.edu" 
                    value="<?= e($email) ?>" 
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                Sign In &rarr;
            </button>
        </form>

        <div class="demo-credentials">
            <h4>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                College Viva / Evaluator Demo Credentials:
            </h4>
            <p><strong>Admin:</strong> <code>admin@quizplatform.com</code> | <code>Admin@12345</code></p>
            <p><strong>Student:</strong> <code>aarav@quiz.edu</code> | <code>Student@123</code></p>
        </div>

        <div class="auth-footer">
            Don't have an account? <a href="<?= url('register.php') ?>">Register here</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
