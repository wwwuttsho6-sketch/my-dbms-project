<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Quick aggregate metrics counters calculation queries
$lost_count = $pdo->query("SELECT COUNT(*) FROM lost_items WHERE status = 'Lost'")->fetchColumn();
$found_count = $pdo->query("SELECT COUNT(*) FROM found_items WHERE status = 'Found'")->fetchColumn();
?>

<div class="bg-white p-5 rounded shadow-sm text-center my-4 border">
    <h1 class="display-4 fw-bold text-primary">🔍 FindIT</h1>
    <p class="lead text-muted">A dedicated centralized platform designed specifically for university campus lost, found, and verified asset recovery tracking.</p>
    <hr class="my-4">
    <p>Ditch cluttered social messaging channels. Post missing item credentials, view reported recoveries, and leverage category-specific manual verification form layouts safely.</p>
    
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-3">
        <a href="lost/index.php" class="btn btn-danger btn-lg px-4 me-sm-3">🔴 Search Lost Items Feed (<?php echo $lost_count; ?>)</a>
        <a href="found/index.php" class="btn btn-success btn-lg px-4">🟢 Search Found Items Feed (<?php echo $found_count; ?>)</a>
    </div>
</div>

<div class="row text-center my-5">
    <div class="col-md-4">
        <div class="p-3">
            <h3 class="text-primary fw-bold">1. Post Belongings</h3>
            <p class="text-muted">Quickly add database records detailing items lost or found on campus grounds, including locations and image assets.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3">
            <h3 class="text-primary fw-bold">2. Manual Form Check</h3>
            <p class="text-muted">Claimants must complete custom dynamic questionnaires to securely verify structural details before exchange workflows trigger.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3">
            <h3 class="text-primary fw-bold">3. Safe Recovery</h3>
            <p class="text-muted">Review confirmation indicators directly inside your dashboard module and safely return items back to their right owners.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>