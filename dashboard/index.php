<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Restrict access to logged‑in users only
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- FETCH USER'S OWN POSTS ----------
$lost_stmt = $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$lost_stmt->execute([$user_id]);
$my_lost_posts = $lost_stmt->fetchAll();

$found_stmt = $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$found_stmt->execute([$user_id]);
$my_found_posts = $found_stmt->fetchAll();

// ---------- FETCH INCOMING CLAIMS ----------
$claim_stmt = $pdo->prepare("
    SELECT c.*, 
           COALESCE(l.title, f.title) as item_title,
           COALESCE(l.category, f.category) as item_category,
           u.name as claimant_name,
           c.item_type
    FROM claims c
    JOIN users u ON c.claimant_id = u.id
    LEFT JOIN lost_items l ON c.item_type = 'lost' AND c.item_id = l.id
    LEFT JOIN found_items f ON c.item_type = 'found' AND c.item_id = f.id
    WHERE (l.user_id = ? OR f.user_id = ?) 
      AND c.status = 'Pending'
    ORDER BY c.created_at DESC
");
$claim_stmt->execute([$user_id, $user_id]);
$incoming_claims = $claim_stmt->fetchAll();

// ---------- FETCH USER'S NOTIFICATIONS ----------
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

// Handle messages
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<div class="mb-4">
    <div class="hero-banner p-4 rounded-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge badge-admin mb-2"><i class="bi bi-person-badge"></i> STUDENT DASHBOARD</span>
                <h2 class="font-heading text-white mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p class="text-muted small mb-0">Manage your lost/found items, review verification claims, and monitor notifications.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../lost/create.php" class="btn btn-danger-custom btn-sm py-2 px-3"><i class="bi bi-plus-circle me-1"></i> Report Lost</a>
                <a href="../found/create.php" class="btn btn-success-custom btn-sm py-2 px-3"><i class="bi bi-plus-circle me-1"></i> Report Found</a>
            </div>
        </div>
    </div>
</div>

<!-- Display messages -->
<?php if ($success_msg): ?>
    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left column: Claims & Notifications -->
    <div class="col-lg-7">
        <!-- Incoming Claims -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="font-heading text-white mb-0"><i class="bi bi-envelope-paper me-2 text-warning"></i> Incoming Claims</h5>
                    <span class="badge badge-admin"><?php echo count($incoming_claims); ?> Pending</span>
                </div>

                <?php if (count($incoming_claims) > 0): ?>
                    <div class="list-group list-group-flush border-0">
                        <?php foreach ($incoming_claims as $claim): ?>
                            <a href="review_claim.php?id=<?php echo $claim['id']; ?>" class="list-group-item bg-dark text-white border-secondary border-opacity-25 rounded-3 mb-2 p-3 text-decoration-none hover-glow">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-heading text-white mb-1"><?php echo htmlspecialchars($claim['item_title']); ?></h6>
                                        <span class="badge badge-category"><?php echo htmlspecialchars($claim['item_category']); ?></span>
                                        <span class="small text-muted ms-2"><i class="bi bi-person me-1"></i>Claimant: <?php echo htmlspecialchars($claim['claimant_name']); ?></span>
                                    </div>
                                    <span class="btn btn-brand btn-sm">Review &rarr;</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No pending verification claims for your items.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card card-custom">
            <div class="card-body p-4">
                <h5 class="font-heading text-white mb-3"><i class="bi bi-bell me-2 text-info"></i> Recent System Notifications</h5>

                <?php if (count($notifications) > 0): ?>
                    <div class="list-group list-group-flush border-0">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="list-group-item bg-dark text-white border-secondary border-opacity-25 rounded-3 mb-2 p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small"><?php echo htmlspecialchars($notif['message']); ?></span>
                                    <span class="small text-muted float-end ms-2"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4 mb-0 small"><i class="bi bi-bell-slash fs-3 d-block mb-1"></i>No notifications yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right column: My Activity Tabs -->
    <div class="col-lg-5">
        <div class="card card-custom">
            <div class="card-body p-4">
                <h5 class="font-heading text-white mb-3"><i class="bi bi-collection me-2 text-primary"></i> My Published Posts</h5>
                
                <ul class="nav nav-pills nav-pills-custom mb-3" id="postTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="lost-tab" data-bs-toggle="tab" data-bs-target="#lost" type="button" role="tab">Lost Items (<?php echo count($my_lost_posts); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="found-tab" data-bs-toggle="tab" data-bs-target="#found" type="button" role="tab">Found Items (<?php echo count($my_found_posts); ?>)</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Lost Posts -->
                    <div class="tab-pane fade show active" id="lost" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_lost_posts as $post): ?>
                                        <tr>
                                            <td class="fw-semibold small"><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td>
                                                <?php if($post['status'] === 'Recovered'): ?>
                                                    <span class="badge badge-found">Recovered</span>
                                                <?php else: ?>
                                                    <span class="badge badge-lost">Lost</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../lost/detail.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-custom p-1 px-2" title="View"><i class="bi bi-eye"></i></a>
                                                    <a href="../lost/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-warning p-1 px-2" title="Edit"><i class="bi bi-pencil"></i></a>
                                                    <a href="../lost/delete.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this lost post?')" class="btn btn-danger-custom p-1 px-2" title="Delete"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($my_lost_posts) == 0): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3 small">No lost posts created yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Found Posts -->
                    <div class="tab-pane fade" id="found" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_found_posts as $post): ?>
                                        <tr>
                                            <td class="fw-semibold small"><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td>
                                                <?php if($post['status'] === 'Returned'): ?>
                                                    <span class="badge badge-found">Returned</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Found</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../found/detail.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-custom p-1 px-2" title="View"><i class="bi bi-eye"></i></a>
                                                    <a href="../found/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-warning p-1 px-2" title="Edit"><i class="bi bi-pencil"></i></a>
                                                    <a href="../found/delete.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this found post?')" class="btn btn-danger-custom p-1 px-2" title="Delete"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($my_found_posts) == 0): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3 small">No found posts created yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>