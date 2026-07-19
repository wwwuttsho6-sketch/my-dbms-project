<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Capture search parameter filters from uniform GET forms
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

$lost_results = [];
$found_results = [];

if (!empty($search_query) || !empty($category_filter)) {
    // 1. Query matching records from the Lost Items database partition table
    $lost_sql = "SELECT l.*, u.name as owner_name FROM lost_items l 
                 JOIN users u ON l.user_id = u.id 
                 WHERE l.status = 'Lost'";
    $lost_params = [];
    
    if (!empty($search_query)) {
        $lost_sql .= " AND (l.title LIKE ? OR l.description LIKE ? OR l.location LIKE ?)";
        $lost_params[] = "%$search_query%";
        $lost_params[] = "%$search_query%";
        $lost_params[] = "%$search_query%";
    }
    if (!empty($category_filter)) {
        $lost_sql .= " AND l.category = ?";
        $lost_params[] = $category_filter;
    }
    $lost_sql .= " ORDER BY l.created_at DESC";
    $lost_stmt = $pdo->prepare($lost_sql);
    $lost_stmt->execute($lost_params);
    $lost_results = $lost_stmt->fetchAll();

    // 2. Query matching records from the Found Items database partition table
    $found_sql = "SELECT f.*, u.name as finder_name FROM found_items f 
                  JOIN users u ON f.user_id = u.id 
                  WHERE f.status = 'Found'";
    $found_params = [];
    
    if (!empty($search_query)) {
        $found_sql .= " AND (f.title LIKE ? OR f.description LIKE ? OR f.location LIKE ?)";
        $found_params[] = "%$search_query%";
        $found_params[] = "%$search_query%";
        $found_params[] = "%$search_query%";
    }
    if (!empty($category_filter)) {
        $found_sql .= " AND f.category = ?";
        $found_params[] = $category_filter;
    }
    $found_sql .= " ORDER BY f.created_at DESC";
    $found_stmt = $pdo->prepare($found_sql);
    $found_stmt->execute($found_params);
    $found_results = $found_stmt->fetchAll();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold text-primary">🔍 Global Search Engine</h2>
        <p class="text-muted">Query the entire campus database instantly for missing or recovered assets.</p>
    </div>
</div>

<div class="card shadow-sm border-0 p-4 mb-5 bg-white">
    <form action="search.php" method="GET" class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Keywords / Item Name / Landmark Location</label>
            <input type="text" name="query" value="<?php echo htmlspecialchars($search_query); ?>" class="form-control form-control-lg" placeholder="e.g., iPhone, wallet, library, keys...">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Filter By System Category</label>
            <select name="category" class="form-select form-select-lg">
                <option value="">All Dynamic Categories</option>
                <option value="Mobile Phones" <?php echo $category_filter == 'Mobile Phones' ? 'selected' : ''; ?>>Mobile Phones</option>
                <option value="Wallets" <?php echo $category_filter == 'Wallets' ? 'selected' : ''; ?>>Wallets</option>
                <option value="Laptops" <?php echo $category_filter == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                <option value="ID Cards" <?php echo $category_filter == 'ID Cards' ? 'selected' : ''; ?>>ID Cards</option>
                <option value="Other Items" <?php echo $category_filter == 'Other Items' ? 'selected' : ''; ?>>Other Items</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">Search</button>
        </div>
    </form>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <h4 class="text-danger border-bottom pb-2 mb-3">🔴 Matching Lost Reports (<?php echo count($lost_results); ?>)</h4>
        <?php if(count($lost_results) > 0): ?>
            <?php foreach($lost_results as $item): ?>
                <div class="card mb-3 shadow-sm h-100-style">
                    <div class="card-body">
                        <span class="badge bg-danger mb-2"><?php echo htmlspecialchars($item['category']); ?></span>
                        <h5 class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="text-muted text-sm mb-2"><?php echo htmlspecialchars(substr($item['description'], 0, 120)) . '...'; ?></p>
                        <small class="text-xs text-muted d-block">📍 Location: <?php echo htmlspecialchars($item['location']); ?> | 📅 Date: <?php echo $item['lost_date']; ?></small>
                        <a href="lost/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger mt-3">View Record Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted italic py-3 bg-light text-center rounded">No matching lost entries listed.</p>
        <?php endif; ?>
    </div>

    <div class="col-lg-6 mb-4">
        <h4 class="text-success border-bottom pb-2 mb-3">🟢 Matching Recovered Reports (<?php echo count($found_results); ?>)</h4>
        <?php if(count($found_results) > 0): ?>
            <?php foreach($found_results as $item): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <span class="badge bg-success mb-2"><?php echo htmlspecialchars($item['category']); ?></span>
                        <h5 class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="text-muted text-sm mb-2"><?php echo htmlspecialchars(substr($item['description'], 0, 120)) . '...'; ?></p>
                        <small class="text-xs text-muted d-block">📍 Location: <?php echo htmlspecialchars($item['location']); ?> | 📅 Date: <?php echo $item['found_date']; ?></small>
                        <a href="found/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-success mt-3">View Record Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted italic py-3 bg-light text-center rounded">No matching found entries listed.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>