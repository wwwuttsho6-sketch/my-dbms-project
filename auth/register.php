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
            
            // Insert user record
            $insert = $pdo->prepare("INSERT INTO users (name, email, phone, department, university_id, password) VALUES (?, ?, ?, ?, ?, ?)");
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
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4>Create your FindIT Account</h4>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Campus Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g., CSE, EEE, BBA" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">University Student ID</label>
                        <input type="text" name="university_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Sign Up</button>
                </form>
                <div class="text-center mt-3">
                    <p class="mb-0">Already registered? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>