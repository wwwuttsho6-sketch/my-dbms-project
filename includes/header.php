<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Dynamically determine project root path context
$base_url = "http://localhost/findit/"; 
$current_page = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIT - University Campus Lost & Found Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <style>
        body, html { background-color: #080616 !important; color: #ffffff !important; }
        .card { background-color: #1A1953 !important; color: #ffffff !important; }
        .table { color: #ffffff !important; }
        .table tbody td { background-color: rgba(26,25,83,0.6) !important; color: #ffffff !important; }
        .table thead th { background-color: rgba(8,6,22,0.92) !important; color: #c7d2fe !important; }
    </style>
</head>
<body style="background-color: #080616 !important; color: #ffffff !important;">

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_url; ?>index.php">
            <span class="fs-3">🔍</span> 
            <span>Find<span style="color: var(--brand-primary);">IT</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'index.php') !== false && strpos($current_page, 'lost') === false && strpos($current_page, 'found') === false ? 'active' : ''; ?>" href="<?php echo $base_url; ?>index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'lost/') !== false ? 'active' : ''; ?>" href="<?php echo $base_url; ?>lost/index.php">
                        <i class="bi bi-search-heart me-1"></i> Lost Section
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'found/') !== false ? 'active' : ''; ?>" href="<?php echo $base_url; ?>found/index.php">
                        <i class="bi bi-check-circle me-1"></i> Found Section
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-warning btn-sm rounded-pill px-3" href="<?php echo $base_url; ?>admin/index.php">
                                <i class="bi bi-shield-lock-fill me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white btn btn-secondary-custom btn-sm px-3" href="<?php echo $base_url; ?>dashboard/index.php">
                            <i class="bi bi-person-circle me-1"></i> Dashboard (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger-emphasis ms-1" href="<?php echo $base_url; ?>auth/logout.php" title="Logout">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-brand btn-sm px-3" href="<?php echo $base_url; ?>auth/register.php">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="main-wrapper py-4">
<div class="container min-vh-100">