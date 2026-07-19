<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'lost' or 'found'
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$claimant_id = $_SESSION['user_id'];

// Verify the target item exists and determine its category
$category = '';
$item_title = '';
$target_user_id = 0;

if ($type === 'lost') {
    $stmt = $pdo->prepare("SELECT title, category, user_id FROM lost_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    if ($item) {
        $category = $item['category'];
        $item_title = $item['title'];
        $target_user_id = $item['user_id'];
    }
} else if ($type === 'found') {
    $stmt = $pdo->prepare("SELECT title, category, user_id FROM found_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    if ($item) {
        $category = $item['category'];
        $item_title = $item['title'];
        $target_user_id = $item['user_id'];
    }
}

if (empty($category)) {
    echo "<div class='alert alert-danger my-5 text-center'><h4>Error: Item records could not be matched.</h4></div>";
    require_once '../includes/footer.php';
    exit;
}

// Fetch claimant's profile details to pre-populate common fields
$user_stmt = $pdo->prepare("SELECT department, university_id, phone FROM users WHERE id = ?");
$user_stmt->execute([$claimant_id]);
$user_profile = $user_stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect specific fields dynamically into an array based on category inputs
    $form_responses = [];
    foreach ($_POST['verify'] as $key => $value) {
        $form_responses[$key] = trim($value);
    }
    
    // Convert form answers to JSON text string
    $json_data = json_encode($form_responses, JSON_UNESCAPED_UNICODE);

    if (!empty($json_data)) {
        // Insert standard claim profile row
        $stmt = $pdo->prepare("INSERT INTO claims (item_type, item_id, claimant_id, verification_data) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$type, $item_id, $claimant_id, $json_data])) {
            
            // Generate notification tracking update text alert for the target user
            $notif_msg = "Someone filed a request/claim on your posted item: '" . $item_title . "'. Check your dashboard to review details.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->execute([$target_user_id, $notif_msg]);
            
            $success = "Verification form submitted successfully! The finder/owner has been notified.";
        } else {
            $error = "Failed to submit verification claims workflow details.";
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Manual Verification Questionnaire</h4>
                <small class="text-light">Item: <?php echo htmlspecialchars($item_title); ?> (Category: <?php echo htmlspecialchars($category); ?>)</small>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?>. <a href="index.php" class="alert-link">Go to Dashboard</a></div>
                <?php else: ?>

                <form action="" method="POST">
                    <!-- Universal Information Fields -->
                    <h5 class="text-primary border-bottom pb-2 mb-3">1. Identity Verification</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">University ID Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_profile['university_id']); ?>" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_profile['department']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_profile['phone']); ?>" disabled>
                        </div>
                    </div>

                    <!-- Category-Specific Dynamic Form Custom Inputs -->
                    <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">2. Item Specific Details</h5>
                    
                    <?php if($category === 'Mobile Phones'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Phone Brand</label><input type="text" name="verify[Brand]" class="form-control" placeholder="e.g., Apple, Samsung" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Model Name / Number</label><input type="text" name="verify[Model]" class="form-control" placeholder="e.g., iPhone 11, Galaxy S21" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Body Color</label><input type="text" name="verify[Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Lock Type (PIN/Pattern/Password)</label><input type="text" name="verify[Lock Type]" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Cover/Case Description</label><input type="text" name="verify[Cover Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">SIM Card Network Operator</label><input type="text" name="verify[SIM Operator]" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Lock Screen Wallpaper Description</label><textarea name="verify[Wallpaper]" rows="2" class="form-control" placeholder="Describe the image/photo on the lock screen..." required></textarea></div>

                    <?php elseif($category === 'Wallets'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Wallet Color</label><input type="text" name="verify[Wallet Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Material / Type (e.g., Leather, Fabric)</label><input type="text" name="verify[Wallet Type]" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Approximate Cash Amount Inside</label><input type="text" name="verify[Approximate Amount]" class="form-control" placeholder="e.g., Around 500 Taka" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Important Cards Inside (NID, ATM, etc.)</label><input type="text" name="verify[Important Cards]" class="form-control" placeholder="List any cards inside..." required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Any Inner Photos or Unique Identification Marks</label><textarea name="verify[Unique Marks]" rows="2" class="form-control" placeholder="Mention specific identifiers..." required></textarea></div>

                    <?php elseif($category === 'Laptops'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Laptop Brand / Manufacturer</label><input type="text" name="verify[Brand]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Shell Color</label><input type="text" name="verify[Color]" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Any Distinctive Stickers, Scratches or Marks</label><input type="text" name="verify[Stickers/Marks]" class="form-control" placeholder="Describe stickers or external physical marks..." required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Password Hint (To verify system access ownership)</label><input type="text" name="verify[Password Hint]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Was the Charger Included inside Bag?</label><select name="verify[Charger Included]" class="form-select" required><option value="Yes">Yes</option><option value="No">No</option></select></div>
                        </div>

                    <?php elseif($category === 'ID Cards'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Exact Student Name Printed</label><input type="text" name="verify[Student Name]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Card Department</label><input type="text" name="verify[Department]" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Batch Number</label><input type="text" name="verify[Batch]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Exact Student ID Number</label><input type="text" name="verify[Student ID Number]" class="form-control" required></div>
                        </div>

                    <?php else: // Other Items ?>
                        <div class="mb-3"><label class="form-label">Granular/Additional Unique Identification Description</label><textarea name="verify[Additional Description]" rows="4" class="form-control" placeholder="Provide as much detailed information as possible so the finder can verify your claim safely..." required></textarea></div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                        <button type="button" onclick="history.back()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-dark px-4">Submit Claim Form</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>