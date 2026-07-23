<?php
require_once 'includes/admin_header.php';

$message = '';
$error = '';

// Process Actions (Role Toggle & Delete User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $target_user_id = intval($_POST['user_id']);
        
        if ($_POST['action'] === 'toggle_role') {
            $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
            
            // Prevent removing self admin
            if ($target_user_id === $_SESSION['user_id'] && $new_role === 'user') {
                $error = "You cannot remove your own admin privileges!";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $target_user_id]);
                $message = "User role successfully updated to " . strtoupper($new_role) . ".";
            }
        } elseif ($_POST['action'] === 'delete_user') {
            if ($target_user_id === $_SESSION['user_id']) {
                $error = "You cannot delete your own admin account!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$target_user_id]);
                $message = "User account successfully deleted.";
            }
        }
    }
}

// Search Filter
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR university_id LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="font-heading mb-1">👥 User Account Governance</h2>
        <p class="text-muted mb-0">Manage registered students and administrators.</p>
    </div>
    
    <form action="users.php" method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by name, email, ID..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-brand"><i class="bi bi-search"></i></button>
        <?php if(!empty($search)): ?>
            <a href="users.php" class="btn btn-secondary-custom"><i class="bi bi-x-circle"></i></a>
        <?php endif; ?>
    </form>
</div>

<?php if(!empty($message)): ?>
    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>User Name</th>
                        <th>Email & Phone</th>
                        <th>Department & Uni ID</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#<?php echo $u['id']; ?></td>
                        <td class="fw-semibold">
                            <?php echo htmlspecialchars($u['name']); ?>
                            <?php if($u['id'] === $_SESSION['user_id']): ?>
                                <span class="badge bg-info text-dark ms-1">You</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($u['email']); ?></div>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($u['phone']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($u['department']); ?></div>
                            <div class="small text-muted">ID: <?php echo htmlspecialchars($u['university_id']); ?></div>
                        </td>
                        <td>
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="badge badge-admin"><i class="bi bi-shield-check"></i> ADMIN</span>
                            <?php else: ?>
                                <span class="badge badge-category">STUDENT</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-2">
                                <form action="users.php" method="POST" onsubmit="return confirm('Toggle admin role for this user?');">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="new_role" value="<?php echo $u['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Toggle Role">
                                        <i class="bi bi-person-gear"></i> <?php echo $u['role'] === 'admin' ? 'Make User' : 'Make Admin'; ?>
                                    </button>
                                </form>

                                <?php if($u['id'] !== $_SESSION['user_id']): ?>
                                    <form action="users.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user and all their posts/claims?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-danger-custom btn-sm" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No users found matching query.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
