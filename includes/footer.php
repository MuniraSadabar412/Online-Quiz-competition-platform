<?php
/**
 * Global Footer Component
 * Online Quiz Competition Platform
 */

$extraJs = $extraJs ?? [];
?>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <div>
                <p style="margin: 0; font-weight: 600; color: var(--text-primary);">
                    QuizArena &bull; Online Quiz Competition Platform
                </p>
                <p style="margin: 4px 0 0; font-size: 0.8rem; color: var(--text-muted);">
                    Built with HTML5, CSS3, JavaScript, Core PHP &amp; MySQL. Academic Project for Viva &amp; Demonstration.
                </p>
            </div>
            <ul class="footer-links">
                <li><a href="<?= url('index.php') ?>">Home</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="<?= url('admin/dashboard.php') ?>">Admin</a></li>
                <?php elseif (is_student()): ?>
                    <li><a href="<?= url('student/dashboard.php') ?>">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?= url('login.php') ?>">Login</a></li>
                    <li><a href="<?= url('register.php') ?>">Register</a></li>
                <?php endif; ?>
                <li><a href="<?= url('setup/create-admin.php') ?>">Admin Setup</a></li>
            </ul>
        </div>
    </footer>

    <!-- Main JS -->
    <script src="<?= url('assets/js/main.js') ?>"></script>

    <!-- Page Specific Scripts -->
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= url('assets/js/' . $js) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
