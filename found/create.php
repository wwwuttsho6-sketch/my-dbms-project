<?php
require_once '../config/db.php';
require_once '../includes/header.php';

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
    $found_date = $_POST['found_date'];
    $user_id = $_SESSION['user_id'];
    
    $image_name = null;

    // Handle File Image Upload
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['item_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Uniquely rename the file
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            $target_dir = "../uploads/found_items/";
            
            // Ensure the directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $target_dir . $image_name)) {
                $error = "Failed to upload image file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF allowed.";
        }
    }

    if (empty($error)) {
        if (!empty($title) && !empty($category) && !empty($description) && !empty($location) && !empty($found_date)) {
            $stmt = $pdo->prepare("INSERT INTO found_items (user_id, title, category, description, location, found_date, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $title, $category, $description, $location, $found_date, $image_name])) {
                $success = "Found item post created successfully!";
            } else {
                $error = "Failed to save the record to the database.";
            }
        } else {
            $error = "Please fill in all mandatory inputs.";
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Report a Found Item</h4>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?>. <a href="../dashboard/index.php" class="alert-link">Go to Dashboard</a></div>
                <?php endif; ?>

                <form action="create.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Title / Name</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Black Wallet, MacBook Pro" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Mobile Phones">Mobile Phones</option>
                            <option value="Wallets">Wallets</option>
                            <option value="Laptops">Laptops</option>
                            <option value="ID Cards">ID Cards</option>
                            <option value="Other Items">Other Items</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Describe color, brand, unique features..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Location Found</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Library, Cafeteria" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Date Found</label>
                            <input type="date" name="found_date" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Attach Image (Optional)</label>
                        <input type="file" name="item_image" class="form-control">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Back to Feed</a>
                        <button type="submit" class="btn btn-success px-4">Publish Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>