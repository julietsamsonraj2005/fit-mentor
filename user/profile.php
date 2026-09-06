<?php
$page_title = 'Profile';
require_once __DIR__ . '/../config/config.php';
require_login();

$uid = $_SESSION['user_id'];
// Handle profile update submission
$message = null;
$messageType = null; // 'success' or 'error'
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name  = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $goal  = sanitize_input($_POST['goal'] ?? '');
    $height_cm = trim((string)($_POST['height_cm'] ?? ''));
    $weight_kg = trim((string)($_POST['weight_kg'] ?? ''));
    $gender = sanitize_input($_POST['gender'] ?? '');
    $age_in = trim((string)($_POST['age'] ?? ''));

    $errors = [];
    if ($name === '') { $errors[] = 'Name is required.'; }
    if ($name !== '' && !is_valid_name($name)) { $errors[] = 'Name may only contain letters A-Z and a-z.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
    if ($height_cm !== '' && !is_numeric($height_cm)) { $errors[] = 'Height must be a number (cm).'; }
    if ($weight_kg !== '' && !is_numeric($weight_kg)) { $errors[] = 'Weight must be a number (kg).'; }
    // Validate Age if provided (integer 0-150)
    $age_to_save = null;
    if ($age_in !== '') {
      if (!is_numeric($age_in)) {
        $errors[] = 'Age must be a number.';
      } else {
        $age_int = (int)$age_in;
        if ($age_int < 0 || $age_int > 150) {
          $errors[] = 'Age must be between 0 and 150.';
        } else {
          $age_to_save = $age_int;
        }
      }
    }

  if (empty($errors)) {
    try {
      // Update core fields
      $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, goal = :goal, height_cm = :h, weight_kg = :w WHERE id = :id");
      $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':goal' => ($goal !== '' ? $goal : null),
        ':h' => ($height_cm !== '' ? (float)$height_cm : null),
        ':w' => ($weight_kg !== '' ? (float)$weight_kg : null),
        ':id' => $uid,
      ]);

      // Attempt to update optional columns if present
      // Update gender independently
      try {
        $stmt2 = $pdo->prepare("UPDATE users SET gender = :g WHERE id = :id");
        $stmt2->execute([
          ':g' => ($gender !== '' ? $gender : null),
          ':id' => $uid,
        ]);
      } catch (Throwable $e2) { /* ignore if column missing */ }
      // Also try updating legacy 'sex' column if present
      try {
        $stmt2b = $pdo->prepare("UPDATE users SET sex = :g WHERE id = :id");
        $stmt2b->execute([
          ':g' => ($gender !== '' ? $gender : null),
          ':id' => $uid,
        ]);
      } catch (Throwable $e2b) { /* ignore if column missing */ }
      // Update age independently (if column exists)
      try {
        $stmt3 = $pdo->prepare("UPDATE users SET age = :a WHERE id = :id");
        $stmt3->execute([
          ':a' => $age_to_save,
          ':id' => $uid,
        ]);
      } catch (Throwable $e3) { /* ignore if column missing */ }
      $message = 'Profile updated successfully.';
      $messageType = 'success';
    } catch (Throwable $e) {
      $message = 'Failed to update profile. ';
      // Common duplicate email error code in MySQL is 1062; keep message generic.
      $messageType = 'error';
    }
  } else {
    $message = implode(' ', $errors);
    $messageType = 'error';
  }
}

$user = $pdo->prepare("SELECT name, email, rank, goal, height_cm, weight_kg, created_at FROM users WHERE id=?");
$user->execute([$uid]);
$u = $user->fetch(PDO::FETCH_ASSOC);

// Try to fetch optional fields (gender, age) if they exist in schema
$age = null; $gender = null;
// Fetch gender independently so failure of "dob" doesn't block it
try {
  $optGender = $pdo->prepare("SELECT gender FROM users WHERE id=?");
  $optGender->execute([$uid]);
  if ($row = $optGender->fetch(PDO::FETCH_ASSOC)) {
    $gender = $row['gender'] ?? null;
  }
} catch (Throwable $e) { /* ignore */ }

// Fallback to legacy 'sex' column if gender is null/empty
if ($gender === null || $gender === '') {
  try {
    $optSex = $pdo->prepare("SELECT sex FROM users WHERE id=?");
    $optSex->execute([$uid]);
    if ($row = $optSex->fetch(PDO::FETCH_ASSOC)) {
      $gender = $row['sex'] ?? null;
    }
  } catch (Throwable $e) { /* ignore */ }
}

// Fetch age directly from column (if exists)
try {
  $optAge = $pdo->prepare("SELECT age FROM users WHERE id=?");
  $optAge->execute([$uid]);
  if ($row = $optAge->fetch(PDO::FETCH_ASSOC)) {
    $ageVal = $row['age'] ?? null;
    if ($ageVal !== null && is_numeric($ageVal)) {
      $age = (int)$ageVal;
    }
  }
} catch (Throwable $e) { /* ignore */ }

$stats = $pdo->prepare("SELECT date, minutes, calories, notes FROM progress_stats WHERE user_id=? ORDER BY date DESC LIMIT 30");
$stats->execute([$uid]);
$rows = $stats->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="profile-content">
  <div class="profile-section">
    <div class="section-header">
      <h2>Your Information</h2>
      <button id="toggleEditProfile" class="btn btn-secondary">Edit Profile</button>
    </div>
    <?php if ($message): ?>
      <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-danger' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <div class="stats-grid compact">
      <div class="stat-item">
        <div class="stat-label">Name</div>
        <div class="stat-value" style="font-size:1.2rem;"><?= htmlspecialchars($u['name']) ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Email</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= htmlspecialchars($u['email']) ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Age</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= $age !== null ? intval($age) . ' yrs' : '—' ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Gender</div>
        <div style="font-weight:600; color:var(--dark-color);">
          <?php
            $gdisp = $gender;
            if ($gdisp !== null) { $gdisp = trim((string)$gdisp); }
            if ($gdisp === null || $gdisp === '') { echo '—'; }
            else { echo htmlspecialchars(ucwords(strtolower($gdisp))); }
          ?>
        </div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Rank</div>
        <?php $rbColor = function_exists('getRankColor') ? getRankColor((string)$u['rank']) : '#7f8c8d'; ?>
        <span class="rank-badge" style="background: <?= htmlspecialchars($rbColor) ?>; color: #fff; border-color: <?= htmlspecialchars($rbColor) ?>;">
          <?= htmlspecialchars($u['rank']) ?>
        </span>
      </div>
      <div class="stat-item">
        <div class="stat-label">Goal</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= htmlspecialchars($u['goal'] ?? '—') ?></div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Height / Weight</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= htmlspecialchars($u['height_cm'] ?? '—') ?> cm / <?= htmlspecialchars($u['weight_kg'] ?? '—') ?> kg</div>
      </div>
      <?php [$bmiVal, $bmiCat] = compute_bmi(floatval($u['height_cm'] ?? 0), floatval($u['weight_kg'] ?? 0)); ?>
      <div class="stat-item">
        <div class="stat-label">Last BMI</div>
        <div style="font-weight:600; color:var(--dark-color);">
          <?= $bmiVal !== null ? htmlspecialchars((string)$bmiVal) . ' (' . htmlspecialchars($bmiCat) . ')' : '—' ?>
        </div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Joined</div>
        <div style="font-weight:600; color:var(--dark-color);"><?= htmlspecialchars($u['created_at']) ?></div>
      </div>
    </div>

    <form id="editProfileForm" action="" method="post" class="mt-2 hidden">
      <input type="hidden" name="action" value="update_profile">
      <div class="form-row">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" class="form-control" pattern="^[A-Za-z]+$" title="Only letters A-Z and a-z are allowed" value="<?= htmlspecialchars($u['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="goal">Goal</label>
          <select id="goal" name="goal" class="form-control">
            <?php $goalSel = strtolower((string)($u['goal'] ?? '')); ?>
            <option value="" <?= $goalSel === '' ? 'selected' : '' ?>>Select goal</option>
            <option value="build muscle" <?= $goalSel === 'build muscle' ? 'selected' : '' ?>>Build muscle</option>
            <option value="loss weight" <?= $goalSel === 'loss weight' ? 'selected' : '' ?>>Loss weight</option>
            <option value="keep fit" <?= $goalSel === 'keep fit' ? 'selected' : '' ?>>Keep fit</option>
          </select>
        </div>
        <div class="form-group">
          <label for="height_cm">Height (cm)</label>
          <input type="number" step="0.1" id="height_cm" name="height_cm" class="form-control" value="<?= htmlspecialchars((string)($u['height_cm'] ?? '')) ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="weight_kg">Weight (kg)</label>
          <input type="number" step="0.1" id="weight_kg" name="weight_kg" class="form-control" value="<?= htmlspecialchars((string)($u['weight_kg'] ?? '')) ?>">
        </div>
        <div class="form-group">
          <label for="gender">Gender</label>
          <select id="gender" name="gender" class="form-control">
            <?php $g = strtolower((string)$gender); ?>
            <option value="" <?= $g === '' ? 'selected' : '' ?>>Select gender</option>
            <option value="Male" <?= $g === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $g === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $g === 'other' ? 'selected' : '' ?>>Other</option>
            <option value="Prefer not to say" <?= $g === 'prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="age">Age</label>
          <input type="number" id="age" name="age" class="form-control" min="0" max="150" value="<?= $age !== null ? intval($age) : '' ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" id="cancelEdit" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>

  <div class="profile-section table-card">
    <div class="section-header">
      <h2>Recent Progress</h2>
    </div>
    <table class="table">
      <thead><tr><th>Date</th><th>Minutes</th><th>Calories</th><th>Feedback</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['date']) ?></td>
            <td><?= intval($r['minutes']) ?></td>
            <td><?= intval($r['calories']) ?></td>
            <td class="notes-cell"><?= htmlspecialchars($r['notes'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div><br><br><br><br>

<script>
  (function(){
    const btn = document.getElementById('toggleEditProfile');
    const form = document.getElementById('editProfileForm');
    const cancelBtn = document.getElementById('cancelEdit');
    if (btn && form) {
      btn.addEventListener('click', function(){
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
          btn.textContent = 'Hide Form';
        } else {
          btn.textContent = 'Edit Profile';
        }
      });
    }
    if (cancelBtn && form && btn) {
      cancelBtn.addEventListener('click', function(){
        form.classList.add('hidden');
        btn.textContent = 'Edit Profile';
      });
    }
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
