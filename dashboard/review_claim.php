<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$claim_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch claim details, claimant details, and verify if the logged-in user is the actual creator of the item post
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

// Security Boundary: Ensure claim exists and belongs to an item posted by the current session user
if (!$claim || $claim['post_owner_id'] != $user_id) {
    echo "<div class='alert alert-danger my-5 text-center'><h4>Unauthorized access or invalid record identifier.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}

// Convert serialized JSON data back into a readable PHP array
$verification_fields = json_decode($claim['verification_data'], true);

$message = '';

// Process Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Update claim status to Approved
        $update_claim = $pdo->prepare("UPDATE claims SET status = 'Approved' WHERE id = ?");
        $update_claim->execute([$claim_id]);

        // Toggle the underlying item listing status to close it out
        if ($claim['item_type'] === 'lost') {
            $update_item = $pdo->prepare("UPDATE lost_items SET status = 'Recovered' WHERE id = ?");
        } else {
            $update_item = $pdo->prepare("UPDATE found_items SET status = 'Returned' WHERE id = ?");
        }
        $update_item->execute([$claim['item_id']]);

        // Push confirmation alert back to the Claimant's dashboard profile
        $notif_msg = "Great news! Your verification request for '" . $claim['item_title'] . "' was APPROVED. Please contact the post creator.";
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

        $message = "<div class='alert alert-success'>Claim successfully approved! An alert notification has been sent to the student.</div>";
        $claim['status'] = 'Approved'; // Update local variable for rendering update state
        
    } else if ($action === 'reject') {
        // Update claim status to Rejected
        $update_claim = $pdo->prepare("UPDATE claims SET status = 'Rejected' WHERE id = ?");
        $update_claim->execute([$claim_id]);

        // Notify the claimant about the manual rejection status update
        $notif_msg = "The verification details you submitted for '" . $claim['item_title'] . "' did not match the physical asset specifications and was rejected.";
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->execute([$claim['claimant_id'], $notif_msg]);

        $message = "<div class='alert alert-warning'>Claim form application has been marked as rejected.</div>";
        $claim['status'] = 'Rejected';
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <?php echo $message; ?>
        
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reviewing Verification Data Submission</h5>
                <span class="badge bg-info text-dark"><?php echo $claim['status']; ?></span>
            </div>
            
            <div class="card-body p-4">
                <!-- Section 1: Claimant Profile -->
                <h5 class="text-primary border-bottom pb-2 mb-3">👤 Claimant Account Identity</h5>
                <div class="row bg-light p-3 rounded mb-4 text-sm">
                    <div class="col-md-6 mb-2"><strong>Full Name:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Campus ID:</strong> <?php echo htmlspecialchars($claim['claimant_uni_id']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($claim['claimant_dept']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Contact Number:</strong> <?php echo htmlspecialchars($claim['claimant_phone']); ?></div>
                    <div class="col-md-12"><strong>Email Address:</strong> <?php echo htmlspecialchars($claim['claimant_email']); ?></div>
                </div>

                <!-- Section 2: Form Submissions Data Details -->
                <h5 class="text-primary border-bottom pb-2 mb-3">📝 Provided Physical Item Characteristics</h5>
                <table class="table table-bordered mb-4">
                    <thead class="table-secondary text-xs">
                        <tr>
                            <th style="width: 40%;">Verification Criteria Field</th>
                            <th>Claimant Input Value Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(is_array($verification_fields)): ?>
                            <?php foreach($verification_fields as $field_label => $user_value): ?>
                                <tr>
                                    <td class="fw-bold bg-light"><?php echo htmlspecialchars($field_label); ?></td>
                                    <td><?php echo htmlspecialchars($user_value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center text-muted">Error parsing form entries structure.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Actions Controls Wrapper -->
                <div class="pt-3 border-top d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                    
                    <?php if($claim['status'] === 'Pending'): ?>
                        <div>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" onclick="return confirm('Reject this verification form request?')" class="btn btn-danger me-2 px-3">❌ Decline Request</button>
                            </form>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" onclick="return confirm('Are you sure the parameters match perfectly? This will approve ownership rights and close out this post listing.')" class="btn btn-success px-4">✓ Approve & Match</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-light" disabled>Workflow Concluded</button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>