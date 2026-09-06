<?php
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
    redirect('/auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT id, name, email, rank, height_cm, weight_kg, goal, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get assigned plans via admin assignment table if present
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

$stmt = $pdo->prepare("SELECT workout_plan_id, diet_plan_id FROM user_assigned_plans WHERE user_id = ?");
$stmt->execute([$user_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$user_plan = [
    'workout_title' => null,
    'workout_desc' => null,
    'diet_title' => null,
    'diet_desc' => null,
];

if ($assignment && !empty($assignment['workout_plan_id'])) {
    $w = $pdo->prepare('SELECT title, description FROM workout_plans WHERE id = ?');
    $w->execute([$assignment['workout_plan_id']]);
    if ($row = $w->fetch(PDO::FETCH_ASSOC)) {
        $user_plan['workout_title'] = $row['title'];
        $user_plan['workout_desc'] = $row['description'];
    }
}
if ($assignment && !empty($assignment['diet_plan_id'])) {
    $d = $pdo->prepare('SELECT title, description FROM diet_plans WHERE id = ?');
    $d->execute([$assignment['diet_plan_id']]);
    if ($row = $d->fetch(PDO::FETCH_ASSOC)) {
        $user_plan['diet_title'] = $row['title'];
        $user_plan['diet_desc'] = $row['description'];
    }
}

// Get progress stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_workouts, 
           SUM(minutes) as total_minutes,
           SUM(calories) as total_calories
    FROM progress_stats 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$progress_stats = $stmt->fetch();

// Get recent workouts
$stmt = $pdo->prepare("
    SELECT date, minutes, calories, notes FROM progress_stats 
    WHERE user_id = ? 
    ORDER BY date DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_workouts = $stmt->fetchAll();

// Update rank based on points
updateUserRank($user_id, $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fit-Mentor</title>
    <link rel="stylesheet" href="<?= rtrim($BASE_PATH, '/') ?>/assets/main.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="dashboard">
            <div class="dashboard-header">
                <div class="welcome-section">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
                    <p>Here's your fitness overview for today</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon rank">🥇</div>
                    <div class="stat-content">
                        <h3>Current Rank</h3>
                        <div class="stat-value"><?php echo htmlspecialchars($user['rank']); ?></div>
                        <div class="rank-badge" style="background: <?php echo getRankColor($user['rank']); ?>">
                            <?php echo htmlspecialchars($user['rank']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon workout">💪</div>
                    <div class="stat-content">
                        <h3>Total Workouts</h3>
                        <div class="stat-value"><?php echo $progress_stats['total_workouts'] ?? 0; ?></div>
                        <p>Workouts completed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bmi">📊</div>
                    <div class="stat-content">
                        <h3>BMI</h3>
                        <?php list($bmiVal, $bmiCat) = compute_bmi((float)($user['height_cm'] ?? 0), (float)($user['weight_kg'] ?? 0)); ?>
                        <div class="stat-value"><?php echo $bmiVal ? number_format($bmiVal, 1) : 'N/A'; ?></div>
                        <p>Body Mass Index</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Progress Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Your Progress</h2>
                        <a href="profile.php" class="btn btn-outline">View Details</a>
                    </div>
                    <div class="progress-grid">
                        <div class="progress-card">
                            <h3>Total Workout Time</h3>
                            <div class="progress-value"><?php echo $progress_stats['total_minutes'] ?? 0; ?> min</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($progress_stats['total_minutes'] ?? 0) / 10); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-card">
                            <h3>Calories Burned</h3>
                            <div class="progress-value"><?php echo $progress_stats['total_calories'] ?? 0; ?> cal</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($progress_stats['total_calories'] ?? 0) / 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Plans -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Your Current Plan</h2>
                        <a href="plans.php" class="btn btn-outline">View Plans</a>
                    </div>
                    <div class="plans-grid">
                        <?php if ($user_plan['workout_title'] || $user_plan['diet_title']): ?>
                            <?php if ($user_plan['workout_title']): ?>
                            <div class="plan-card">
                                <div class="plan-icon">💪</div>
                                <h3>Workout Plan</h3>
                                <h4><?php echo htmlspecialchars($user_plan['workout_title']); ?></h4>
                                <p><?php echo htmlspecialchars($user_plan['workout_desc']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($user_plan['diet_title']): ?>
                            <div class="plan-card">
                                <div class="plan-icon">🥗</div>
                                <h3>Diet Plan</h3>
                                <h4><?php echo htmlspecialchars($user_plan['diet_title']); ?></h4>
                                <p><?php echo htmlspecialchars($user_plan['diet_desc']); ?></p>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Plan Assigned</h3>
                                <p>You don't have any workout or diet plan assigned yet.</p>
                                <p>Please contact the administrator to get your personalized fitness plan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Recent Workouts</h2>
                        <a href="profile.php" class="btn btn-outline">View All</a>
                    </div>
                    <?php if ($recent_workouts): ?>
                        <div class="activity-list">
                            <?php foreach ($recent_workouts as $workout): ?>
                            <div class="activity-item">
                                <div class="activity-icon">🏃‍♂️</div>
                                <div class="activity-details">
                                    <h4>Workout</h4>
                                    <p><?php echo (int)$workout['minutes']; ?> min • <?php echo (int)$workout['calories']; ?> cal</p>
                                    <span class="activity-date"><?php echo date('M j, Y', strtotime($workout['date'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <h3>No Workouts Yet</h3>
                            <p>Start logging your workouts to track your progress and earn points!</p>
                            <button onclick="openModal('workout-modal')" class="btn btn-primary">Log Your First Workout</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Workout Modal -->
    <div id="workout-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Workout</h3>
                <button class="modal-close" onclick="closeModal('workout-modal')">&times;</button>
            </div>
            <form id="workout-form">
                <div class="form-group">
                    <label for="workout_date">Date</label>
                    <input type="date" class="form-control" id="workout_date" name="workout_date" required>
                </div>
                
                <div class="form-group">
                    <label for="workout_type">Workout Type</label>
                    <input type="text" class="form-control" id="workout_type" name="workout_type" placeholder="e.g., Running, Weight Training, Yoga" required>
                </div>
                
                <div class="form-group">
                    <label for="duration_minutes">Duration (minutes)</label>
                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="calories_burned">Calories Burned</label>
                    <input type="number" class="form-control" id="calories_burned" name="calories_burned" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes about your workout..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('workout-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Workout</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
    // Workout form submission
    document.getElementById('workout-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('progress.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal('workout-modal');
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while logging workout');
        });
    });

    // Set today's date as default for workout date
    document.getElementById('workout_date').valueAsDate = new Date();
    </script>
</body>
</html>