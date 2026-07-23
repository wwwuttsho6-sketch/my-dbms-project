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
    echo "<div class='alert alert-danger my-5 text-center bg-danger text-white border-0 rounded-3'><h4>Error: Target item record not found in database.</h4></div>";
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
    $form_responses = [];
    if (isset($_POST['verify']) && is_array($_POST['verify'])) {
        foreach ($_POST['verify'] as $key => $value) {
            $form_responses[$key] = trim($value);
        }
    }
    
    $json_data = json_encode($form_responses, JSON_UNESCAPED_UNICODE);

    if (!empty($json_data)) {
        $stmt = $pdo->prepare("INSERT INTO claims (item_type, item_id, claimant_id, verification_data) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$type, $item_id, $claimant_id, $json_data])) {
            
            $notif_msg = "Someone filed a verification claim on your post: '" . $item_title . "'. Check your dashboard to review.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->execute([$target_user_id, $notif_msg]);
            
            $success = "Verification form submitted successfully! The owner/finder and platform admins have been notified.";
        } else {
            $error = "Failed to submit verification claim.";
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8">
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="fs-1">📋</span>
                    <h3 class="font-heading text-white mt-2">Ownership Verification Questionnaire</h3>
                    <p class="text-muted small">Item: <strong class="text-info"><?php echo htmlspecialchars($item_title); ?></strong> (Category: <?php echo htmlspecialchars($category); ?>)</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?> <a href="index.php" class="text-white fw-bold text-decoration-underline ms-2">Go to Dashboard</a></div>
                <?php else: ?>

                <form action="" method="POST">
                    <!-- Universal Information Fields -->
                    <h5 class="font-heading text-primary border-bottom border-secondary border-opacity-25 pb-2 mb-3"><i class="bi bi-person-badge me-2"></i>1. Identity Credentials</h5>
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

                    <!-- Category-Specific Inputs -->
                    <h5 class="font-heading text-primary border-bottom border-secondary border-opacity-25 pb-2 mb-3 mt-4"><i class="bi bi-shield-check me-2"></i>2. Item Specific Verification Criteria</h5>
                    
                    <?php if($category === 'Mobile Phones'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Phone Brand</label><input type="text" name="verify[Brand]" class="form-control" placeholder="e.g., Apple, Samsung" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Model Name / Number</label><input type="text" name="verify[Model]" class="form-control" placeholder="e.g., iPhone 13, Galaxy S21" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Body Color</label><input type="text" name="verify[Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Lock Type (PIN/Pattern/Password)</label><input type="text" name="verify[Lock Type]" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Cover/Case Description</label><input type="text" name="verify[Cover Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">SIM Network Operator</label><input type="text" name="verify[SIM Operator]" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Lock Screen Wallpaper Description</label><textarea name="verify[Wallpaper]" rows="2" class="form-control" placeholder="Describe the image/photo on lock screen..." required></textarea></div>

                    <?php elseif($category === 'Wallets'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Wallet Color</label><input type="text" name="verify[Wallet Color]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Material / Type (e.g., Leather)</label><input type="text" name="verify[Wallet Type]" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Approximate Cash Amount Inside</label><input type="text" name="verify[Approximate Amount]" class="form-control" placeholder="e.g., Around 500 Taka" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Important Cards Inside (NID, ATM)</label><input type="text" name="verify[Important Cards]" class="form-control" placeholder="List cards inside..." required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Unique Inner Identification Marks</label><textarea name="verify[Unique Marks]" rows="2" class="form-control" placeholder="Mention specific identifiers..." required></textarea></div>

                    <?php elseif($category === 'Laptops'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Laptop Brand / Manufacturer</label><input type="text" name="verify[Brand]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Shell Color</label><input type="text" name="verify[Color]" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Distinctive Stickers / Scratches</label><input type="text" name="verify[Stickers/Marks]" class="form-control" placeholder="Describe stickers or physical marks..." required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Password Hint</label><input type="text" name="verify[Password Hint]" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Was Charger Included in Bag?</label><select name="verify[Charger Included]" class="form-select" required><option value="Yes">Yes</option><option value="No">No</option></select></div>
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
                        <div class="mb-3"><label class="form-label">Detailed Identification Description</label><textarea name="verify[Additional Description]" rows="4" class="form-control" placeholder="Provide as much unique detail as possible so ownership can be verified..." required></textarea></div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                        <button type="button" onclick="history.back()" class="btn btn-secondary-custom">Cancel</button>
                        <button type="submit" class="btn btn-brand px-4 py-2">
                            <i class="bi bi-send-fill me-1"></i> Submit Verification Claim
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>