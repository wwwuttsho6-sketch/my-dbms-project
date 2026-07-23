<?php
require_once 'includes/admin_header.php';

$message = '';
$error = '';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $claim_id = intval($_POST['claim_id']);
    $new_status = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';

    $stmt = $pdo->prepare("SELECT * FROM claims WHERE id = ?");
    $stmt->execute([$claim_id]);
    $claim = $stmt->fetch();

    if ($claim) {
        $update = $pdo->prepare("UPDATE claims SET status = ? WHERE id = ?");
        $update->execute([$new_status, $claim_id]);

        // Send notification to claimant
        $notif_msg = "Admin Review Update: Your claim for item (" . strtoupper($claim['item_type']) . " #" . $claim['item_id'] . ") has been " . strtoupper($new_status) . ".";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif->execute([$claim['claimant_id'], $notif_msg]);

        // If approved, update item status
        if ($new_status === 'Approved') {
            if ($claim['item_type'] === 'lost') {
                $pdo->prepare("UPDATE lost_items SET status = 'Recovered' WHERE id = ?")->execute([$claim['item_id']]);
            } else {
                $pdo->prepare("UPDATE found_items SET status = 'Returned' WHERE id = ?")->execute([$claim['item_id']]);
            }
        }

        $message = "Claim #" . $claim_id . " marked as " . $new_status . " and notification sent to claimant.";
    } else {
        $error = "Claim not found.";
    }
}

// Fetch all claims with user details
$stmt = $pdo->query("
    SELECT c.*, u.name as claimant_name, u.email as claimant_email, u.phone as claimant_phone, u.department as claimant_dept
    FROM claims c
    JOIN users u ON c.claimant_id = u.id
    ORDER BY c.created_at DESC
");
$claims = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading mb-1">📋 Verification Claims Governance</h2>
        <p class="text-muted mb-0">Review claimant dynamic questionnaire submissions and authorize item ownership.</p>
    </div>
</div>

<?php if(!empty($message)): ?>
    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Claim ID</th>
                        <th>Claimant Info</th>
                        <th>Target Item</th>
                        <th>Verification Answers</th>
                        <th>Submitted Date</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Governance Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($claims as $c): ?>
                    <?php 
                        $vData = json_decode($c['verification_data'], true);
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#<?php echo $c['id']; ?></td>
                        <td>
                            <div class="fw-semibold text-white"><?php echo htmlspecialchars($c['claimant_name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($c['claimant_email']); ?> | <?php echo htmlspecialchars($c['claimant_phone']); ?></div>
                            <div class="small text-info"><?php echo htmlspecialchars($c['claimant_dept']); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-category"><?php echo strtoupper($c['item_type']); ?> ITEM #<?php echo $c['item_id']; ?></span>
                        </td>
                        <td style="max-width: 300px;">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill" data-bs-toggle="collapse" data-bs-target="#claimDetails<?php echo $c['id']; ?>">
                                <i class="bi bi-eye me-1"></i> View Answers (<?php echo is_array($vData) ? count($vData) : 0; ?> fields)
                            </button>
                            <div class="collapse mt-2" id="claimDetails<?php echo $c['id']; ?>">
                                <div class="p-2 bg-dark rounded text-start small border border-secondary border-opacity-25">
                                    <?php if(is_array($vData)): ?>
                                        <?php foreach($vData as $key => $val): ?>
                                            <div><strong class="text-info"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars(is_array($val) ? implode(', ', $val) : $val); ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted">Raw Data: <?php echo htmlspecialchars($c['verification_data']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></td>
                        <td>
                            <?php if ($c['status'] == 'Approved'): ?>
                                <span class="badge badge-found"><i class="bi bi-check-circle me-1"></i> APPROVED</span>
                            <?php elseif ($c['status'] == 'Rejected'): ?>
                                <span class="badge badge-lost"><i class="bi bi-x-circle me-1"></i> REJECTED</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <?php if ($c['status'] == 'Pending'): ?>
                                <div class="d-inline-flex gap-2">
                                    <form action="claims.php" method="POST">
                                        <input type="hidden" name="claim_id" value="<?php echo $c['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success-custom btn-sm">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form action="claims.php" method="POST">
                                        <input type="hidden" name="claim_id" value="<?php echo $c['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger-custom btn-sm">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="small text-muted">Action Taken</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($claims)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No claims submitted for review.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
