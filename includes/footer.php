<?php require_once __DIR__ . '/../config/config.php'; ?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">

            <div class="footer-section">
                <h3>Fit-Mentor</h3>
                <p>Your companion in fitness journey. Transform your life with personalized plans and continuous support.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= $BASE_PATH ?>/user/dashboard.php">Dashboard</a></li>
                        <li><a href="<?= $BASE_PATH ?>/user/plans.php">My Plan</a></li>
                        <li><a href="<?= $BASE_PATH ?>/user/progress.php">Progress</a></li>
                        <li><a href="<?= $BASE_PATH ?>/user/ranking.php">Ranking</a></li>
                        <li><a href="<?= $BASE_PATH ?>/user/profile.php">Profile</a></li>
                        <li><a href="<?= $BASE_PATH ?>/auth/logout.php" class="btn btn-outline">Logout</a></li>
                    <?php elseif (is_admin_logged_in()): ?>
                        <li><a href="<?= $BASE_PATH ?>/admin/dashboard.php">Dashboard</a></li>
                        <li><a href="<?= $BASE_PATH ?>/admin/assign_plan.php">Assign Plans</a></li>
                        <li><a href="<?= $BASE_PATH ?>/admin/users.php">Users</a></li>
                        <li><a href="<?= $BASE_PATH ?>/admin/plans.php">Manage Plans</a></li>
                        <li><a href="<?= $BASE_PATH ?>/auth/logout.php" class="btn btn-outline">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= $BASE_PATH ?>/">Home</a></li>
                        <li><a href="<?= $BASE_PATH ?>/auth/login.php" class="btn btn-outline">Login</a></li>
                        <li><a href="<?= $BASE_PATH ?>/auth/register.php" class="btn btn-primary">Get Started</a></li>
                    <?php endif; ?>
                </ul>

        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Fit-Mentor. All rights reserved.</p>
        </div>
    </div>
</footer>
<!-- Global script to ensure consistent interaction on all pages -->
<script src="<?= rtrim($BASE_PATH, '/') ?>/assets/main.js"></script>