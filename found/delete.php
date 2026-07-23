<?php
require_once '../config/db.php';

// Enforce login restriction
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    // Check if the record exists and belongs to the user (FOUND items table)
    $stmt = $pdo->prepare("SELECT image_path FROM found_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $item = $stmt->fetch();

    if ($item) {
        // Unlink/Delete the file image asset if it exists locally to free up server storage
        if (!empty($item['image_path'])) {
            $file_path = "../uploads/found_items/" . $item['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete the listing data row from database
        $delete = $pdo->prepare("DELETE FROM found_items WHERE id = ?");
        $delete->execute([$id]);
        
        $_SESSION['success_msg'] = "Found item post record removed successfully.";
    } else {
        $_SESSION['error_msg'] = "Unauthorized operation or invalid row parameter.";
    }
}

header("Location: ../dashboard/index.php");
exit;
?>