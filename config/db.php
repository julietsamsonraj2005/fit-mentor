<?php
$DB_HOST = 'localhost';
$DB_NAME = 'fit_mentor';
$DB_USER = 'root';
$DB_PASS = ''; // Set your password if any

// Base path for redirects (ensure this matches your web root folder name)
$BASE_PATH = '/fit_mentor';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Use separate session cookies so admin and user can be logged in simultaneously
// Decide which session name to use BEFORE session_start()
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$intendedUserType = $_POST['user_type'] ?? null; // during login POST

if (strpos($requestUri, '/admin/') !== false
    || $intendedUserType === 'admin'
    || isset($_COOKIE['FITADMINSESSID'])) {
    $sessionName = 'FITADMINSESSID';
} else {
    $sessionName = 'FITUSERSESSID';
}

// Scope cookies to the app path for consistency
if (PHP_SESSION_ACTIVE !== session_status()) {
    session_name($sessionName);
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $BASE_PATH,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        global $BASE_PATH;
        header('Location: ' . $BASE_PATH . '/auth/login.php');
        exit;
    }
}

function require_admin() {
    if (!isset($_SESSION['admin_id'])) {
        global $BASE_PATH;
        header('Location: ' . $BASE_PATH . '/auth/login.php');
        exit;
    }
}

function compute_bmi($height_cm, $weight_kg) {
    if ($height_cm <= 0 || $weight_kg <= 0) return [null, 'Invalid'];
    $h_m = $height_cm / 100.0;
    $bmi = $weight_kg / ($h_m * $h_m);
    $category = ($bmi < 18.5) ? 'Underweight' :
                (($bmi < 24.9) ? 'Normal' :
                (($bmi < 29.9) ? 'Overweight' : 'Obese'));
    return [round($bmi, 2), $category];
}

function rank_for_points($minutes, $calories) {
    // Temporary score model: minutes + (calories/10). Adjust later if switching to explicit points.
    $score = $minutes + intval($calories / 10);
    if ($score >= 1500) return 'Legendary';
    if ($score >= 700)  return 'World class';
    if ($score >= 300)  return 'Professional';
    if ($score >= 100)  return 'Amateur';
    return 'Beginner';
}
