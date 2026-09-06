<?php
$page_title = 'Your plans';
require_once __DIR__ . '/../config/db.php';
require_login();

$uid = $_SESSION['user_id'];

// Fetch user rank to show matching plans
$ur = $pdo->prepare("SELECT rank FROM users WHERE id=?");
$ur->execute([$uid]);
$rank = $ur->fetchColumn();

// Check if admin has assigned specific plans to this user
$pdo->exec("CREATE TABLE IF NOT EXISTS user_assigned_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    workout_plan_id INT DEFAULT NULL,
    diet_plan_id INT DEFAULT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (diet_plan_id) REFERENCES diet_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$ap = $pdo->prepare('SELECT workout_plan_id, diet_plan_id FROM user_assigned_plans WHERE user_id = ?');
$ap->execute([$uid]);
$assigned = $ap->fetch(PDO::FETCH_ASSOC) ?: null;

$assignedWorkout = null;
$assignedDiet = null;

if ($assigned && !empty($assigned['workout_plan_id'])) {
    $stmt = $pdo->prepare('SELECT title, description, exercises FROM workout_plans WHERE id = ?');
    $stmt->execute([$assigned['workout_plan_id']]);
    $assignedWorkout = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($assigned && !empty($assigned['diet_plan_id'])) {
    $stmt = $pdo->prepare('SELECT title, description, meals FROM diet_plans WHERE id = ?');
    $stmt->execute([$assigned['diet_plan_id']]);
    $assignedDiet = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Fallback: Plans by rank (used if no specific assignments)
$workouts = $pdo->prepare("SELECT title, description, exercises FROM workout_plans WHERE level=? ORDER BY created_at DESC");
$workouts->execute([$rank]);
$diets = $pdo->prepare("SELECT title, description, meals FROM diet_plans WHERE level=? ORDER BY created_at DESC");
$diets->execute([$rank]);

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <?php if ($assignedWorkout || $assignedDiet): ?>
    <div class="dashboard-section" style="width:100%">
      <div class="section-header">
        <h2>Your Assigned Plans</h2>
      </div>
      <div class="plans-grid">
        <?php if ($assignedWorkout): ?>
          <div class="plan-card">
            <div class="plan-icon">💪</div>
            <h3>Workout Plan</h3>
            <h4><?= htmlspecialchars($assignedWorkout['title']) ?></h4>
            <p><?= nl2br(htmlspecialchars($assignedWorkout['description'])) ?></p>
            <?php $ex = json_decode($assignedWorkout['exercises'], true) ?: []; ?>
            <?php if ($ex): ?>
              <ul>
                <?php foreach ($ex as $item): ?>
                  <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($assignedDiet): ?>
          <div class="plan-card">
            <div class="plan-icon">🥗</div>
            <h3>Diet Plan</h3>
            <h4><?= htmlspecialchars($assignedDiet['title']) ?></h4>
            <p><?= nl2br(htmlspecialchars($assignedDiet['description'])) ?></p>
            <?php $meals = json_decode($assignedDiet['meals'], true) ?: []; ?>
            <?php if ($meals): ?>
              <ul>
                <?php foreach ($meals as $item): ?>
                  <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="dashboard-section" style="width:100%">
      <div class="section-header">
        <h2>Recommended Workout Plans (<?= htmlspecialchars($rank) ?>)</h2>
      </div>
      <div class="plans-grid">
        <?php foreach ($workouts as $w): ?>
          <div class="plan-card">
            <div class="plan-icon">💪</div>
            <h3>Workout</h3>
            <h4><?= htmlspecialchars($w['title']) ?></h4>
            <p><?= nl2br(htmlspecialchars($w['description'])) ?></p>
            <?php $ex = json_decode($w['exercises'], true) ?: []; ?>
            <?php if ($ex): ?>
              <ul>
                <?php foreach ($ex as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dashboard-section" style="width:100%">
      <div class="section-header">
        <h2>Recommended Diet Plans (<?= htmlspecialchars($rank) ?>)</h2>
      </div>
      <div class="plans-grid">
        <?php foreach ($diets as $d): ?>
          <div class="plan-card">
            <div class="plan-icon">🥗</div>
            <h3>Diet</h3>
            <h4><?= htmlspecialchars($d['title']) ?></h4>
            <p><?= nl2br(htmlspecialchars($d['description'])) ?></p>
            <?php $meals = json_decode($d['meals'], true) ?: []; ?>
            <?php if ($meals): ?>
              <ul>
                <?php foreach ($meals as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div><br><br><br><br><br><br><br><br><br><br>
<?php include __DIR__ . '/../includes/footer.php'; ?>
