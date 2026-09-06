<?php
// Admin page + endpoint to assign workout/diet plans to a user
// GET  -> render form
// POST -> save assignment and redirect (or return JSON for XHR)

require_once __DIR__ . '/../config/db.php';
require_admin();

// Ensure support table exists
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

function respond_json($arr, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $workout_plan_id = isset($_POST['workout_plan_id']) && $_POST['workout_plan_id'] !== '' ? intval($_POST['workout_plan_id']) : null;
        $diet_plan_id = isset($_POST['diet_plan_id']) && $_POST['diet_plan_id'] !== '' ? intval($_POST['diet_plan_id']) : null;

        if ($user_id <= 0) {
            return respond_json(['success' => false, 'message' => 'user_id is required'], 400);
        }

        // Validate referenced ids (if provided)
        if ($workout_plan_id !== null) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM workout_plans WHERE id=?');
            $chk->execute([$workout_plan_id]);
            if (!$chk->fetchColumn()) {
                return respond_json(['success' => false, 'message' => 'Invalid workout_plan_id'], 400);
            }
        }
        if ($diet_plan_id !== null) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM diet_plans WHERE id=?');
            $chk->execute([$diet_plan_id]);
            if (!$chk->fetchColumn()) {
                return respond_json(['success' => false, 'message' => 'Invalid diet_plan_id'], 400);
            }
        }

        // Upsert assignment (one active assignment per user) - handles unchanged values gracefully
        $sql = 'INSERT INTO user_assigned_plans (user_id, workout_plan_id, diet_plan_id)
                VALUES (:u, :w, :d)
                ON DUPLICATE KEY UPDATE
                    workout_plan_id = VALUES(workout_plan_id),
                    diet_plan_id    = VALUES(diet_plan_id),
                    assigned_at     = CURRENT_TIMESTAMP';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $user_id, ':w' => $workout_plan_id, ':d' => $diet_plan_id]);

        // If XHR or explicitly requested JSON, return JSON
        $is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($is_xhr || (isset($_GET['format']) && $_GET['format'] === 'json')) {
            return respond_json(['success' => true, 'message' => 'Plan assignment saved successfully']);
        }

        // Otherwise redirect with flash message
        $_SESSION['flash_success'] = 'Plan assignment saved successfully';
        global $BASE_PATH;
        header('Location: ' . $BASE_PATH . '/admin/assign_plan.php');
        exit;
    } catch (Throwable $e) {
        $is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($is_xhr || (isset($_GET['format']) && $_GET['format'] === 'json')) {
            return respond_json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
        $_SESSION['flash_error'] = 'Server error: ' . $e->getMessage();
        global $BASE_PATH;
        header('Location: ' . $BASE_PATH . '/admin/assign_plan.php');
        exit;
    }
}

// GET: render admin form
$page_title = 'Assign plans to user';
include __DIR__ . '/../includes/header.php';

// Load users and plans for selects
$users = $pdo->query("SELECT id, name, email, rank FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$wplans = $pdo->query("SELECT id, title, level FROM workout_plans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$dplans = $pdo->query("SELECT id, title, level FROM diet_plans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Assign Plans</h2>
    <a class="btn btn-secondary" href="<?= $BASE_PATH ?>/admin/dashboard.php">Back</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); endif; ?>

  <form method="post" class="form-grid">
    <div class="form-group">
      <label class="form-label">User</label>
      <select class="form-control" name="user_id" required>
        <option value="">Select user</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>) - <?= htmlspecialchars($u['rank']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Workout plan (optional)</label>
        <select class="form-control" name="workout_plan_id">
          <option value="">-- None --</option>
          <?php foreach ($wplans as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['level']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Diet plan (optional)</label>
        <select class="form-control" name="diet_plan_id">
          <option value="">-- None --</option>
          <?php foreach ($dplans as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['level']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary">Save assignment</button>
    </div>
  </form>
</div><br><br><br><br><br><br><br><br><br><br>

<?php include __DIR__ . '/../includes/footer.php'; ?>
