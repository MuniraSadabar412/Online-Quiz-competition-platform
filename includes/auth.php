<?php
/**
 * Authentication & Authorization Handler
 * Online Quiz Competition Platform
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400 * 2, // 2 days
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current user data from session
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
    ];
}

/**
 * Check if the authenticated user is an administrator
 */
function is_admin(): bool {
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * Check if the authenticated user is a student
 */
function is_student(): bool {
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'student');
}

/**
 * Require general authentication
 */
function require_login(): void {
    if (!is_logged_in()) {
        $baseUrl = get_project_root();
        header("Location: " . $baseUrl . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require student role (blocks unauthenticated and admin users)
 */
function require_student(): void {
    require_login();
    if (!is_student()) {
        $baseUrl = get_project_root();
        header("Location: " . $baseUrl . "/access-denied.php?reason=student_only");
        exit;
    }
}

/**
 * Require administrator role (strictly blocks students)
 */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        $baseUrl = get_project_root();
        header("Location: " . $baseUrl . "/access-denied.php?reason=admin_only");
        exit;
    }
}

/**
 * Establish a user session with session regeneration to prevent fixation
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in_at'] = time();
}

/**
 * Securely terminate user session and clear cookies
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Determine root web directory path (e.g. /quiz)
 */
function get_project_root(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Look for /quiz in script path or detect base
    if (strpos($script, '/quiz/') !== false || $script === '/quiz') {
        return '/quiz';
    }
    // Fallback for root hosting or virtual hosts
    return '';
}
