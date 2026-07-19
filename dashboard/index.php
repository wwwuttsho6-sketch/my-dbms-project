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
// Lost items posted by this user
$lost_stmt = $pdo->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
$lost_stmt->execute([$user_id]);
$my_lost_posts = $lost_stmt->fetchAll();

// Found items posted by this user
$found_stmt = $pdo->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
$found_stmt->execute([$user_id]);
$my_found_posts = $found_stmt->fetchAll();

// ---------- FETCH INCOMING CLAIMS (for items the user owns) ----------
// Claims on lost items owned by this user
$incoming_claims = [];
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
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

// Handle success/error messages from redirects
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<div class="container mt-4">
    <!-- Display flash messages -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($success_msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error_msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row">
        <!-- Left column: Incoming Claims & Notifications -->
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📩 Incoming Verification Claims</h5>
                    <span class="badge bg-light text-dark"><?php echo count($incoming_claims); ?> pending</span>
                </div>
                <div class="card-body p-0">
                    <?php if (count($incoming_claims) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($incoming_claims as $claim): ?>
                                <a href="review_claim.php?id=<?php echo $claim['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($claim['item_title']); ?></strong>
                                        <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($claim['item_category']); ?></span>
                                        <br>
                                        <small class="text-muted">Claimant: <?php echo htmlspecialchars($claim['claimant_name']); ?></small>
                                    </div>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center p-3 mb-0">No pending claims for your items.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">🔔 Recent Notifications</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($notifications) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <li class="list-group-item <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                    <span class="badge bg-light text-muted float-end"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center p-3 mb-0">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right column: User's own posts (tabs) -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📋 My Activity</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="postTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="lost-tab" data-bs-toggle="tab" data-bs-target="#lost" type="button" role="tab">Lost</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="found-tab" data-bs-toggle="tab" data-bs-target="#found" type="button" role="tab">Found</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3">
                        <!-- Lost Posts Tab -->
                        <div class="tab-pane fade show active" id="lost" role="tabpanel">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_lost_posts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td><?php echo $post['lost_date']; ?></td>
                                            <td><span class="badge bg-danger"><?php echo $post['status']; ?></span></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="../lost/detail.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary py-0 px-2">View</a>
                                                    <a href="../lost/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-warning py-0 px-2">Edit</a>
                                                    <a href="../lost/delete.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this lost post?')" class="btn btn-outline-danger py-0 px-2">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($my_lost_posts) == 0): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-2">No lost posts yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <a href="../lost/create.php" class="btn btn-danger btn-sm w-100">+ Report Lost</a>
                        </div>

                        <!-- Found Posts Tab -->
                        <div class="tab-pane fade" id="found" role="tabpanel">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_found_posts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td><?php echo $post['found_date']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $post['status']; ?></span></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="../found/detail.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary py-0 px-2">View</a>
                                                    <a href="../found/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-warning py-0 px-2">Edit</a>
                                                    <a href="../found/delete.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this found post?')" class="btn btn-outline-danger py-0 px-2">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($my_found_posts) == 0): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-2">No found posts yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <a href="../found/create.php" class="btn btn-success btn-sm w-100">+ Report Found</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>