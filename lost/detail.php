<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT lost_items.*, users.name as owner_name, users.department, users.email 
                       FROM lost_items 
                       JOIN users ON lost_items.user_id = users.id 
                       WHERE lost_items.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo "<div class='alert alert-danger my-5 text-center bg-danger text-white border-0 rounded-3'><h4>Error: Item record not found in database.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}

$img = $item['image_path'];
if(!empty($img) && strpos($img, 'http') === false && strpos($img, 'uploads/') !== 0) {
    $img = 'uploads/lost_items/' . $img;
}
?>

<div class="row my-4 gy-4">
    <div class="col-md-6">
        <div class="card card-custom h-100 p-2">
            <?php if(!empty($img)): ?>
                <img src="<?php echo $base_url . $img; ?>" class="img-fluid rounded previewable-image w-100" style="max-height: 480px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['title']); ?>">
            <?php else: ?>
                <div class="no-image-placeholder rounded" style="height: 380px;">
                    <i class="bi bi-image fs-1 opacity-50"></i>
                    <h5 class="mt-2 text-muted font-heading">No Image Asset Attached</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card card-custom h-100 p-4 d-flex flex-column">
            <div>
                <span class="badge badge-custom badge-lost mb-3 align-self-start py-2 px-3">
                    <i class="bi bi-exclamation-circle me-1"></i> LOST ITEM
                </span>
                <h2 class="font-heading text-white mb-3"><?php echo htmlspecialchars($item['title']); ?></h2>
            </div>
            
            <table class="table table-custom my-3">
                <tr>
                    <td style="width: 35%;" class="text-muted"><i class="bi bi-geo-alt me-1 text-danger"></i> Lost Location:</td>
                    <td class="fw-semibold text-white"><?php echo htmlspecialchars($item['location']); ?></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="bi bi-calendar3 me-1 text-info"></i> Date Lost:</td>
                    <td class="fw-semibold text-white"><?php echo htmlspecialchars($item['lost_date']); ?></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="bi bi-person me-1 text-primary"></i> Posted By:</td>
                    <td class="fw-semibold text-white"><?php echo htmlspecialchars($item['owner_name']); ?> (<?php echo htmlspecialchars($item['department']); ?>)</td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="bi bi-info-circle me-1 text-warning"></i> Current Status:</td>
                    <td>
                        <?php if($item['status'] === 'Recovered'): ?>
                            <span class="badge badge-found">RECOVERED</span>
                        <?php else: ?>
                            <span class="badge badge-lost">LOST</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h5 class="font-heading text-white mt-3 mb-2"><i class="bi bi-card-text me-1 text-primary"></i> Detailed Description</h5>
            <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 text-light small mb-4" style="white-space: pre-line;">
                <?php echo htmlspecialchars($item['description']); ?>
            </div>

            <div class="mt-auto pt-4 border-top border-secondary border-opacity-25">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_id'] == $item['user_id']): ?>
                        <div class="alert alert-info bg-dark border-primary text-info text-center rounded-3">
                            <i class="bi bi-info-circle me-1"></i> You published this post. Check responses inside your dashboard.
                        </div>
                    <?php else: ?>
                        <a href="../dashboard/verification_form.php?type=lost&item_id=<?php echo $item['id']; ?>" class="btn btn-success-custom btn-lg w-100 py-3 font-heading shadow">
                            <i class="bi bi-check2-circle me-2"></i> I Found This Item! Submit Verification
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-brand btn-lg w-100 py-3 font-heading">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Contact Owner
                    </a>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-link w-100 text-center text-muted text-decoration-none mt-2 small">
                    &larr; Back to Lost Feed
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>