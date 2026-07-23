<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = "http://localhost/findit/";

require_once __DIR__ . '/../../config/db.php';

// Check if logged in AND role is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access Denied! You must be logged in as an Administrator to access the Admin Panel.";
    header("Location: " . $base_url . "auth/login.php");
    exit;
}
?>
