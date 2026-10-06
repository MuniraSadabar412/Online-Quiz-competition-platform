<?php
/**
 * Access Denied Page (403 Forbidden)
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Access Denied';
$reason = $_GET['reason'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<div style="max-width: 580px; margin: 60px auto; text-align: center;">
    <div class="card" style="padding: 48px 32px;">
        <div style="font-size: 3.5rem; margin-bottom: 16px; color: var(--danger);">&#x1F6AB;</div>
        <h1 style="font-size: 1.85rem; margin-bottom: 12px;">Access Denied</h1>
        
        <?php if ($reason === 'admin_only'): ?>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                You do not have administrative privileges to access this page. This administrative route is strictly restricted to system administrators.
            </p>
        <?php elseif ($reason === 'student_only'): ?>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                This page is specifically reserved for enrolled students taking academic assessments.
            </p>
        <?php else: ?>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                You do not have permission to view the requested resource.
            </p>
        <?php endif; ?>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <?php if (is_admin()): ?>
                <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-primary">Go to Admin Dashboard</a>
            <?php elseif (is_student()): ?>
                <a href="<?= url('student/dashboard.php') ?>" class="btn btn-primary">Go to Student Dashboard</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="btn btn-primary">Go to Login</a>
            <?php endif; ?>
            <a href="<?= url('index.php') ?>" class="btn btn-secondary">Home</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
