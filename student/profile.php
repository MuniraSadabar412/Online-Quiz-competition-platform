<?php
/**
 * Student Profile Page
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_student();

$pageTitle = 'My Profile';
$extraCss = ['dashboard.css'];

$user = current_user();
$userId = $user['id'];
$errors = [];

try {
    $pdo = get_db_connection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf_post();
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $newName = trim($_POST['full_name'] ?? '');
            if (empty($newName) || mb_strlen($newName) < 2) {
                $errors[] = "Full name must be at least 2 characters.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$newName, $userId]);
                $_SESSION['full_name'] = $newName;
                set_flash_message('success', 'Profile details updated successfully.');
                header("Location: " . url('student/profile.php'));
                exit;
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            // Fetch current hash
            $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $hash = $userStmt->fetchColumn();

            if (!password_verify($currentPass, $hash)) {
                $errors[] = "The current password you entered is incorrect.";
            } elseif (strlen($newPass) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            } elseif ($newPass !== $confirmPass) {
                $errors[] = "New passwords do not match.";
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updatePass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updatePass->execute([$newHash, $userId]);
                set_flash_message('success', 'Your password has been changed successfully.');
                header("Location: " . url('student/profile.php'));
                exit;
            }
        }
    }

    // Fetch user info and statistics
    $infoStmt = $pdo->prepare("
        SELECT 
            u.*,
            COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_count,
            COALESCE(AVG(CASE WHEN a.status = 'completed' THEN a.percentage END), 0) as avg_score
        FROM users u
        LEFT JOIN attempts a ON u.id = a.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $infoStmt->execute([$userId]);
    $studentData = $infoStmt->fetch();

} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1>Student Profile &amp; Settings</h1>
        <p>Manage your account profile and credentials.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
        <!-- Profile Card -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 20px;">Basic Information</h3>

            <form method="POST" action="<?= url('student/profile.php') ?>">
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        value="<?= e($studentData['full_name'] ?? '') ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        class="form-control" 
                        value="<?= e($studentData['email'] ?? '') ?>" 
                        disabled 
                        style="opacity: 0.6; cursor: not-allowed;"
                    >
                    <span class="form-text">Email address cannot be modified once registered.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Member Since</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="<?= format_date($studentData['created_at'] ?? '') ?>" 
                        disabled 
                        style="opacity: 0.6; cursor: not-allowed;"
                    >
                </div>

                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 20px;">Change Password</h3>

            <form method="POST" action="<?= url('student/profile.php') ?>">
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-control" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control" 
                        placeholder="At least 6 characters" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Repeat new password" 
                        required
                    >
                </div>

                <button type="submit" class="btn btn-secondary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
