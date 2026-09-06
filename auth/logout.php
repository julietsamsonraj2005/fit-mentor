<?php
require_once __DIR__ . '/../config/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
redirect('/');
?>