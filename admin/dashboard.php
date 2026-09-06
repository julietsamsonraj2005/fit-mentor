<?php
$page_title = 'Admin dashboard';
require_once __DIR__ . '/../config/db.php';
require_admin();

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-section">
  <div class="section-header">
    <h2>Admin Dashboard</h2>
  </div>
  <div class="plans-grid">
    <div class="plan-card">
      <div class="plan-icon">🧭</div>
      <h3>Manage Plans</h3>
      <h4>Workout & Diet</h4>
      <p>Create and edit workout/diet plans by level.</p>
      <a class="btn btn-primary" href="<?= $BASE_PATH ?>/admin/plans.php">Open</a>
    </div>

    <div class="plan-card">
      <div class="plan-icon">👥</div>
      <h3>Users</h3>
      <h4>Registered Members</h4>
      <p>View registered users and their ranks.</p>
      <a class="btn btn-primary" href="<?= $BASE_PATH ?>/admin/users.php">Open</a>
    </div>

    <div class="plan-card">
      <div class="plan-icon">📋</div>
      <h3>Assign Plans</h3>
      <h4>Personalized</h4>
      <p>Assign workout and diet plans to users.</p>
      <a class="btn btn-primary" href="<?= $BASE_PATH ?>/admin/assign_plan.php">Open</a>
    </div>
  </div>
</div><br><br><br><br><br><br><br><br><br><br>
<?php include __DIR__ . '/../includes/footer.php'; ?>
