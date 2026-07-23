<?php
require_once '../config/db.php';

// Enforce login restriction
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
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
    $user_id = $_SESSION['user_id'];
    
    $image_name = null;

    // Ensure target dir exists
    $target_dir = "../uploads/lost_items/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Handle File Image Uploading Operations
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['item_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Uniquely rename the file to avoid conflicts
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            
            if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $target_dir . $image_name)) {
                $error = "Failed to upload image file destination.";
            }
        } else {
            $error = "Invalid file extension type. Only JPG, PNG, and GIF allowed.";
        }
    }

    if (empty($error)) {
        if (!empty($title) && !empty($category) && !empty($description) && !empty($location) && !empty($lost_date)) {
            $stmt = $pdo->prepare("INSERT INTO lost_items (user_id, title, category, description, location, lost_date, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $title, $category, $description, $location, $lost_date, $image_name])) {
                $success = "Lost item report published successfully!";
            } else {
                $error = "Failed to save record to database.";
            }
        } else {
            $error = "Please fill in all mandatory inputs.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="fs-1">🔴</span>
                    <h3 class="font-heading mt-2">Report a Missing Item</h3>
                    <p class="text-muted small">Provide clear details to help fellow campus members identify your belonging.</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?> 
                        <a href="index.php" class="text-white fw-bold text-decoration-underline ms-2">View in Feed</a>
                    </div>
                <?php endif; ?>

                <form action="create.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tag me-1"></i> Item Title / Name</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Black HP Pavilion Laptop, Blue Leather Wallet" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-grid me-1"></i> Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Item Category</option>
                            <option value="Mobile Phones">Mobile Phones</option>
                            <option value="Wallets">Wallets</option>
                            <option value="Laptops">Laptops</option>
                            <option value="ID Cards">ID Cards</option>
                            <option value="Other Items">Other Items</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-card-text me-1"></i> Detailed Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Describe unique features, stickers, contents (for wallets), brand, scratch marks..." required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-geo-alt me-1"></i> Approx. Lost Location</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Central Library, Cafeteria 2nd floor" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-calendar3 me-1"></i> Date Lost</label>
                            <input type="date" name="lost_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-image me-1"></i> Upload Image Asset (Optional)</label>
                        <input type="file" name="item_image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25">
                        <a href="index.php" class="btn btn-secondary-custom">
                            &larr; Back to Feed
                        </a>
                        <button type="submit" class="btn btn-danger-custom px-4 py-2">
                            <i class="bi bi-send-fill me-1"></i> Publish Lost Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>