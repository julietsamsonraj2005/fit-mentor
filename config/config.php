<?php
require_once __DIR__ . '/../config/db.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function redirect(string $path): void {
    global $BASE_PATH;
    // Absolute URL
    if (preg_match('/^https?:\/\//i', $path)) {
        header('Location: ' . $path);
        exit;
    }
    // If path starts with '/', treat as site-absolute
    if (strpos($path, '/') === 0) {
        header('Location: ' . rtrim($BASE_PATH, '/') . $path);
        exit;
    }
    // Otherwise, path relative to BASE_PATH root
    header('Location: ' . rtrim($BASE_PATH, '/') . '/' . ltrim($path, '/'));
    exit;
}

function sanitize_input(?string $value): string {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function is_valid_name(string $name): bool {
    return preg_match('/^[A-Za-z]+$/', $name) === 1;
}

// Compute and persist user's rank and points based on workout progress
// Uses minutes + calories/10 as a simple score, then maps to rank using rank_for_points() from db.php
function updateUserRank(int $user_id, PDO $pdo): void {
    // Sum stats from user_progress if table exists
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(minutes),0) AS total_minutes, COALESCE(SUM(calories),0) AS total_calories FROM progress_stats WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_minutes' => 0, 'total_calories' => 0];
        $minutes = (int)($row['total_minutes'] ?? 0);
        $calories = (int)($row['total_calories'] ?? 0);

        // Score model and rank mapping
        if (!function_exists('rank_for_points')) {
            // Fallback mapping if helper not present
            $score = $minutes + intdiv($calories, 10);
            $rank = ($score >= 1500) ? 'Legendary' : (($score >= 700) ? 'World class' : (($score >= 300) ? 'Professional' : (($score >= 100) ? 'Amateur' : 'Beginner')));
        } else {
            $rank = \rank_for_points($minutes, $calories);
        }
        $points = $minutes + intdiv($calories, 10);

        // Persist to users table if columns exist
        // Attempt to update columns; ignore if they don't exist
        try {
            $upd = $pdo->prepare("UPDATE users SET rank = :r WHERE id = :id");
            $upd->execute([':r' => $rank, ':id' => $user_id]);
        } catch (Throwable $e) {
            // Silently ignore if schema differs
        }
    } catch (Throwable $e) {
        // Ignore errors to avoid breaking the page
    }
}

// Map rank to a color used in UI badges
function getRankColor(string $rank): string {
    $map = [
        'Legendary'   => '#8e44ad',
        'World class' => '#e67e22',
        'Professional'=> '#2980b9',
        'Amateur'     => '#27ae60',
        'Beginner'    => '#7f8c8d',
    ];
    return $map[$rank] ?? '#7f8c8d';
}
