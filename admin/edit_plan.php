<?php
$page_title = 'Edit Plan';
require_once __DIR__ . '/../config/db.php';
require_admin();

// Validate query params
$type = isset($_GET['type']) && in_array($_GET['type'], ['workout', 'diet'], true) ? $_GET['type'] : 'workout';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid plan id';
    header('Location: ' . $BASE_PATH . '/admin/plans.php');
    exit;
}

// Fetch plan
if ($type === 'workout') {
    $stmt = $pdo->prepare('SELECT id, title, level, description, exercises AS items FROM workout_plans WHERE id = ?');
} else {
    $stmt = $pdo->prepare('SELECT id, title, level, description, meals AS items FROM diet_plans WHERE id = ?');
}
$stmt->execute([$id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$plan) {
    $_SESSION['flash_error'] = 'Plan not found';
    header('Location: ' . $BASE_PATH . '/admin/plans.php');
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $level = $_POST['level'] ?? $plan['level'];
    $desc  = trim($_POST['description'] ?? '');
    $items = array_filter(array_map('trim', explode("\n", $_POST['items'] ?? '')));

    if (!$title || empty($items)) {
        $_SESSION['flash_error'] = 'Title and at least one item are required.';
    } else {
        $jsonItems = json_encode(array_values($items), JSON_UNESCAPED_UNICODE);
        if ($type === 'workout') {
            $upd = $pdo->prepare('UPDATE workout_plans SET title=?, level=?, description=?, exercises=? WHERE id=?');
        } else {
            $upd = $pdo->prepare('UPDATE diet_plans SET title=?, level=?, description=?, meals=? WHERE id=?');
        }
        $upd->execute([$title, $level, $desc, $jsonItems, $id]);
        $_SESSION['flash_success'] = ucfirst($type) . ' plan updated successfully';
        header('Location: ' . $BASE_PATH . '/admin/plans.php');
        exit;
    }
}

// Pre-fill items as newline text
$itemsArray = json_decode($plan['items'] ?? '[]', true);
if (!is_array($itemsArray)) { $itemsArray = []; }
$itemsText = implode("\n", $itemsArray);

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Edit <?= $type === 'workout' ? 'Workout' : 'Diet' ?> Plan</h2>
    <a class="btn btn-secondary" href="<?= $BASE_PATH ?>/admin/plans.php">Back</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); endif; ?>

  <form method="post" class="form-grid">
    <div class="form-group">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" value="<?= htmlspecialchars($plan['title']) ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Type</label>
        <input class="form-control" value="<?= $type === 'workout' ? 'Workout' : 'Diet' ?>" disabled>
      </div>
      <div class="form-group">
        <label class="form-label">Level</label>
        <select class="form-control" name="level">
          <?php foreach (['Beginner','Intermediate','Advanced','Pro','Legendary'] as $lvl): ?>
            <option value="<?= $lvl ?>" <?= $lvl === $plan['level'] ? 'selected' : '' ?>><?= $lvl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($plan['description']) ?></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">Items (one per line)</label>
      <textarea class="form-control" name="items" rows="6" required><?= htmlspecialchars($itemsText) ?></textarea>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
