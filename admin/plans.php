<?php
$page_title = 'Manage plans';
require_once __DIR__ . '/../config/db.php';
require_admin();

$msg = '';
// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $planType = $_POST['type'] ?? '';
    $planId = (int)($_POST['id'] ?? 0);
    if ($planId > 0 && in_array($planType, ['workout', 'diet'], true)) {
        if ($planType === 'workout') {
            $stmt = $pdo->prepare("DELETE FROM workout_plans WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM diet_plans WHERE id = ?");
        }
        $stmt->execute([$planId]);
        $msg = ucfirst($planType) . ' plan deleted.';
    } else {
        $msg = 'Invalid delete request.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'workout';
    $title = trim($_POST['title'] ?? '');
    $level = $_POST['level'] ?? 'Beginner';
    $desc  = trim($_POST['description'] ?? '');
    $items = array_filter(array_map('trim', explode("\n", $_POST['items'] ?? '')));
    if ($title && $items) {
        if ($type === 'workout') {
            $pdo->prepare("INSERT INTO workout_plans (title, level, description, exercises, created_by) VALUES (?,?,?,?,?)")
                ->execute([$title, $level, $desc, json_encode(array_values($items), JSON_UNESCAPED_UNICODE), $_SESSION['admin_id']]);
        } else {
            $pdo->prepare("INSERT INTO diet_plans (title, level, description, meals, created_by) VALUES (?,?,?,?,?)")
                ->execute([$title, $level, $desc, json_encode(array_values($items), JSON_UNESCAPED_UNICODE), $_SESSION['admin_id']]);
        }
        $msg = 'Plan created.';
    } else {
        $msg = 'Title and items are required.';
    }
}

$w = $pdo->query("SELECT id, title, level, created_at FROM workout_plans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$d = $pdo->query("SELECT id, title, level, created_at FROM diet_plans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Manage Plans</h2>
  </div>
  <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="form-grid">
    <div class="form-group">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" placeholder="e.g., Starter Full-Body" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Type</label>
        <select class="form-control" name="type">
          <option value="workout">Workout</option>
          <option value="diet">Diet</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Level</label>
        <select class="form-control" name="level">
          <option>Beginner</option><option>Intermediate</option><option>Advanced</option><option>Pro</option><option>Legendary</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="2"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Items (one per line)</label>
      <textarea class="form-control" name="items" rows="5" placeholder="Exercise or meal per line" required></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-success">Create</button>
    </div>
  </form>
</div>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Existing Plans</h2>
  </div>
  <h3 class="mb-1">Workout Plans</h3>
  <div class="plans-grid">
    <?php foreach ($w as $row): ?>
      <div class="plan-card">
        <div class="plan-icon">💪</div>
        <h3>Workout</h3>
        <h4><?= htmlspecialchars($row['title']) ?> (<?= htmlspecialchars($row['level']) ?>)</h4>
        <p>Created: <?= htmlspecialchars($row['created_at']) ?></p>
        <div class="form-actions">
          <a class="btn btn-secondary" href="<?= $BASE_PATH ?>/admin/edit_plan.php?id=<?= (int)$row['id'] ?>&type=workout">Edit</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this workout plan?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="type" value="workout">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h3 class="mt-2 mb-1">Diet Plans</h3>
  <div class="plans-grid">
    <?php foreach ($d as $row): ?>
      <div class="plan-card">
        <div class="plan-icon">🥗</div>
        <h3>Diet</h3>
        <h4><?= htmlspecialchars($row['title']) ?> (<?= htmlspecialchars($row['level']) ?>)</h4>
        <p>Created: <?= htmlspecialchars($row['created_at']) ?></p>
        <div class="form-actions">
          <a class="btn btn-secondary" href="<?= $BASE_PATH ?>/admin/edit_plan.php?id=<?= (int)$row['id'] ?>&type=diet">Edit</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this diet plan?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="type" value="diet">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
