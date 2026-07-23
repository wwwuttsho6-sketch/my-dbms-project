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

<div class="row mb-4 align-items-center gy-3">
    <div class="col-md-7">
        <h2 class="font-heading mb-1 text-white"><span class="text-success">🟢</span> Campus Found Items Feed</h2>
        <p class="text-muted mb-0">Browse items recovered on campus grounds or report an item you found.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="btn btn-success-custom px-4 py-2">
                <i class="bi bi-plus-circle me-1"></i> Report a Found Item
            </a>
        <?php else: ?>
            <a href="../auth/login.php" class="btn btn-brand px-4 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login to Report Item
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Interactive Filter Bar -->
<div class="card card-custom p-3 mb-4">
    <form action="index.php" method="GET" class="row g-2">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-dark text-muted border-secondary border-opacity-25"><i class="bi bi-search"></i></span>
                <input type="text" name="search" id="itemSearchInput" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Type keywords, item title, or location...">
            </div>
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">All Item Categories</option>
                <option value="Mobile Phones" <?php echo $category == 'Mobile Phones' ? 'selected' : ''; ?>>Mobile Phones</option>
                <option value="Wallets" <?php echo $category == 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
                <option value="Laptops" <?php echo $category == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                <option value="ID Cards" <?php echo $category == 'ID Cards' ? 'selected' : ''; ?>>ID Cards</option>
                <option value="Other Items" <?php echo $category == 'Other Items' ? 'selected' : ''; ?>>Other Items</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-brand w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
        </div>
    </form>

    <!-- Quick Category Filter Pills -->
    <div class="nav nav-pills nav-pills-custom mt-3 pt-3 border-top border-secondary border-opacity-25">
        <a href="index.php" class="nav-link category-filter <?php echo empty($category) ? 'active' : ''; ?>" data-category="all">All Items</a>
        <a href="index.php?category=Mobile Phones" class="nav-link category-filter <?php echo $category == 'Mobile Phones' ? 'active' : ''; ?>" data-category="Mobile Phones">Mobile Phones</a>
        <a href="index.php?category=Wallets" class="nav-link category-filter <?php echo $category == 'Wallets' ? 'active' : ''; ?>" data-category="Wallets">Wallets</a>
        <a href="index.php?category=Laptops" class="nav-link category-filter <?php echo $category == 'Laptops' ? 'active' : ''; ?>" data-category="Laptops">Laptops</a>
        <a href="index.php?category=ID Cards" class="nav-link category-filter <?php echo $category == 'ID Cards' ? 'active' : ''; ?>" data-category="ID Cards">ID Cards</a>
        <a href="index.php?category=Other Items" class="nav-link category-filter <?php echo $category == 'Other Items' ? 'active' : ''; ?>" data-category="Other Items">Other Items</a>
    </div>
</div>

<!-- Item Grid -->
<div class="row g-4" id="itemsGrid">
    <?php if(count($items) > 0): ?>
        <?php foreach($items as $item): ?>
            <?php 
                $img = $item['image_path'];
                if(!empty($img) && strpos($img, 'http') === false && strpos($img, 'uploads/') !== 0) {
                    $img = 'uploads/found_items/' . $img;
                }
            ?>
            <div class="col-md-4 item-card-col" 
                 data-title="<?php echo htmlspecialchars($item['title']); ?>"
                 data-location="<?php echo htmlspecialchars($item['location']); ?>"
                 data-category="<?php echo htmlspecialchars($item['category']); ?>">
                
                <div class="card card-custom h-100">
                    <div class="item-img-wrapper">
                        <?php if(!empty($img)): ?>
                            <img src="<?php echo $base_url . $img; ?>" class="previewable-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="bi bi-image fs-1 opacity-50"></i>
                                <span class="small mt-1">No Image Attached</span>
                            </div>
                        <?php endif; ?>
                        <span class="badge badge-custom badge-found position-absolute top-0 start-0 m-3">
                            <i class="bi bi-check-circle me-1"></i> Found
                        </span>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge badge-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <span class="small text-muted float-end"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($item['found_date'])); ?></span>
                        </div>

                        <h5 class="card-title font-heading text-white"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="card-text text-muted small flex-grow-1">
                            <?php echo htmlspecialchars(substr($item['description'], 0, 110)) . (strlen($item['description']) > 110 ? '...' : ''); ?>
                        </p>

                        <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-geo-alt me-1 text-success"></i><?php echo htmlspecialchars($item['location']); ?></span>
                            <a href="detail.php?id=<?php echo $item['id']; ?>" class="btn btn-success-custom btn-sm">
                                Claim This Item &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="col-12 text-center py-5" id="noItemsNotice" style="<?php echo count($items) === 0 ? 'display: block;' : 'display: none;'; ?>">
        <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted font-heading">No found items matching your criteria.</h5>
        <p class="small text-muted">Try clearing search filters or report a new found item.</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>