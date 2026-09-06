<?php
$page_title = 'Ranking';
require_once __DIR__ . '/../config/config.php';
require_login();

$uid = $_SESSION['user_id'];

// Sync and compute user's current rank using helper if available
try { updateUserRank($uid, $pdo); } catch (Throwable $e) { /* non-fatal */ }

// Fetch user's totals
$totals = $pdo->prepare("SELECT COALESCE(SUM(minutes),0) AS total_minutes, COALESCE(SUM(calories),0) AS total_calories FROM progress_stats WHERE user_id = ?");
$totals->execute([$uid]);
$t = $totals->fetch(PDO::FETCH_ASSOC) ?: ['total_minutes' => 0, 'total_calories' => 0];
$minutes = (int)($t['total_minutes'] ?? 0);
$calories = (int)($t['total_calories'] ?? 0);

// Compute points and rank
$points = $minutes + intdiv($calories, 10);
if (function_exists('rank_for_points')) {
    $rank = rank_for_points($minutes, $calories);
} else {
    $rank = ($points >= 1500) ? 'Legendary' : (($points >= 700) ? 'World class' : (($points >= 300) ? 'Professional' : (($points >= 100) ? 'Amateur' : 'Beginner')));
}

// Determine thresholds and progress to next rank
$thresholds = [
    'Beginner'    => 0,
    'Amateur'     => 100,
    'Professional'=> 300,
    'World class' => 700,
    'Legendary'   => 1500,
];

$ranksOrdered = array_keys($thresholds);
$currentIndex = array_search($rank, $ranksOrdered, true);
if ($currentIndex === false) { $currentIndex = 0; }
$nextIndex = min($currentIndex + 1, count($ranksOrdered) - 1);
$nextRank = ($nextIndex === $currentIndex) ? null : $ranksOrdered[$nextIndex];
$nextTarget = $thresholds[$nextRank] ?? null;
$toNext = ($nextTarget !== null) ? max(0, $nextTarget - $points) : 0;
$fromCurrentFloor = $thresholds[$ranksOrdered[$currentIndex]] ?? 0;
$range = ($nextTarget !== null) ? max(1, $nextTarget - $fromCurrentFloor) : 1;
$progressPct = ($nextTarget !== null) ? max(0, min(100, round((($points - $fromCurrentFloor) / $range) * 100))) : 100;

// Fetch user profile basics for display
$usrStmt = $pdo->prepare("SELECT name, rank FROM users WHERE id = ?");
$usrStmt->execute([$uid]);
$usr = $usrStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'You', 'rank' => $rank];
$rankDisplay = $usr['rank'] ?: $rank;
$rankColor = function_exists('getRankColor') ? getRankColor($rankDisplay) : '#7f8c8d';

// Leaderboard Top 10 by points
$leaderboard = [];
try {
    $lb = $pdo->query(
        "SELECT u.name,
                COALESCE(SUM(ps.minutes),0) AS minutes,
                COALESCE(SUM(ps.calories),0) AS calories,
                (COALESCE(SUM(ps.minutes),0) + FLOOR(COALESCE(SUM(ps.calories),0)/10)) AS points,
                COALESCE(u.rank, '') AS rank
         FROM users u
         LEFT JOIN progress_stats ps ON ps.user_id = u.id
         GROUP BY u.id
         ORDER BY points DESC, u.name ASC
         LIMIT 10"
    );
    $leaderboard = $lb->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* ignore */ }

include __DIR__ . '/../includes/header.php';
?>

<div class="profile-content">
  <div class="profile-section">
    <div class="section-header">
      <h2>Your Ranking</h2>
    </div>
    <div class="stats-grid compact">
      <div class="stat-item">
        <div class="stat-label">Name</div>
        <div class="stat-value" style="font-size:1.2rem; font-weight:700;"><?= htmlspecialchars($usr['name'] ?? 'You') ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Current Rank</div>
        <span class="rank-badge" style="background: <?= htmlspecialchars($rankColor) ?>; border-color: <?= htmlspecialchars($rankColor) ?>; color: #fff;">
          <?= htmlspecialchars($rankDisplay) ?>
        </span>
      </div>
      <div class="stat-item">
        <div class="stat-label">Points</div>
        <div class="stat-value" style="font-size:1.2rem;"><?= (int)$points ?></div>
        <div class="stat-subtext">Formula: minutes + calories/10</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Workout Minutes</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= (int)$minutes ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Calories</div>
        <div style="font-weight:600; color:var(--dark-color);">
          <?= (int)$calories ?> (<?= (int)floor($calories/10) ?> pts)
        </div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Progress to Next</div>
        <div style="font-weight:600; color:var(--dark-color);">
          <?php if ($nextRank): ?>
            <?= htmlspecialchars($nextRank) ?> in <?= (int)$toNext ?> pts
          <?php else: ?>
            Max rank achieved
          <?php endif; ?>
        </div>
        <div class="progress-bar" style="margin-top:8px;">
          <div class="progress-fill" style="width: <?= (int)$progressPct ?>%;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="profile-section table-card">
    <div class="section-header">
      <h2>Rank Thresholds</h2>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Points Required</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($thresholds as $r => $minPts): ?>
          <tr>
            <?php $col = function_exists('getRankColor') ? getRankColor($r) : '#7f8c8d'; ?>
            <td><span class="rank-badge" style="background: <?= htmlspecialchars($col) ?>; border-color: <?= htmlspecialchars($col) ?>; color: #fff; "><?= htmlspecialchars($r) ?></span></td>
            <td><?= (int)$minPts ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="profile-section table-card">
    <div class="section-header">
      <h2>Leaderboard (Top 10)</h2>
    </div>
    <?php if (empty($leaderboard)): ?>
      <div class="empty-state">
        <div class="empty-icon">🏆</div>
        <h3>No leaderboard data yet</h3>
        <p>Start logging your workouts to appear on the leaderboard.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Rank</th>
            <th>Minutes</th>
            <th>Calories</th>
            <th>Points</th>
          </tr>
        </thead>
        <tbody>
          <?php $pos = 1; foreach ($leaderboard as $row): ?>
            <tr>
              <td><?= $pos++ ?></td>
              <td><?= htmlspecialchars($row['name'] ?? '—') ?></td>
              <?php 
                $rowRank = $row['rank'] ?: (function_exists('rank_for_points') ? rank_for_points((int)$row['minutes'], (int)$row['calories']) : '');
                $rowCol = function_exists('getRankColor') ? getRankColor($rowRank) : '#7f8c8d';
              ?>
              <td><span class="rank-badge" style="background: <?= htmlspecialchars($rowCol) ?>; border-color: <?= htmlspecialchars($rowCol) ?>; color: #fff; "><?= htmlspecialchars($rowRank) ?></span></td>
              <td><?= (int)$row['minutes'] ?></td>
              <td><?= (int)$row['calories'] ?></td>
              <td><?= (int)$row['points'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
