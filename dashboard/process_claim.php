<?php
require_once '../config/db.php';

// Enforce login restriction
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claim_id = isset($_POST['claim_id']) ? intval($_POST['claim_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($claim_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error_msg'] = "Invalid claim action or missing record identifier.";
        header("Location: index.php");
        exit;
    }

    // Fetch claim details and verify if the logged-in user is the actual creator of the item post
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as claimant_name,
               COALESCE(l.title, f.title) as item_title,
               COALESCE(l.user_id, f.user_id) as post_owner_id
        FROM claims c
        JOIN users u ON c.claimant_id = u.id
        LEFT JOIN lost_items l ON c.item_type = 'lost' AND c.item_id = l.id
        LEFT JOIN found_items f ON c.item_type = 'found' AND c.item_id = f.id
        WHERE c.id = ?
    ");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();

    // Security Boundary: Ensure claim exists and belongs to an item posted by the current session user
    if (!$claim || $claim['post_owner_id'] != $user_id) {
        $_SESSION['error_msg'] = "Unauthorized access attempt.";
        header("Location: index.php");
        exit;
    }

    try {
        // Begin transaction to ensure database consistency
        $pdo->beginTransaction();

        if ($action === 'approve') {
            // 1. Update claim status to Approved
            $update_claim = $pdo->prepare("UPDATE claims SET status = 'Approved' WHERE id = ?");
            $update_claim->execute([$claim_id]);

            // 2. Toggle the underlying item listing status to close it out
            if ($claim['item_type'] === 'lost') {
                $update_item = $pdo->prepare("UPDATE lost_items SET status = 'Recovered' WHERE id = ?");
            } else {
                $update_item = $pdo->prepare("UPDATE found_items SET status = 'Returned' WHERE id = ?");
            }
            $update_item->execute([$claim['item_id']]);

            // 3. Reject all OTHER pending claims for this specific item automatically
            $reject_others = $pdo->prepare("UPDATE claims SET status = 'Rejected' WHERE item_type = ? AND item_id = ? AND id != ? AND status = 'Pending'");
            $reject_others->execute([$claim['item_type'], $claim['item_id'], $claim_id]);

            // 4. Push confirmation alert back to the Claimant's dashboard profile
            $notif_msg = "Great news! Your verification request for '" . $claim['item_title'] . "' was APPROVED. Please contact the post creator.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

            $_SESSION['success_msg'] = "Claim successfully approved! This item has been marked resolved.";

        } else if ($action === 'reject') {
            // 1. Update claim status to Rejected
            $update_claim = $pdo->prepare("UPDATE claims SET status = 'Rejected' WHERE id = ?");
            $update_claim->execute([$claim_id]);

            // 2. Notify the claimant about the manual rejection status update
            $notif_msg = "The verification details you submitted for '" . $claim['item_title'] . "' did not match the physical asset specifications and was rejected.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

            $_SESSION['success_msg'] = "Claim request has been successfully declined.";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "An error occurred during workflow processing: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
} else {
    // Redirect if hit via direct URL address query
    header("Location: index.php");
    exit;
}