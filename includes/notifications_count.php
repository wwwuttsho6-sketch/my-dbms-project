<?php
require_once __DIR__ . '/../config/db.php';

// If no session exists or user is not logged in, return 0
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Count rows where is_read is 0 (unread) for the logged-in user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();
}

// If this file is requested directly via AJAX, it outputs just the number
if (basename($_SERVER['PHP_SELF']) == 'notifications_count.php') {
    echo $unread_count;
    exit;
}
?>