<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Capture Search and Category Inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build Query dynamically based on filters
$query = "SELECT found_items.*, users.name as finder_name FROM found_items 
          JOIN users ON found_items.user_id = users.id 
          WHERE found_items.status = 'Found'";
$params = [];

if (!empty($search)) {
    $query .= " AND (found_items.title LIKE ? OR found_items.description LIKE ? OR found_items.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND found_items.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY found_items.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="text-success">🟢 Found Items Feed</h2>
        <p class="text-muted">Browse items turned in or found around the university campus.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="btn btn-success">+ Report a Found Item</a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-secondary">Login to Report Item</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm p-3 mb-4">
    <form action="index.php" method="GET" class="row g-2">
        <div class="col-md-6">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search by item name, landmarks, or location...">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <option value="Mobile Phones" <?php echo $category == 'Mobile Phones' ? 'selected' : ''; ?>>Mobile Phones</option>
                <option value="Wallets" <?php echo $category == 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
                <option value="Laptops" <?php echo $category == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                <option value="ID Cards" <?php echo $category == 'ID Cards' ? 'selected' : ''; ?>>ID Cards</option>
                <option value="Other Items" <?php echo $category == 'Other Items' ? 'selected' : ''; ?>>Other Items</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
</div>

<div class="row">
    <?php if(count($items) > 0): ?>
        <?php foreach($items as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="../uploads/found_items/<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Found Item Image">
                    <?php else: ?>
                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span>No Image Uploaded</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-success mb-2 align-self-start"><?php echo htmlspecialchars($item['category']); ?></span>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="card-text text-muted flex-grow-1">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?>
                        </p>
                        <div class="text-xs text-muted mb-2">
                            📍 <strong>Found Location:</strong> <?php echo htmlspecialchars($item['location']); ?><br>
                            📅 <strong>Date Found:</strong> <?php echo htmlspecialchars($item['found_date']); ?>
                        </div>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-success w-100 mt-2">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <h5 class="text-muted">No recovered items match this criteria query.</h5>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>