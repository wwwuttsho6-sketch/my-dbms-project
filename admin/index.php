<?php
require_once 'includes/admin_header.php';

// Metrics queries
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_lost = $pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$total_found = $pdo->query("SELECT COUNT(*) FROM found_items")->fetchColumn();
$pending_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
$approved_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'Approved'")->fetchColumn();

// Recovery rate calculation
$total_items = max(1, $total_lost + $total_found);
$recovery_rate = round(($approved_claims / $total_items) * 100);

// Category breakdown queries
$cat_phones = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE category='Mobile Phones') + (SELECT COUNT(*) FROM found_items WHERE category='Mobile Phones')")->fetchColumn();
$cat_wallets = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE category='Wallets') + (SELECT COUNT(*) FROM found_items WHERE category='Wallets')")->fetchColumn();
$cat_laptops = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE category='Laptops') + (SELECT COUNT(*) FROM found_items WHERE category='Laptops')")->fetchColumn();
$cat_idcards = $pdo->query("SELECT (SELECT COUNT(*) FROM lost_items WHERE category='ID Cards') + (SELECT COUNT(*) FROM found_items WHERE category='ID Cards')")->fetchColumn();

// Recent 5 users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent 5 claims with full claimant info
$recent_claims = $pdo->query("
    SELECT c.*, u.name as claimant_name, u.email as claimant_email, u.department as claimant_dept
    FROM claims c
    JOIN users u ON c.claimant_id = u.id
    ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();
?>

<!-- Hero Banner -->
<div class="hero-banner my-4">
    <div class="row align-items-center gy-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge badge-admin py-2 px-3 fs-6">
                    <i class="bi bi-shield-check me-1"></i> Admin Portal Active
                </span>
                <span class="badge bg-success text-white border border-success py-2 px-3 fs-6">
                    <i class="bi bi-circle-fill me-1 small"></i> System Operational
                </span>
            </div>
            <h1 class="display-5 font-heading text-white mb-2">
                FindIT Governance & Analytics Control
            </h1>
            <p class="lead text-white mb-0" style="font-size: 1.1rem; color: #ffffff !important;">
                Monitor university asset recovery, review verification claim submissions, and manage student accounts in real-time.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="p-3 rounded-4 bg-dark border border-primary d-inline-block text-start w-100 shadow">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-white small">Claim Resolution Efficiency</span>
                    <span class="fw-bold text-success fs-5"><?php echo $recovery_rate; ?>%</span>
                </div>
                <div class="progress bg-secondary" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo max(10, $recovery_rate); ?>%" aria-valuenow="<?php echo $recovery_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-white small mt-2 fw-semibold">
                    <span><?php echo $approved_claims; ?> Approved</span>
                    <span><?php echo $pending_claims; ?> Pending</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Key Stat Counters -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="stat-card shadow h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 fw-bold text-white">Total Users</h6>
                    <h2 class="font-heading mb-0 text-white display-5"><?php echo $total_users; ?></h2>
                </div>
                <div class="stat-icon text-info"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-medium">Registered Students</span>
                <a href="users.php" class="btn btn-primary btn-sm px-3 fw-bold">Manage &rarr;</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card shadow h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 fw-bold text-white">Lost Reports</h6>
                    <h2 class="font-heading mb-0 text-danger display-5"><?php echo $total_lost; ?></h2>
                </div>
                <div class="stat-icon text-danger"><i class="bi bi-search"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-medium">Active Missing Posts</span>
                <a href="lost_items.php" class="btn btn-danger btn-sm px-3 fw-bold">View Feed &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card shadow h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 fw-bold text-white">Found Items</h6>
                    <h2 class="font-heading mb-0 text-success display-5"><?php echo $total_found; ?></h2>
                </div>
                <div class="stat-icon text-success"><i class="bi bi-check2-circle"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-medium">Recovered on Campus</span>
                <a href="found_items.php" class="btn btn-success btn-sm px-3 fw-bold">View Feed &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card shadow h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase mb-1 fw-bold text-white">Pending Claims</h6>
                    <h2 class="font-heading mb-0 text-warning display-5"><?php echo $pending_claims; ?></h2>
                </div>
                <div class="stat-icon text-warning"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="mt-3 pt-2 border-top border-secondary d-flex justify-content-between align-items-center">
                <span class="text-white fw-medium">Requires Review</span>
                <a href="claims.php" class="btn btn-warning text-dark btn-sm px-3 fw-bold">Review &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Category Distribution Breakdown Cards -->
<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="card card-custom">
            <div class="card-body p-4">
                <h5 class="font-heading text-white mb-3"><i class="bi bi-pie-chart me-2 text-primary"></i> Category Item Volume Distribution</h5>
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="p-3 bg-dark rounded-3 border border-secondary">
                            <i class="bi bi-phone text-info fs-2 d-block mb-1"></i>
                            <span class="text-white small fw-bold d-block">Mobile Phones</span>
                            <h4 class="font-heading text-white mb-0 mt-1"><?php echo $cat_phones; ?> items</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-dark rounded-3 border border-secondary">
                            <i class="bi bi-wallet2 text-warning fs-2 d-block mb-1"></i>
                            <span class="text-white small fw-bold d-block">Wallets</span>
                            <h4 class="font-heading text-white mb-0 mt-1"><?php echo $cat_wallets; ?> items</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-dark rounded-3 border border-secondary">
                            <i class="bi bi-laptop text-primary fs-2 d-block mb-1"></i>
                            <span class="text-white small fw-bold d-block">Laptops</span>
                            <h4 class="font-heading text-white mb-0 mt-1"><?php echo $cat_laptops; ?> items</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-dark rounded-3 border border-secondary">
                            <i class="bi bi-card-heading text-success fs-2 d-block mb-1"></i>
                            <span class="text-white small fw-bold d-block">ID Cards</span>
                            <h4 class="font-heading text-white mb-0 mt-1"><?php echo $cat_idcards; ?> items</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Tables Section -->
<div class="row g-4">
    <!-- Recently Registered Users -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="font-heading text-white mb-0"><i class="bi bi-person-plus me-2 text-info"></i> Recently Joined Users</h5>
                    <a href="users.php" class="btn btn-outline-light btn-sm">Manage All</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-end">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td class="fw-semibold text-white">
                                    <i class="bi bi-person-circle me-1 text-info"></i><?php echo htmlspecialchars($u['name']); ?>
                                </td>
                                <td class="small text-white"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if($u['role'] === 'admin'): ?>
                                        <span class="badge badge-admin">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge badge-category">STUDENT</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-white text-end"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Claims Submissions -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="font-heading text-white mb-0"><i class="bi bi-shield-check me-2 text-warning"></i> Recent Claim Submissions</h5>
                    <a href="claims.php" class="btn btn-brand btn-sm">Review Queue</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Claimant</th>
                                <th>Item Target</th>
                                <th>Status</th>
                                <th class="text-end">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_claims as $c): ?>
                            <tr>
                                <td class="fw-semibold text-white">
                                    <?php echo htmlspecialchars($c['claimant_name']); ?>
                                    <div class="small text-white opacity-75"><?php echo htmlspecialchars($c['claimant_dept']); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-category"><?php echo strtoupper($c['item_type']); ?> #<?php echo $c['item_id']; ?></span>
                                </td>
                                <td>
                                    <?php if ($c['status'] == 'Approved'): ?>
                                        <span class="badge badge-found">Approved</span>
                                    <?php elseif ($c['status'] == 'Rejected'): ?>
                                        <span class="badge badge-lost">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-white text-end"><?php echo date('M d, H:i', strtotime($c['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent_claims)): ?>
                                <tr><td colspan="4" class="text-center text-white py-4">No claims submitted yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
