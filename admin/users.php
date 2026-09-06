<?php
$page_title = 'Users';
require_once __DIR__ . '/../config/db.php';
require_admin();

$users = $pdo->query(
    "SELECT u.id, u.name, u.email, u.rank, u.goal, u.created_at,
            (
              SELECT ps.notes FROM progress_stats ps
              WHERE ps.user_id = u.id AND ps.notes IS NOT NULL AND ps.notes <> ''
              ORDER BY ps.date DESC LIMIT 1
            ) AS last_feedback
     FROM users u
     ORDER BY u.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Try to fetch last_login for each user if the column exists
try {
    $stmtLL = $pdo->prepare("SELECT last_login FROM users WHERE id = ?");
    foreach ($users as &$u) {
        $stmtLL->execute([$u['id']]);
        if ($row = $stmtLL->fetch(PDO::FETCH_ASSOC)) {
            $u['last_login'] = $row['last_login'] ?? null;
        }
    }
    unset($u);
} catch (Throwable $e) {
    // Column may not exist; ignore gracefully
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-section">
  <div class="section-header">
    <h2>Registered Users</h2>
  </div>
  <table class="table">
    <thead>
      <tr><th>Name</th><th>Email</th><th>Rank</th><th>Goal</th><th>Joined</th><th>Last Login</th><th>Last Feedback</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <?php $rbColor = function_exists('getRankColor') ? getRankColor((string)$u['rank']) : '#7f8c8d'; ?>
          <td><span class="rank-badge" style="background: <?= htmlspecialchars($rbColor) ?>; color: #fff; border-color: <?= htmlspecialchars($rbColor) ?>;"><?= htmlspecialchars($u['rank']) ?></span></td>
          <td><?= htmlspecialchars($u['goal']) ?></td>
          <td><?= htmlspecialchars($u['created_at']) ?></td>
          <td><?= htmlspecialchars($u['last_login'] ?? '—') ?></td>
          <td class="notes-cell"><?= htmlspecialchars($u['last_feedback'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div><br><br><br><br><br><br><br><br><br><br><br><br><br>

<?php include __DIR__ . '/../includes/footer.php'; ?>
