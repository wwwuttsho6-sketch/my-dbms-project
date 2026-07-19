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

// Fetch the existing lost post details
$stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

// Security Boundary: Ensure post exists and belongs to the current logged-in user
if (!$item || $item['user_id'] != $user_id) {
    echo "<div class='alert alert-danger my-5 text-center'><h4>Unauthorized access or record not found.</h4></div>";
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
    
    $image_name = $item['image_path']; // Keep existing image by default

    // Handle new image upload if provided
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['item_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            $target_dir = "../uploads/lost_items/";
            
            if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $target_dir . $image_name)) {
                $error = "Failed to store image in uploads folder.";
            } else {
                // Optional: Delete old image from server to save space
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
                $success = "Lost item listing updated successfully!";
                // Refresh local data values for form rendering
                $item['title'] = $title;
                $item['category'] = $category;
                $item['description'] = $description;
                $item['location'] = $location;
                $item['lost_date'] = $lost_date;
                $item['image_path'] = $image_name;
                $item['status'] = $status;
            } else {
                $error = "Database modification execution failed.";
            }
        } else {
            $error = "All mandatory fields must be completed.";
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Edit Lost Item Report</h4>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?>. <a href="../dashboard/index.php" class="alert-link">Return to Dashboard</a></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Title / Name</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Mobile Phones" <?php echo $item['category'] == 'Mobile Phones' ? 'selected' : ''; ?>>Mobile Phones</option>
                            <option value="Wallets" <?php echo $item['category'] == 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
                            <option value="Laptops" <?php echo $item['category'] == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                            <option value="ID Cards" <?php echo $item['category'] == 'ID Cards' ? 'selected' : ''; ?>>ID Cards</option>
                            <option value="Other Items" <?php echo $item['category'] == 'Other Items' ? 'selected' : ''; ?>>Other Items</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Description</label>
                        <textarea name="description" rows="4" class="form-control" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Location Lost</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($item['location']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date Lost</label>
                            <input type="date" name="lost_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $item['lost_date']; ?>" required>
                        </div>
                    </div>
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Listing Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Lost" <?php echo $item['status'] == 'Lost' ? 'selected' : ''; ?>>Lost (Active)</option>
                                <option value="Recovered" <?php echo $item['status'] == 'Recovered' ? 'selected' : ''; ?>>Recovered (Resolved)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Update Image Asset (Optional)</label>
                            <input type="file" name="item_image" class="form-control">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="../dashboard/index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger px-4">Update Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>