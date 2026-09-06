<?php
$page_title = 'BMI history';
require_once __DIR__ . '/../config/config.php';
require_login();

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT height_cm, weight_kg, bmi, category, recorded_at FROM bmi_history WHERE user_id=? ORDER BY recorded_at DESC");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h3>BMI history</h3>
<table class="table table-striped">
  <thead><tr>
    <th>Date</th><th>Height (cm)</th><th>Weight (kg)</th><th>BMI</th><th>Category</th>
  </tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['recorded_at']) ?></td>
        <td><?= htmlspecialchars($r['height_cm']) ?></td>
        <td><?= htmlspecialchars($r['weight_kg']) ?></td>
        <td><?= htmlspecialchars($r['bmi']) ?></td>
        <td><?= htmlspecialchars($r['category']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
