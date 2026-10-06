<?php
/**
 * Admin Profile & Security Settings
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pageTitle = 'Admin Profile';
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
                $errors[] = "Administrator name must be at least 2 characters.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$newName, $userId]);
                $_SESSION['full_name'] = $newName;
                set_flash_message('success', 'Admin profile details updated successfully.');
                header("Location: " . url('admin/profile.php'));
                exit;
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $hash = $userStmt->fetchColumn();

            if (!password_verify($currentPass, $hash)) {
                $errors[] = "The current admin password you entered is incorrect.";
            } elseif (strlen($newPass) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            } elseif ($newPass !== $confirmPass) {
                $errors[] = "New passwords do not match.";
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updatePass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updatePass->execute([$newHash, $userId]);
                set_flash_message('success', 'Admin credentials updated successfully.');
                header("Location: " . url('admin/profile.php'));
                exit;
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $adminData = $stmt->fetch();

} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1>Admin Account &amp; Credentials</h1>
        <p>Manage system administrator credentials and security settings.</p>
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
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 20px;">Administrator Profile</h3>

            <form method="POST" action="<?= url('admin/profile.php') ?>">
                <?php csrf_input(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label" for="full_name">Administrator Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        value="<?= e($adminData['full_name'] ?? '') ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        class="form-control" 
                        value="<?= e($adminData['email'] ?? '') ?>" 
                        disabled 
                        style="opacity: 0.6; cursor: not-allowed;"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Security Role</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="System Administrator (Superuser)" 
                        disabled 
                        style="opacity: 0.6; cursor: not-allowed;"
                    >
                </div>

                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-title" style="margin-bottom: 20px;">Update Master Password</h3>

            <form method="POST" action="<?= url('admin/profile.php') ?>">
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

                <button type="submit" class="btn btn-secondary">Update Admin Password</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
