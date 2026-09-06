<?php
require_once __DIR__ . '/config/db.php';

// If admin logged in, go to admin dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . rtrim($BASE_PATH, '/') . '/admin/dashboard.php');
    exit;
}

// If user logged in, go to user dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . rtrim($BASE_PATH, '/') . '/user/dashboard.php');
    exit;
}

// Otherwise, serve the marketing landing page
// Ensure correct content type and output the static HTML
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');
exit;
