<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$claim_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch claim details, claimant details, and verify if logged-in user is item owner
$stmt = $pdo->prepare("
    SELECT c.*, u.name as claimant_name, u.email as claimant_email, u.phone as claimant_phone, u.department as claimant_dept, u.university_id as claimant_uni_id,
           COALESCE(l.title, f.title) as item_title,
           COALESCE(l.user_id, f.user_id) as post_owner_id,
           COALESCE(l.category, f.category) as item_category
    FROM claims c
    JOIN users u ON c.claimant_id = u.id
    LEFT JOIN lost_items l ON c.item_type = 'lost' AND c.item_id = l.id
    LEFT JOIN found_items f ON c.item_type = 'found' AND c.item_id = f.id
    WHERE c.id = ?
");
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

// Security check
if (!$claim || $claim['post_owner_id'] != $user_id) {
    echo "<div class='alert alert-danger my-5 text-center bg-danger text-white border-0 rounded-3'><h4>Unauthorized access or invalid claim record.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}

$verification_fields = json_decode($claim['verification_data'], true);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $update_claim = $pdo->prepare("UPDATE claims SET status = 'Approved' WHERE id = ?");
        $update_claim->execute([$claim_id]);

        if ($claim['item_type'] === 'lost') {
            $update_item = $pdo->prepare("UPDATE lost_items SET status = 'Recovered' WHERE id = ?");
        } else {
            $update_item = $pdo->prepare("UPDATE found_items SET status = 'Returned' WHERE id = ?");
        }
        $update_item->execute([$claim['item_id']]);

        $notif_msg = "Great news! Your verification request for '" . $claim['item_title'] . "' was APPROVED. Please contact the post owner.";
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

        $message = "<div class='alert alert-success bg-success text-white border-0 rounded-3 mb-4'><i class='bi bi-check-circle-fill me-2'></i>Claim approved! Notification sent to claimant.</div>";
        $claim['status'] = 'Approved';
        
    } else if ($action === 'reject') {
        $update_claim = $pdo->prepare("UPDATE claims SET status = 'Rejected' WHERE id = ?");
        $update_claim->execute([$claim_id]);

        $notif_msg = "Verification details for '" . $claim['item_title'] . "' did not match item specifications and was rejected.";
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

        $message = "<div class='alert alert-warning bg-warning text-dark border-0 rounded-3 mb-4'><i class='bi bi-exclamation-triangle-fill me-2'></i>Claim rejected. Notification sent to claimant.</div>";
        $claim['status'] = 'Rejected';
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <?php echo $message; ?>
        
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                    <div>
                        <h4 class="font-heading text-white mb-1">Review Claim Submission</h4>
                        <p class="text-muted small mb-0">Item: <strong class="text-info"><?php echo htmlspecialchars($claim['item_title']); ?></strong></p>
                    </div>
                    <?php if($claim['status'] === 'Approved'): ?>
                        <span class="badge badge-found py-2 px-3">APPROVED</span>
                    <?php elseif($claim['status'] === 'Rejected'): ?>
                        <span class="badge badge-lost py-2 px-3">REJECTED</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark py-2 px-3">PENDING</span>
                    <?php endif; ?>
                </div>
                
                <!-- Section 1: Claimant Profile -->
                <h5 class="font-heading text-primary mb-3"><i class="bi bi-person-circle me-2"></i> Claimant Account Information</h5>
                <div class="row bg-dark p-3 rounded-3 mb-4 border border-secondary border-opacity-25 g-2">
                    <div class="col-md-6"><strong class="text-muted">Full Name:</strong> <span class="text-white"><?php echo htmlspecialchars($claim['claimant_name']); ?></span></div>
                    <div class="col-md-6"><strong class="text-muted">Campus ID:</strong> <span class="text-white"><?php echo htmlspecialchars($claim['claimant_uni_id']); ?></span></div>
                    <div class="col-md-6"><strong class="text-muted">Department:</strong> <span class="text-white"><?php echo htmlspecialchars($claim['claimant_dept']); ?></span></div>
                    <div class="col-md-6"><strong class="text-muted">Contact Phone:</strong> <span class="text-info"><?php echo htmlspecialchars($claim['claimant_phone']); ?></span></div>
                    <div class="col-md-12"><strong class="text-muted">Campus Email:</strong> <span class="text-white"><?php echo htmlspecialchars($claim['claimant_email']); ?></span></div>
                </div>

                <!-- Section 2: Form Submissions Details -->
                <h5 class="font-heading text-primary mb-3"><i class="bi bi-card-checklist me-2"></i> Provided Item Criteria Responses</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Verification Question Field</th>
                                <th>Claimant Input Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(is_array($verification_fields)): ?>
                                <?php foreach($verification_fields as $field_label => $user_value): ?>
                                    <tr>
                                        <td class="fw-semibold text-info"><?php echo htmlspecialchars($field_label); ?></td>
                                        <td class="text-white"><?php echo htmlspecialchars(is_array($user_value) ? implode(', ', $user_value) : $user_value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Error reading form data.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Actions Controls Wrapper -->
                <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <a href="index.php" class="btn btn-secondary-custom">&larr; Back to Dashboard</a>
                    
                    <?php if($claim['status'] === 'Pending'): ?>
                        <div class="d-flex gap-2">
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" onclick="return confirm('Decline this verification claim?')" class="btn btn-danger-custom">
                                    <i class="bi bi-x-lg me-1"></i> Reject Claim
                                </button>
                            </form>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" onclick="return confirm('Approve ownership claim? Item will be marked as recovered.')" class="btn btn-success-custom">
                                    <i class="bi bi-check-lg me-1"></i> Approve Claim
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span class="badge badge-category py-2 px-3">Workflow Completed</span>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>