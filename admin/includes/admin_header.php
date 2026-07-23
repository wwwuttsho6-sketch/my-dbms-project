<?php
require_once __DIR__ . '/admin_check.php';
$admin_current = $_SERVER['PHP_SELF'];

// Quick count for admin badges
$pending_claims_badge = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'Pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Governance Suite - FindIT Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <!-- Admin Nuclear Dark Override (must be after Bootstrap) -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/admin.css">
    <style>
        /* Final inline nuclear override - cannot be beaten by any external stylesheet */
        body, html { background-color: #080616 !important; color: #ffffff !important; }
        .card { background-color: #1A1953 !important; color: #ffffff !important; }
        .table tbody td { background-color: rgba(26,25,83,0.65) !important; color: #ffffff !important; }
        .table thead th { background-color: rgba(8,6,22,0.95) !important; color: #c7d2fe !important; }
        .table { color: #ffffff !important; }
        .stat-card { color: #ffffff !important; }
        .stat-card * { color: #ffffff !important; }
    </style>
</head>
<body class="admin-body" style="background-color: #080616 !important; color: #ffffff !important;">

<nav class="navbar navbar-expand-lg navbar-dark admin-header sticky-top shadow-lg">
    <div class="container-fluid px-md-5">
        <a class="navbar-brand font-heading d-flex align-items-center gap-2" href="<?php echo $base_url; ?>admin/index.php">
            <div class="rounded-circle bg-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; box-shadow: 0 0 15px rgba(47,47,228,0.6);">
                <i class="bi bi-shield-lock-fill text-white fs-5"></i>
            </div>
            <div>
                <span class="fs-4 font-heading text-white">Find<span style="color: var(--brand-primary);">IT</span></span>
                <span class="badge bg-warning text-dark font-heading ms-2 small">ADMIN GOVERNANCE</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto ms-lg-4 gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo strpos($admin_current, 'admin/index.php') !== false ? 'active fw-bold' : ''; ?>" href="<?php echo $base_url; ?>admin/index.php">
                        <i class="bi bi-speedometer2 me-1 text-primary"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo strpos($admin_current, 'admin/users.php') !== false ? 'active fw-bold' : ''; ?>" href="<?php echo $base_url; ?>admin/users.php">
                        <i class="bi bi-people-fill me-1 text-info"></i> User Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo strpos($admin_current, 'admin/lost_items.php') !== false ? 'active fw-bold' : ''; ?>" href="<?php echo $base_url; ?>admin/lost_items.php">
                        <i class="bi bi-search me-1 text-danger"></i> Lost Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo strpos($admin_current, 'admin/found_items.php') !== false ? 'active fw-bold' : ''; ?>" href="<?php echo $base_url; ?>admin/found_items.php">
                        <i class="bi bi-check2-circle me-1 text-success"></i> Found Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 position-relative <?php echo strpos($admin_current, 'admin/claims.php') !== false ? 'active fw-bold' : ''; ?>" href="<?php echo $base_url; ?>admin/claims.php">
                        <i class="bi bi-clipboard-check me-1 text-warning"></i> Claims
                        <?php if($pending_claims_badge > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size: 0.65rem;">
                                <?php echo $pending_claims_badge; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item">
                    <span class="badge bg-dark border border-secondary text-info px-3 py-2">
                        <i class="bi bi-person-fill-check me-1"></i> Logged as <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-custom btn-sm px-3" href="<?php echo $base_url; ?>index.php" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Public Site
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-danger-custom btn-sm px-3" href="<?php echo $base_url; ?>auth/logout.php">
                        <i class="bi bi-power me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-wrapper py-4" style="background-color: #080616;">
<div class="container-fluid px-md-5 min-vh-100">
