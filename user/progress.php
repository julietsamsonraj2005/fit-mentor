<?php
require_once __DIR__ . '/../config/config.php';
require_login();

$uid = $_SESSION['user_id'];

// Ensure optional column exists to store workout type (plan title)
try {
    $pdo->exec("ALTER TABLE progress_stats ADD COLUMN IF NOT EXISTS workout_type VARCHAR(150) NULL");
} catch (Throwable $e) { /* ignore for older MySQL versions */ }

// Handle POST: log workout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $minutes = max(0, intval($_POST['minutes'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');
    $workout_type = trim($_POST['workout_type'] ?? '');

    // Compute calories server-side
    $wu = $pdo->prepare("SELECT weight_kg FROM users WHERE id=?");
    $wu->execute([$uid]);
    $weightRow = $wu->fetch(PDO::FETCH_ASSOC) ?: ['weight_kg' => null];
    $weight = (float)($weightRow['weight_kg'] ?? 0);
    // Simple estimate: MET 6.0 (moderate) if weight known, else fallback 5 cal/min
    if ($weight > 0 && $minutes > 0) {
        $met = 6.0;
        $calories = (int) round(($met * 3.5 * $weight / 200) * $minutes);
    } else {
        $calories = (int) ($minutes * 5);
    }
    try {
        $pdo->prepare("INSERT INTO progress_stats (user_id, date, minutes, calories, notes, workout_type) VALUES (?,?,?,?,?,?)")
            ->execute([$uid, $date, $minutes, $calories, $notes, $workout_type ?: null]);
    } catch (PDOException $e) {
        // If duplicate date, update instead
        $pdo->prepare("UPDATE progress_stats SET minutes=?, calories=?, notes=?, workout_type=? WHERE user_id=? AND date=?")
            ->execute([$minutes, $calories, $notes, ($workout_type ?: null), $uid, $date]);
    }

    // Update rank
    updateUserRank($uid, $pdo);

    // If AJAX, return JSON; else redirect back to this page
    $is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($is_xhr || (isset($_GET['format']) && $_GET['format'] === 'json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Workout logged successfully']);
        exit;
    }
    global $BASE_PATH;
    header('Location: ' . rtrim($BASE_PATH, '/') . '/user/progress.php');
    exit;
}

// GET: render analytics page
// Fetch recent 30 entries for charts
$rows = $pdo->prepare("SELECT date, minutes, calories FROM progress_stats WHERE user_id=? ORDER BY date ASC LIMIT 30");
$rows->execute([$uid]);
$chartRows = $rows->fetchAll(PDO::FETCH_ASSOC);

// Totals
$tot = $pdo->prepare("SELECT COUNT(*) as days, SUM(minutes) as minutes, SUM(calories) as calories FROM progress_stats WHERE user_id=?");
$tot->execute([$uid]);
$totals = $tot->fetch(PDO::FETCH_ASSOC) ?: ['days'=>0,'minutes'=>0,'calories'=>0];

// User for BMI
$uStmt = $pdo->prepare("SELECT height_cm, weight_kg FROM users WHERE id=?");
$uStmt->execute([$uid]);
$u = $uStmt->fetch(PDO::FETCH_ASSOC) ?: ['height_cm'=>null,'weight_kg'=>null];
list($bmiVal, $bmiCat) = compute_bmi((float)($u['height_cm'] ?? 0), (float)($u['weight_kg'] ?? 0));

// Workout plans list for dropdown
$wlist = $pdo->query("SELECT id, title, level FROM workout_plans ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Your Progress & Analytics</h2>
  </div>
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon workout">⏱️</div>
      <div class="stat-content">
        <h3>Total Minutes</h3>
        <div class="stat-value"><?= (int)($totals['minutes'] ?? 0) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔥</div>
      <div class="stat-content">
        <h3>Total Calories</h3>
        <div class="stat-value"><?= (int)($totals['calories'] ?? 0) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon bmi">📊</div>
      <div class="stat-content">
        <h3>Current BMI</h3>
        <div class="stat-value"><?= $bmiVal ? number_format($bmiVal,1) : 'N/A' ?></div>
        <p><?= htmlspecialchars($bmiCat) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Charts</h2>
  </div>
  <div class="progress-grid">
    <div class="progress-card">
      <h3>Minutes over time</h3>
      <canvas id="minutesChart" width="600" height="240"></canvas>
    </div>
    <div class="progress-card">
      <h3>Calories over time</h3>
      <canvas id="caloriesChart" width="600" height="240"></canvas>
    </div>
  </div>
</div>

<div class="dashboard-section">
  <div class="section-header">
    <h2>BMI Calculator</h2>
  </div>
  <div class="form-grid">
    <div class="form-row">
      <div class="form-group">
        <label>Height (cm)</label>
        <input type="number" id="bmi_height" class="form-control" min="50" max="250" value="<?= htmlspecialchars((string)($u['height_cm'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>Weight (kg)</label>
        <input type="number" id="bmi_weight" class="form-control" min="20" max="400" value="<?= htmlspecialchars((string)($u['weight_kg'] ?? '')) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" id="bmi_calc_btn">Calculate BMI</button>
      <div id="bmi_result" class="stat-subtext"></div>
    </div>
  </div>
</div>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Log Workout</h2>
  </div>
  <form id="progress-log-form" method="post" class="form-grid">
    <div class="form-row">
      <div class="form-group">
        <label>Date</label>
        <input type="date" class="form-control" name="date" id="p_date" required>
      </div>
      <div class="form-group">
        <label>Workout Type</label>
        <select class="form-control" name="workout_type" id="p_type">
          <option value="">Select a workout</option>
          <?php foreach ($wlist as $opt): ?>
            <option value="<?= htmlspecialchars($opt['title']) ?>"><?= htmlspecialchars($opt['title']) ?> (<?= htmlspecialchars($opt['level']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Minutes</label>
        <input type="number" class="form-control" name="minutes" id="p_minutes" min="1" required>
      </div>
    </div>
    <div class="form-group">
      <label>Feedback</label>
      <textarea class="form-control" name="notes" id="p_notes" rows="3" placeholder="Any Feedback..."></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Save</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= rtrim($BASE_PATH, '/') ?>/assets/charts.js"></script>
<script>
  // Seed data for charts
  const progressData = <?= json_encode([
    'labels' => array_map(fn($r)=>$r['date'], $chartRows),
    'minutes' => array_map(fn($r)=> (int)$r['minutes'], $chartRows),
    'calories' => array_map(fn($r)=> (int)$r['calories'], $chartRows),
  ], JSON_UNESCAPED_UNICODE); ?>;

  // Render charts
  renderLineChart('minutesChart', progressData.labels, progressData.minutes, '#4a90e2');
  renderLineChart('caloriesChart', progressData.labels, progressData.calories, '#f39c12');

  // BMI calculator
  document.getElementById('bmi_calc_btn').addEventListener('click', function(e){
    e.preventDefault();
    calcBMI('bmi_height','bmi_weight','bmi_result');
  });

  // Progress form AJAX
  document.getElementById('progress-log-form').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fetch('progress.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
      .then(r=>r.json())
      .then(d=>{ alert(d.message || 'Saved'); location.reload(); })
      .catch(()=> alert('Error saving progress'));
  });

  // Defaults
  document.getElementById('p_date').valueAsDate = new Date();
</script>
