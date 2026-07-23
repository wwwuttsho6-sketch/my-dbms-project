<?php
// Establish tracking settings and start global session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'findit_db';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Auto-migrate role column if not exists in users table
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'");
        }

        // Seed initial admin user if none exists
        $adminCheck = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        if (!$adminCheck) {
            $adminEmail = 'admin@university.edu';
            $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, department, university_id, password, role) VALUES (?, ?, ?, ?, ?, ?, 'admin') ON DUPLICATE KEY UPDATE role = 'admin'");
            $stmt->execute(['System Admin', $adminEmail, '0000000000', 'Administration', 'ADMIN001', $adminPass]);
        }
    } catch (\PDOException $ex) {
        // Table might not exist yet if database script hasn't run
    }

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>