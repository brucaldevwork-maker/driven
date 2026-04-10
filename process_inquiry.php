<?php
session_start();
require_once 'include/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $car_name = isset($_POST['car_name']) ? trim($_POST['car_name']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validate
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    if (empty($errors)) {
        try {
            // Insert into reservations table (since it matches your database structure)
            $stmt = $pdo->prepare("
                INSERT INTO reservations (car_id, customer_name, customer_email, customer_phone, message, type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'car_reservation', 'pending', NOW())
            ");
            $stmt->execute([$car_id, $name, $email, $phone, $message]);
            
            $_SESSION['success'] = "Your inquiry has been sent successfully! We'll contact you soon.";
            header("Location: car_details.php?id=$car_id&success=1");
            exit;
        } catch (PDOException $e) {
            error_log("Inquiry Error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to send inquiry. Please try again.";
            header("Location: car_details.php?id=$car_id&error=1");
            exit;
        }
    } else {
        $_SESSION['errors'] = $errors;
        header("Location: car_details.php?id=$car_id");
        exit;
    }
} else {
    header("Location: available_units.php");
    exit;
}
?>