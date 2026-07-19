<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Capture Search and Category Inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build Query dynamically based on filters
$query = "SELECT lost_items.*, users.name as owner_name FROM lost_items 
          JOIN users ON lost_items.user_id = users.id 
          WHERE lost_items.status = 'Lost'";
$params = [];

if (!empty($search)) {
    $query .= " AND (lost_items.title LIKE ? OR lost_items.description LIKE ? OR lost_items.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND lost_items.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY lost_items.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="text-danger">🔴 Lost Items Feed</h2>
        <p class="text-muted">Browse items reported lost on campus or post a new one.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="btn btn-danger">+ Report a Lost Item</a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-secondary">Login to Report Item</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm p-3 mb-4">
    <form action="index.php" method="GET" class="row g-2">
        <div class="col-md-6">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search by title, keywords, or location...">
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
                        <img src="../uploads/lost_items/<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Item Image">
                    <?php else: ?>
                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span>No Image Provided</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-danger mb-2 align-self-start"><?php echo htmlspecialchars($item['category']); ?></span>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="card-text text-muted flex-grow-1">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?>
                        </p>
                        <div class="text-xs text-muted mb-2">
                            📍 <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?><br>
                            📅 <strong>Date Lost:</strong> <?php echo htmlspecialchars($item['lost_date']); ?>
                        </div>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-danger w-100 mt-2">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <h5 class="text-muted">No lost items found matching your criteria.</h5>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>