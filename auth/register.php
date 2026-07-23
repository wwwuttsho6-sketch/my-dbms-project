<?php
require_once '../config/db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $university_id = trim($_POST['university_id']);
    $password = $_POST['password'];

    if (!empty($name) && !empty($email) && !empty($phone) && !empty($department) && !empty($university_id) && !empty($password)) {
        
        // Check if email or University ID already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR university_id = ?");
        $stmt->execute([$email, $university_id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email or University ID is already registered.";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user record with default 'user' role
            $insert = $pdo->prepare("INSERT INTO users (name, email, phone, department, university_id, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
            if ($insert->execute([$name, $email, $phone, $department, $university_id, $hashed_password])) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "An error occurred. Please try again later.";
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-7">
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="fs-1">🎓</span>
                    <h3 class="font-heading mt-2">Create Student Account</h3>
                    <p class="text-muted small">Register to report items, track recoveries, and verify ownership</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success bg-success text-white border-0 rounded-3 mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?> <a href="login.php" class="text-white fw-bold text-decoration-underline ms-2">Click to Login</a>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-person me-1"></i> Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-envelope me-1"></i> Campus Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="john@university.edu" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-telephone me-1"></i> Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+880 1700 000000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-building me-1"></i> Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g., Computer Science" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-card-heading me-1"></i> University Student ID</label>
                            <input type="text" name="university_id" class="form-control" placeholder="2024-1-60-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-key me-1"></i> Account Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-brand w-100 py-2.5 mt-4">
                        <i class="bi bi-person-check-fill me-1"></i> Complete Registration
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <p class="mb-0 text-muted small">Already registered? <a href="login.php" class="text-info fw-semibold text-decoration-none">Sign In to Account</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>