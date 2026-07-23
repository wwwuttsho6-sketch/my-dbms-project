<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Metrics queries
$lost_count = $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'Lost'")->fetchColumn();
$found_count = $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'Found'")->fetchColumn();
$recovered_count = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE status = 'Recovered') + (SELECT COUNT(*) FROM found_items WHERE status = 'Returned')")->fetchColumn();

// Fetch recent 3 lost items and 3 found items
$recent_lost = $pdo->query("SELECT * FROM lost_items ORDER BY created_at DESC LIMIT 3")->fetchAll();
$recent_found = $pdo->query("SELECT * FROM found_items ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>

<!-- Hero Banner -->
<div class="hero-banner my-4 text-center text-md-start">
    <div class="row align-items-center gy-4">
        <div class="col-lg-7">
            <span class="badge badge-admin mb-3 px-3 py-2">
                <i class="bi bi-mortarboard-fill me-1"></i> Campus Asset Recovery Network
            </span>
            <h1 class="display-4 font-heading mb-3 text-white">
                Reconnecting Missing Belongings with Their Owners
            </h1>
            <p class="lead text-muted mb-4">
                Ditch cluttered social chat channels. FindIT provides a secure, verified dynamic claim system for university students, staff, and campus security.
            </p>

            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start">
                <a href="lost/index.php" class="btn btn-danger-custom btn-lg px-4 py-2.5">
                    <i class="bi bi-search me-2"></i> Search Lost Feed (<?php echo $lost_count; ?>)
                </a>
                <a href="found/index.php" class="btn btn-success-custom btn-lg px-4 py-2.5">
                    <i class="bi bi-check-circle me-2"></i> Search Found Feed (<?php echo $found_count; ?>)
                </a>
                <a href="lost/create.php" class="btn btn-outline-custom btn-lg px-4 py-2.5">
                    <i class="bi bi-plus-circle me-2"></i> Report Lost Item
                </a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <i class="bi bi-exclamation-circle text-danger fs-2 mb-2 d-block"></i>
                        <h2 class="font-heading text-white mb-0"><?php echo $lost_count; ?></h2>
                        <span class="small text-muted">Active Lost Reports</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <i class="bi bi-check2-circle text-success fs-2 mb-2 d-block"></i>
                        <h2 class="font-heading text-white mb-0"><?php echo $found_count; ?></h2>
                        <span class="small text-muted">Recovered & Found</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="font-heading text-white mb-0"><?php echo $recovered_count; ?>+ Items</h3>
                            <span class="small text-muted">Successfully Returned to Owners</span>
                        </div>
                        <i class="bi bi-trophy text-warning fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Workflow Cards -->
<div class="row text-center my-5 g-4">
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="text-primary fs-1 mb-3"><i class="bi bi-card-checklist"></i></div>
            <h4 class="font-heading">1. Post Belongings</h4>
            <p class="text-muted small mb-0">Create detailed posts with lost/found location tags, categories, descriptions, and optional photos.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="text-info fs-1 mb-3"><i class="bi bi-shield-lock"></i></div>
            <h4 class="font-heading">2. Claim Verification</h4>
            <p class="text-muted small mb-0">Claimants answer customized category questions to securely verify ownership before exchange approval.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom h-100 p-4">
            <div class="text-success fs-1 mb-3"><i class="bi bi-heart-pulse"></i></div>
            <h4 class="font-heading">3. Safe Handover</h4>
            <p class="text-muted small mb-0">Track verification approvals in your dashboard and arrange safe handovers at designated campus locations.</p>
        </div>
    </div>
</div>

<!-- Recent Posts Section -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-heading mb-1"><i class="bi bi-clock-history me-2 text-primary"></i> Recently Reported Items</h3>
            <p class="text-muted mb-0 small">Latest missing and recovered assets on campus</p>
        </div>
        <a href="lost/index.php" class="btn btn-outline-custom btn-sm">Explore All Items &rarr;</a>
    </div>

    <div class="row g-4">
        <?php foreach ($recent_lost as $item): ?>
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="item-img-wrapper">
                        <?php if(!empty($item['image_path'])): ?>
                            <img src="<?php echo $base_url . $item['image_path']; ?>" class="previewable-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="bi bi-image fs-1 opacity-50"></i>
                                <span class="small mt-1">No Image Attached</span>
                            </div>
                        <?php endif; ?>
                        <span class="badge badge-custom badge-lost position-absolute top-0 start-0 m-3">
                            <i class="bi bi-exclamation-circle me-1"></i> Lost
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge badge-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <span class="small text-muted float-end"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d', strtotime($item['lost_date'])); ?></span>
                        </div>
                        <h5 class="card-title font-heading text-white"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($item['description'], 0, 90)) . '...'; ?></p>
                        <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($item['location']); ?></span>
                            <a href="lost/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-brand btn-sm">Details &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php foreach ($recent_found as $item): ?>
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="item-img-wrapper">
                        <?php if(!empty($item['image_path'])): ?>
                            <img src="<?php echo $base_url . $item['image_path']; ?>" class="previewable-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
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
                            <span class="small text-muted float-end"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d', strtotime($item['found_date'])); ?></span>
                        </div>
                        <h5 class="card-title font-heading text-white"><?php echo htmlspecialchars($item['title']); ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($item['description'], 0, 90)) . '...'; ?></p>
                        <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($item['location']); ?></span>
                            <a href="found/detail.php?id=<?php echo $item['id']; ?>" class="btn btn-success-custom btn-sm">Claim Item &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>