<?php require_once __DIR__ . '/../config/config.php'; ?>
<!-- Global stylesheet to ensure consistent UI on all pages -->
<link rel="stylesheet" href="<?= rtrim($BASE_PATH, '/') ?>/assets/main.css">
<header class="header">
    <nav class="navbar">
        <div class="nav-brand">
            <h1><a href="<?= $BASE_PATH ?>/" style="text-decoration: none; color: inherit;">Fit-Mentor</a></h1>
        </div>
        <ul class="nav-menu">
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
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
</header>