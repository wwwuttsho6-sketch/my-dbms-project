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
    echo "<div class='alert alert-danger my-5 text-center'><h4>Error: Item records could not be matched inside our database.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<div class="row my-4">
    <div class="col-md-6 mb-4">
        <?php if(!empty($item['image_path'])): ?>
            <img src="../uploads/lost_items/<?php echo htmlspecialchars($item['image_path']); ?>" class="img-fluid rounded shadow w-100" style="max-height: 450px; object-fit: cover;" alt="Lost item image">
        <?php else: ?>
            <div class="bg-secondary text-white rounded shadow d-flex align-items-center justify-content-center w-100" style="height: 350px;">
                <h4>No Image Asset Available</h4>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100 p-4">
            <span class="badge bg-danger mb-2 align-self-start py-2 px-3"><?php echo htmlspecialchars($item['category']); ?></span>
            <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($item['title']); ?></h2>
            
            <table class="table table-striped my-3">
                <tr>
                    <td style="width: 35%;"><strong>📍 Lost Location:</strong></td>
                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                </tr>
                <tr>
                    <td><strong>📅 Date Lost:</strong></td>
                    <td><?php echo htmlspecialchars($item['lost_date']); ?></td>
                </tr>
                <tr>
                    <td><strong>👤 Posted By:</strong></td>
                    <td><?php echo htmlspecialchars($item['owner_name']); ?> (<?php echo htmlspecialchars($item['department']); ?>)</td>
                </tr>
                <tr>
                    <td><strong>💼 Current Status:</strong></td>
                    <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($item['status']); ?></span></td>
                </tr>
            </table>

            <h5 class="fw-bold mt-4">Description</h5>
            <p class="text-muted bg-light p-3 rounded" style="white-space: pre-line;">
                <?php echo htmlspecialchars($item['description']); ?>
            </p>

            <div class="mt-auto pt-4 border-top">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_id'] == $item['user_id']): ?>
                        <div class="alert alert-info text-center">You published this post. Track submissions in your dashboard.</div>
                    <?php else: ?>
                        <a href="../dashboard/verification_form.php?type=lost&item_id=<?php echo $item['id']; ?>" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">
                            🤝 I Found It!
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary btn-lg w-100">Login to Connect with Owner</a>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-link w-100 text-center text-muted mt-2">Return to List</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>