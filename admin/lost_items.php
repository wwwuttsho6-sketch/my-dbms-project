<?php
require_once 'includes/admin_header.php';

$message = '';
$error = '';

// Delete lost post action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_item') {
    $item_id = intval($_POST['item_id']);
    $stmt = $pdo->prepare("DELETE FROM lost_items WHERE id = ?");
    if ($stmt->execute([$item_id])) {
        $message = "Lost item post #" . $item_id . " permanently deleted.";
    } else {
        $error = "Failed to delete item post.";
    }
}

// Toggle status action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $item_id = intval($_POST['item_id']);
    $new_status = $_POST['new_status'] === 'Recovered' ? 'Recovered' : 'Lost';
    $stmt = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $item_id]);
    $message = "Item #" . $item_id . " status updated to " . $new_status . ".";
}

// Fetch all lost items
$stmt = $pdo->query("
    SELECT l.*, u.name as poster_name, u.email as poster_email
    FROM lost_items l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading mb-1">🔴 Lost Items Management</h2>
        <p class="text-muted mb-0">Review, moderate, or update status of all reported lost items.</p>
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
                        <th class="ps-4">ID</th>
                        <th>Item Details</th>
                        <th>Category & Location</th>
                        <th>Posted By</th>
                        <th>Lost Date</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#<?php echo $item['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if(!empty($item['image_path'])): ?>
                                    <img src="<?php echo $base_url . $item['image_path']; ?>" class="rounded previewable-image" style="width: 50px; height: 50px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold text-white"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 220px;"><?php echo htmlspecialchars($item['description']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <div class="small text-muted mt-1"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($item['location']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($item['poster_name']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($item['poster_email']); ?></div>
                        </td>
                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($item['lost_date'])); ?></td>
                        <td>
                            <?php if($item['status'] === 'Recovered'): ?>
                                <span class="badge badge-found">RECOVERED</span>
                            <?php else: ?>
                                <span class="badge badge-lost">LOST</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-2">
                                <form action="lost_items.php" method="POST">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $item['status'] === 'Recovered' ? 'Lost' : 'Recovered'; ?>">
                                    <button type="submit" class="btn btn-outline-custom btn-sm">
                                        Mark as <?php echo $item['status'] === 'Recovered' ? 'Lost' : 'Recovered'; ?>
                                    </button>
                                </form>

                                <a href="<?php echo $base_url; ?>lost/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-secondary-custom btn-sm" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <form action="lost_items.php" method="POST" onsubmit="return confirm('Permanently delete this lost item post?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger-custom btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($items)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No lost items posted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
