<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'driven_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['username']);
}

// Function to redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /Driven/auth/admin_login.php');
        exit();
    }
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Function to display success message
function showSuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// Function to display error message
function showError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}
// Function to logout
function adminLogout() {
    session_destroy();
    header('Location: /Driven/auth/admin_login.php');
    exit();
}
?>