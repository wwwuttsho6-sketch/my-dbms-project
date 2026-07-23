<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Enforce login restriction
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch existing lost post details
$stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

// Security check
if (!$item || ($item['user_id'] != $user_id && ($_SESSION['role'] ?? '') !== 'admin')) {
    echo "<div class='alert alert-danger my-5 text-center bg-danger text-white border-0 rounded-3'><h4>Unauthorized access or record not found.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $lost_date = $_POST['lost_date'];
    $status = $_POST['status'];
    
    $image_name = $item['image_path'];

    // Ensure target dir exists
    $target_dir = "../uploads/lost_items/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['item_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            
            if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $target_dir . $image_name)) {
                $error = "Failed to store image in uploads folder.";
            } else {
                if (!empty($item['image_path'])) {
                    $old_file = $target_dir . $item['image_path'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
        } else {
            $error = "Invalid format. Only JPG, PNG, and GIF allowed.";
        }
    }

    if (empty($error)) {
        if (!empty($title) && !empty($category) && !empty($description) && !empty($location) && !empty($lost_date)) {
            $update = $pdo->prepare("UPDATE lost_items SET title = ?, category = ?, description = ?, location = ?, lost_date = ?, image_path = ?, status = ? WHERE id = ?");
            if ($update->execute([$title, $category, $description, $location, $lost_date, $image_name, $status, $id])) {
                $success = "Lost item post updated successfully!";
                $item['title'] = $title;
                $item['category'] = $category;
                $item['description'] = $description;
                $item['location'] = $location;
                $item['lost_date'] = $lost_date;
                $item['image_path'] = $image_name;
                $item['status'] = $status;
            } else {
                $error = "Database modification failed.";
            }
        } else {
            $error = "All mandatory fields must be completed.";
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="fs-1">✏️</span>
                    <h3 class="font-heading mt-2">Edit Lost Item Post</h3>
                    <p class="text-muted small">Update your lost post details or status.</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?> <a href="../dashboard/index.php" class="text-white fw-bold text-decoration-underline ms-2">Return to Dashboard</a></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tag me-1"></i> Item Title / Name</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-grid me-1"></i> Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Mobile Phones" <?php echo $item['category'] == 'Mobile Phones' ? 'selected' : ''; ?>>Mobile Phones</option>
                            <option value="Wallets" <?php echo $item['category'] == 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
                            <option value="Laptops" <?php echo $item['category'] == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                            <option value="ID Cards" <?php echo $item['category'] == 'ID Cards' ? 'selected' : ''; ?>>ID Cards</option>
                            <option value="Other Items" <?php echo $item['category'] == 'Other Items' ? 'selected' : ''; ?>>Other Items</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-card-text me-1"></i> Item Description</label>
                        <textarea name="description" rows="4" class="form-control" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-geo-alt me-1"></i> Location Lost</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($item['location']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-calendar3 me-1"></i> Date Lost</label>
                            <input type="date" name="lost_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $item['lost_date']; ?>" required>
                        </div>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-info-circle me-1"></i> Listing Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Lost" <?php echo $item['status'] == 'Lost' ? 'selected' : ''; ?>>Lost (Active)</option>
                                <option value="Recovered" <?php echo $item['status'] == 'Recovered' ? 'selected' : ''; ?>>Recovered (Resolved)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-image me-1"></i> Replace Image (Optional)</label>
                            <input type="file" name="item_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25">
                        <a href="../dashboard/index.php" class="btn btn-secondary-custom">&larr; Cancel</a>
                        <button type="submit" class="btn btn-brand px-4 py-2"><i class="bi bi-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>