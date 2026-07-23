<?php
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../dashboard/index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Save valid session data variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';

            if ($_SESSION['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../dashboard/index.php");
            }
            exit;
        } else {
            $error = "Invalid email credentials or wrong password.";
        }
    } else {
        $error = "Please fill in all criteria inputs.";
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-5">
        <div class="card card-custom shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="fs-1">🔐</span>
                    <h3 class="font-heading mt-2">Welcome Back</h3>
                    <p class="text-muted small">Sign in to report missing items or manage recovery claims</p>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 rounded-3 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i> Campus Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="student@university.edu" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-lock me-1"></i> Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <p class="mb-1 text-muted small">Don't have an account?</p>
                    <a href="register.php" class="text-info fw-semibold text-decoration-none">Create a Student Account</a>
                </div>

                <div class="alert alert-info bg-dark border-primary text-info small mt-4 mb-0 text-center rounded-3">
                    <i class="bi bi-info-circle me-1"></i> Default Admin Credentials:<br>
                    <strong>admin@university.edu</strong> / <strong>admin123</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>