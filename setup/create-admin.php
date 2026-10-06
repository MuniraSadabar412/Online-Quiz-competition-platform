<?php
/**
 * Admin Creation Script
 * Online Quiz Competition Platform
 * 
 * Can be run via CLI (`php setup/create-admin.php`) or accessed via web browser during initial setup.
 */

require_once __DIR__ . '/../config/database.php';

$message = '';
$isCLI = (php_sapi_name() === 'cli');

$defaultEmail = 'admin@quizplatform.com';
$defaultPass = 'Admin@12345';
$defaultName = 'System Administrator';

if ($isCLI) {
    echo "=== CREATE ADMIN ACCOUNT ===\n";
    $email = $defaultEmail;
    $password = $defaultPass;
    $name = $defaultName;
} else {
    // If submitted via browser form
    $email = isset($_POST['email']) ? trim($_POST['email']) : $defaultEmail;
    $password = isset($_POST['password']) ? trim($_POST['password']) : $defaultPass;
    $name = isset($_POST['name']) ? trim($_POST['name']) : $defaultName;
}

if ($isCLI || ($_SERVER['REQUEST_METHOD'] === 'POST')) {
    try {
        $pdo = get_db_connection();

        // Check if admin already exists
        $checkStmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existing) {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, password_hash = ?, role = 'admin' WHERE id = ?");
            $updateStmt->execute([$name, $hash, $existing['id']]);
            $msg = "Admin account '$email' already existed and has been updated successfully with new password.";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $insertStmt->execute([$name, $email, $hash]);
            $msg = "Admin account '$email' created successfully.";
        }

        if ($isCLI) {
            echo "[✓] $msg\n";
            echo "Email: $email\nPassword: $password\n";
            exit(0);
        } else {
            $message = $msg;
        }
    } catch (Exception $e) {
        $err = "Error: " . $e->getMessage();
        if ($isCLI) {
            echo "[✗] $err\n";
            exit(1);
        } else {
            $message = $err;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Online Quiz Platform</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px; width: 100%; max-width: 440px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4); }
        h1 { margin-top: 0; font-size: 22px; color: #f8fafc; font-weight: 700; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 20px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #cbd5e1; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 10px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: #fff; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #4f46e5; border: none; border-radius: 6px; color: #fff; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 8px; }
        button:hover { background: #4338ca; }
        .links { margin-top: 20px; text-align: center; font-size: 13px; }
        .links a { color: #818cf8; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Create Admin Account</h1>
        <p>Use this utility to provision the default master administrator for the quiz platform.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($defaultName) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($defaultEmail) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="<?= htmlspecialchars($defaultPass) ?>" required>
            </div>
            <button type="submit">Create / Reset Admin</button>
        </form>

        <div class="links">
            <a href="../login.php">Go to Login Page &rarr;</a>
        </div>
    </div>
</body>
</html>
