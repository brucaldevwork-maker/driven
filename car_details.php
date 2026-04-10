<?php
require_once 'include/config.php';

// Helper function to safely decode JSON
function safeJsonDecode($json, $default = []) {
    if (empty($json)) return $default;
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

// Helper function to format price
function formatPrice($price) {
    return '₱ ' . number_format(floatval($price), 0, '.', ',');
}

// Get car ID from URL
$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($car_id <= 0) {
    header('Location: available_units.php');
    exit;
}

// Fetch car details with category info
$stmt = $pdo->prepare("
    SELECT c.*, cat.category_name 
    FROM cars c 
    LEFT JOIN categories cat ON c.category_id = cat.category_id 
    WHERE c.car_id = ?
");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    header('Location: available_units.php');
    exit;
}

// Decode images from JSON
$images = safeJsonDecode($car['images']);
$images = array_filter($images); // Remove empty values

// Decode included items from JSON
$included_items = safeJsonDecode($car['included_items']);
if (!is_array($included_items)) {
    $included_items = [];
}

// Get similar cars (same category, different car)
$stmt = $pdo->prepare("
    SELECT car_id, car_name, price, monthly, images, is_low_dp 
    FROM cars 
    WHERE category_id = ? AND car_id != ? 
    ORDER BY created_at DESC
    LIMIT 3
");
$stmt->execute([$car['category_id'], $car_id]);
$similar_cars = $stmt->fetchAll();

$terms = intval($car['terms']);
$termsDisplay = $terms > 0 ? $terms . ' months' : $car['terms'];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $customer_email = sanitize($_POST['customer_email'] ?? '');
    $customer_phone = sanitize($_POST['customer_phone'] ?? '');
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $message = sanitize($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'appointment';
    $car_id_post = (int)($_POST['car_id'] ?? 0);
    
    // Validation
    $errors = [];
    
    if (empty($customer_name)) $errors[] = "Name is required";
    if (empty($customer_email)) $errors[] = "Email is required";
    elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($customer_phone)) $errors[] = "Phone number is required";
    if (empty($appointment_date)) $errors[] = "Appointment date is required";
    if (empty($appointment_time)) $errors[] = "Appointment time is required";
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO reservations (car_id, customer_name, customer_email, customer_phone, appointment_date, appointment_time, message, type, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$car_id_post, $customer_name, $customer_email, $customer_phone, $appointment_date, $appointment_time, $message, $type]);
            
            $success_message = "Your inquiry has been submitted successfully!";
            
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again later.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($car['car_name']); ?> - Detailed view at Driven Online Auto Sales. Check specs, pricing, and availability.">
    <title><?php echo htmlspecialchars($car['car_name']); ?> - Driven Auto Sales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        :root {
            --primary-red: #E50914;
            --primary-red-dark: #B80710;
            --primary-red-light: #FF3B3B;
            --dark-bg: #0A0A0A;
            --card-bg: #111111;
            --card-border: #1E1E1E;
            --text-primary: #FFFFFF;
            --text-secondary: #B3B3B3;
            --text-muted: #6B6B6B;
            --gradient-1: linear-gradient(135deg, #E50914 0%, #FF3B3B 100%);
            --gradient-fire: linear-gradient(135deg, #E50914 0%, #FF8C00 100%);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.5);
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
            --success-green: #00C851;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--card-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-red);
            border-radius: 4px;
        }

        /* Page Header */
        .page-header {
            background: var(--gradient-1);
            padding: 60px 5% 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .page-header h1 {
            font-size: clamp(1.5rem, 5vw, 2.5rem);
            margin-bottom: 10px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .page-header p {
            color: rgba(255,255,255,0.95);
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 5%;
        }

        /* Back Button */
        .back-button {
            margin-bottom: 30px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-fast);
        }
        
        .back-link:hover {
            transform: translateX(-5px);
            color: var(--primary-red-light);
        }

        /* Car Details Grid */
        .car-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-bottom: 60px;
        }

        /* Image Gallery */
        .gallery-container {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }

        .swiper {
            width: 100%;
            height: 450px;
        }

        .swiper-slide {
            background: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: var(--primary-red);
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: var(--transition-fast);
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: var(--primary-red);
            color: white;
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
        }

        .swiper-pagination-bullet {
            background: var(--text-secondary);
            opacity: 0.7;
        }

        .swiper-pagination-bullet-active {
            background: var(--primary-red);
        }

        /* Thumbnails */
        .thumbnail-container {
            display: flex;
            gap: 12px;
            padding: 16px;
            overflow-x: auto;
            border-top: 1px solid var(--card-border);
        }

        .thumbnail-item {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid var(--card-border);
            transition: var(--transition-fast);
            flex-shrink: 0;
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumbnail-item:hover {
            border-color: var(--primary-red);
            transform: translateY(-3px);
        }

        .thumbnail-item.active {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.3);
        }

        /* Car Info */
        .car-info-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .car-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;
        }

        .car-title h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .low-dp-badge {
            background: var(--gradient-fire);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            animation: pulse 2s infinite;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(229, 9, 20, 0.3);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 0 4px 15px rgba(229, 9, 20, 0.5);
            }
        }

        /* Price Card */
        .price-card {
            background: linear-gradient(135deg, rgba(229, 9, 20, 0.1) 0%, rgba(0, 0, 0, 0) 100%);
            border: 1px solid rgba(229, 9, 20, 0.2);
            border-radius: 20px;
            padding: 24px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 12px 0;
        }

        .price-row:first-child {
            border-bottom: 1px solid var(--card-border);
        }

        .price-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .price-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        .monthly-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        /* Specs Grid */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--card-border);
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .spec-item i {
            font-size: 1.25rem;
            color: var(--primary-red);
            width: 32px;
        }

        .spec-info {
            display: flex;
            flex-direction: column;
        }

        .spec-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .spec-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 8px;
        }

        .btn-primary, .btn-secondary {
            flex: 1;
            padding: 14px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            font-size: 0.9375rem;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: var(--card-bg);
            border-color: var(--primary-red);
            transform: translateY(-2px);
        }

        /* Description Section */
        .description-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 40px;
            border: 1px solid var(--card-border);
        }

        .description-section h2 {
            font-size: 1.25rem;
            margin-bottom: 16px;
            color: var(--primary-red);
        }

        .description-section h2 i {
            margin-right: 10px;
        }

        .description-content {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Included Items */
        .included-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 40px;
            border: 1px solid var(--card-border);
        }

        .included-section h2 {
            font-size: 1.25rem;
            margin-bottom: 20px;
            color: var(--primary-red);
        }

        .included-section h2 i {
            margin-right: 10px;
        }

        .included-items {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .included-item {
            background: rgba(229, 9, 20, 0.1);
            padding: 8px 16px;
            border-radius: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            border: 1px solid rgba(229, 9, 20, 0.2);
        }

        .included-item i {
            color: var(--primary-red);
            font-size: 0.75rem;
        }

        /* Similar Vehicles */
        .similar-section {
            margin-top: 40px;
        }

        .similar-section h2 {
            font-size: 1.5rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .similar-section h2 i {
            color: var(--primary-red);
        }

        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .similar-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
            animation: fadeInUp 0.5s ease;
        }

        .similar-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
            box-shadow: var(--shadow-lg);
        }

        .similar-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .similar-lowdp {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--gradient-fire);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .similar-info {
            padding: 20px;
        }

        .similar-info h3 {
            font-size: 1.125rem;
            margin-bottom: 8px;
        }

        .similar-price {
            color: var(--primary-red);
            font-weight: 700;
            font-size: 1.125rem;
            margin: 8px 0;
        }

        .similar-monthly {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 16px;
        }

        .similar-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-fast);
        }

        .similar-link:hover {
            gap: 12px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--card-bg);
            margin: 3% auto;
            width: 90%;
            max-width: 600px;
            border-radius: 28px;
            border: 1px solid var(--card-border);
            animation: slideDown 0.3s ease;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px;
            border-bottom: 1px solid var(--card-border);
            background: var(--card-bg);
            flex-shrink: 0;
        }

        .modal-header h2 {
            font-size: 1.35rem;
            color: var(--primary-red);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            font-size: 1.5rem;
        }

        .close {
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: var(--transition-fast);
            line-height: 1;
        }

        .close:hover {
            color: var(--primary-red);
            transform: rotate(90deg);
        }

        /* Scrollable Modal Body */
        .modal-body {
            padding: 28px;
            overflow-y: auto;
            flex: 1;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-red) var(--card-border);
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: var(--card-border);
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary-red);
            border-radius: 3px;
        }

        /* Success Modal Specific Styles */
        .success-modal-content {
            max-width: 500px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(0, 200, 81, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon i {
            font-size: 48px;
            color: var(--success-green);
        }

        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--success-green);
        }

        .success-message {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .info-box {
            background: rgba(229, 9, 20, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .info-box h4 {
            color: var(--primary-red);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box ul {
            list-style: none;
            padding-left: 0;
        }

        .info-box li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .info-box li i {
            color: var(--primary-red);
            width: 20px;
        }

        .btn-success-close {
            background: var(--gradient-1);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            width: 100%;
            font-size: 1rem;
            margin-top: 20px;
        }

        .btn-success-close:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary-modal {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--text-secondary);
            padding: 12px 24px;
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition-fast);
            margin-top: 12px;
            width: 100%;
        }

        .btn-secondary-modal:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        /* Car Preview in Modal */
        .modal-car-preview {
            background: rgba(229, 9, 20, 0.05);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 24px;
            border: 1px solid rgba(229, 9, 20, 0.2);
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .modal-car-preview-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background-size: cover;
            background-position: center;
            flex-shrink: 0;
        }

        .modal-car-preview-info h4 {
            font-size: 1rem;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .modal-car-preview-info .preview-price {
            color: var(--primary-red);
            font-weight: 700;
            font-size: 0.875rem;
        }

        .modal-car-preview-info .preview-dp {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-top: 4px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group label i {
            margin-right: 8px;
            color: var(--primary-red);
            width: 20px;
        }

        .form-group label .required {
            color: var(--primary-red);
            margin-left: 4px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: var(--dark-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: var(--transition-fast);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px 24px;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .info-text {
            text-align: center;
            margin-top: 16px;
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .info-text i {
            color: var(--primary-red);
        }

        /* Footer */
        .footer {
            background: #050505;
            padding: 48px 5% 32px;
            text-align: center;
            border-top: 1px solid var(--card-border);
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 32px;
        }

        .footer-logo h3 {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .footer-logo h3 span {
            color: var(--primary-red);
        }

        .footer-links {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .footer-links a:hover {
            color: var(--primary-red);
        }

        .footer-copyright {
            padding-top: 32px;
            border-top: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Scroll to Top */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: var(--primary-red);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-normal);
            z-index: 99;
            box-shadow: var(--shadow-md);
        }

        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
            background: var(--primary-red-light);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .car-details-grid,
        .description-section,
        .included-section,
        .similar-section {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .car-details-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .swiper {
                height: 350px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
            }

            .car-title h1 {
                font-size: 1.5rem;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .similar-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .modal-content {
                margin: 5% auto;
                width: 95%;
                max-height: 85vh;
            }

            .modal-header {
                padding: 18px 20px;
            }

            .modal-body {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .modal-car-preview {
                flex-direction: column;
                text-align: center;
            }

            .modal-car-preview-image {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 480px) {
            .thumbnail-item {
                width: 60px;
                height: 60px;
            }

            .modal-header h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/nav.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-car"></i> VEHICLE DETAILS</h1>
        <p>Explore the features and specifications of your dream car</p>
    </div>

    <div class="main-container">
        <!-- Back Button -->
        <div class="back-button">
            <a href="available_units.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Units
            </a>
        </div>

        <!-- Car Details Grid -->
        <div class="car-details-grid">
            <!-- Image Gallery -->
            <div class="gallery-container">
                <div class="swiper carSwiper">
                    <div class="swiper-wrapper">
                        <?php if (!empty($images)): ?>
                            <?php foreach ($images as $index => $img): 
                                $imagePath = !empty($img) && file_exists("uploads/" . $img) ? "uploads/" . $img : "img/default-car.jpg";
                            ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($car['car_name']); ?> - Image <?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="swiper-slide">
                                <img src="img/default-car.jpg" alt="No image available">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $img): 
                        $thumbPath = !empty($img) && file_exists("uploads/" . $img) ? "uploads/" . $img : "img/default-car.jpg";
                    ?>
                        <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <img src="<?php echo $thumbPath; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Car Info Section -->
            <div class="car-info-section">
                <div class="car-title">
                    <h1><?php echo htmlspecialchars($car['car_name']); ?></h1>
                    <?php if ($car['is_low_dp'] == 1): ?>
                        <div class="low-dp-badge">
                            <i class="fas fa-fire"></i> LOW DOWN PAYMENT
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Price Card -->
                <div class="price-card">
                    <div class="price-row">
                        <span class="price-label">Cash Price</span>
                        <span class="price-value"><?php echo formatPrice($car['price']); ?></span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Down Payment</span>
                        <span class="price-value"><?php echo formatPrice($car['down_payment']); ?></span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Monthly Amortization</span>
                        <span class="monthly-value"><?php echo formatPrice($car['monthly']); ?>/mo</span>
                    </div>
                </div>

                <!-- Specifications -->
                <div class="specs-grid">
                    <div class="spec-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="spec-info">
                            <span class="spec-label">Terms</span>
                            <span class="spec-value"><?php echo htmlspecialchars($termsDisplay); ?></span>
                        </div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <div class="spec-info">
                            <span class="spec-label">Mileage</span>
                            <span class="spec-value"><?php echo number_format($car['mileage']); ?> km</span>
                        </div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-tag"></i>
                        <div class="spec-info">
                            <span class="spec-label">Category</span>
                            <span class="spec-value"><?php echo htmlspecialchars($car['category_name']); ?></span>
                        </div>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-percent"></i>
                        <div class="spec-info">
                            <span class="spec-label">DP Percentage</span>
                            <span class="spec-value"><?php echo round(($car['down_payment'] / $car['price']) * 100); ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn-primary" onclick="openReserveModal()">
                        <i class="fas fa-calendar-check"></i> Inquire / Reserve Now
                    </button>
                    <a href="available_units.php" class="btn-secondary">
                        <i class="fas fa-car"></i> Browse Other Cars
                    </a>
                </div>
            </div>
        </div>

        <!-- Description Section -->
        <?php if (!empty($car['description'])): ?>
        <div class="description-section">
            <h2><i class="fas fa-info-circle"></i> Vehicle Description</h2>
            <div class="description-content">
                <?php echo nl2br(htmlspecialchars($car['description'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Included Items Section -->
        <?php if (!empty($included_items)): ?>
        <div class="included-section">
            <h2><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($car['included_heading']); ?></h2>
            <div class="included-items">
                <?php foreach ($included_items as $item): ?>
                    <div class="included-item">
                        <i class="fas fa-check"></i>
                        <span><?php echo htmlspecialchars($item); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Similar Vehicles -->
        <?php if (count($similar_cars) > 0): ?>
        <div class="similar-section">
            <h2><i class="fas fa-car"></i> Similar Vehicles You Might Like</h2>
            <div class="similar-grid">
                <?php foreach ($similar_cars as $similar): 
                    $simImages = safeJsonDecode($similar['images']);
                    $simImage = !empty($simImages[0]) && file_exists("uploads/" . $simImages[0]) 
                        ? "uploads/" . $simImages[0] 
                        : "img/default-car.jpg";
                ?>
                <div class="similar-card">
                    <div class="similar-image" style="background-image: url('<?php echo $simImage; ?>');">
                        <?php if ($similar['is_low_dp']): ?>
                            <div class="similar-lowdp"><i class="fas fa-fire"></i> LOW DP</div>
                        <?php endif; ?>
                    </div>
                    <div class="similar-info">
                        <h3><?php echo htmlspecialchars($similar['car_name']); ?></h3>
                        <div class="similar-price"><?php echo formatPrice($similar['price']); ?></div>
                        <div class="similar-monthly"><?php echo formatPrice($similar['monthly']); ?>/mo</div>
                        <a href="car_details.php?id=<?php echo $similar['car_id']; ?>" class="similar-link">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Reserve/Inquiry Modal -->
    <div id="reserveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-calendar-check"></i>
                    Inquire / Reserve
                </h2>
                <span class="close" onclick="closeReserveModal()">&times;</span>
            </div>
            
            <div class="modal-body">
                <!-- Car Preview -->
                <div class="modal-car-preview">
                    <?php 
                    $previewImage = !empty($images[0]) && file_exists("uploads/" . $images[0]) ? "uploads/" . $images[0] : "img/default-car.jpg";
                    ?>
                    <div class="modal-car-preview-image" style="background-image: url('<?php echo $previewImage; ?>'); background-size: cover; background-position: center;"></div>
                    <div class="modal-car-preview-info">
                        <h4><?php echo htmlspecialchars($car['car_name']); ?></h4>
                        <div class="preview-price"><?php echo formatPrice($car['price']); ?></div>
                        <div class="preview-dp">DP: <?php echo formatPrice($car['down_payment']); ?> | <?php echo round(($car['down_payment'] / $car['price']) * 100); ?>%</div>
                    </div>
                </div>

                <form id="reserveForm" action="" method="POST">
                    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                            <input type="text" name="customer_name" required placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                            <input type="email" name="customer_email" required placeholder="your@email.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number <span class="required">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="+63 XXX XXX XXXX">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-day"></i> Preferred Date <span class="required">*</span></label>
                            <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Preferred Time <span class="required">*</span></label>
                            <input type="time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Inquiry Type</label>
                        <select name="type">
                            <option value="appointment">🚗 Test Drive Appointment</option>
                            <option value="car_reservation">📞 Vehicle Inquiry / Information</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Additional Message</label>
                        <textarea name="message" rows="4" placeholder="Any specific questions or requirements? Let us know!">I'm interested in the <?php echo htmlspecialchars($car['car_name']); ?>. Please contact me with more information.</textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Inquiry
                    </button>
                    
                    <div class="info-text">
                        <i class="fas fa-shield-alt"></i>
                        <span>Your information is secure. Our team will contact you within 24 hours.</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content success-modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-check-circle"></i>
                    Inquiry Submitted!
                </h2>
                <span class="close" onclick="closeSuccessModal()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <div class="success-title">
                    Thank You for Your Interest!
                </div>
                
                <div class="success-message">
                    Your inquiry has been successfully submitted. Our sales team will review your request and contact you within <strong>24 hours</strong> to confirm your appointment or provide more information.
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                    <ul>
                        <li><i class="fas fa-clock"></i> You'll receive a confirmation call/text within 24 hours</li>
                        <li><i class="fas fa-phone-alt"></i> Our staff will verify your preferred date and time</li>
                        <li><i class="fas fa-car"></i> Final confirmation will be sent via email/SMS</li>
                        <li><i class="fas fa-question-circle"></i> Feel free to call us if you have immediate questions</li>
                    </ul>
                </div>
                
                <button class="btn-success-close" onclick="closeSuccessAndRefresh()">
                    <i class="fas fa-check"></i> Got it, Thanks!
                </button>
                
                <button class="btn-secondary-modal" onclick="closeSuccessModal()">
                    <i class="fas fa-car"></i> Continue Browsing
                </button>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <div class="scroll-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <h3>DRIVEN <span>AUTO</span></h3>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Right People · Right Price · Right Car</p>
            </div>
            <div class="footer-links">
                <a href="available_units.php">Browse Cars</a>
                <a href="reserve.php">Book Appointment</a>
                <a href="low_dp_units.php">Low DP Offers</a>
                <a href="contact.php">Contact Us</a>
            </div>
        </div>
        <div class="footer-copyright">
            <p>© <?php echo date('Y'); ?> Driven Online Auto Sales. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper Carousel
        const carSwiper = new Swiper('.carSwiper', {
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            effect: 'slide',
            speed: 600,
        });
        
        // Thumbnail click handler
        const thumbnails = document.querySelectorAll('.thumbnail-item');
        thumbnails.forEach((thumb, index) => {
            thumb.addEventListener('click', () => {
                carSwiper.slideTo(index);
                thumbnails.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
        
        // Update active thumbnail when slide changes
        carSwiper.on('slideChange', () => {
            const activeIndex = carSwiper.realIndex;
            thumbnails.forEach((thumb, index) => {
                if (index === activeIndex) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
        });

        // Modal functions
        function openReserveModal() {
            const modal = document.getElementById('reserveModal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Reset scroll position to top when opening
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody) {
                modalBody.scrollTop = 0;
            }
        }

        function closeReserveModal() {
            document.getElementById('reserveModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function openSuccessModal() {
            document.getElementById('successModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function closeSuccessAndRefresh() {
            closeSuccessModal();
            // Optional: Redirect to available units or stay on page
            // window.location.href = 'available_units.php';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const reserveModal = document.getElementById('reserveModal');
                const successModal = document.getElementById('successModal');
                if (reserveModal.style.display === 'block') {
                    closeReserveModal();
                }
                if (successModal.style.display === 'block') {
                    closeSuccessModal();
                }
            }
        });

        // Form submission handler
        document.getElementById('reserveForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Basic validation
            const name = formData.get('customer_name');
            const email = formData.get('customer_email');
            const phone = formData.get('customer_phone');
            const date = formData.get('appointment_date');
            const time = formData.get('appointment_time');
            
            if (!name || !email || !phone || !date || !time) {
                alert('Please fill in all required fields.');
                return;
            }
            
            if (!email.includes('@')) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // Validate date and time
            const selectedDateTime = new Date(date + ' ' + time);
            const now = new Date();
            
            if (selectedDateTime < now) {
                alert('Please select a future date and time for your appointment.');
                return;
            }
            
            // Submit the form via fetch
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Close the inquiry modal
                closeReserveModal();
                // Open success modal
                openSuccessModal();
                // Reset the form
                document.getElementById('reserveForm').reset();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('There was an error submitting your inquiry. Please try again.');
            });
        });

        // Scroll to top button
        window.addEventListener('scroll', () => {
            const scrollBtn = document.querySelector('.scroll-top');
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (window.scrollY > 100) {
                    navbar.style.background = '#0A0A0A';
                    navbar.style.backdropFilter = 'blur(0px)';
                } else {
                    navbar.style.background = 'rgba(10, 10, 10, 0.95)';
                    navbar.style.backdropFilter = 'blur(10px)';
                }
            }
        });

        // Set min time for today's appointments
        const dateInput = document.querySelector('input[name="appointment_date"]');
        const timeInput = document.querySelector('input[name="appointment_time"]');
        
        if (dateInput && timeInput) {
            dateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate.getTime() === today.getTime()) {
                    const now = new Date();
                    const currentHour = now.getHours();
                    const currentMinute = now.getMinutes();
                    const minTime = `${String(currentHour + 1).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`;
                    timeInput.min = minTime;
                } else {
                    timeInput.min = '09:00';
                }
            });
        }
        
        // Check if we should show success modal (if form was submitted with success)
        <?php if (!empty($success_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openSuccessModal();
        });
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            alert('<?php echo addslashes($error_message); ?>');
        });
        <?php endif; ?>
    </script>
</body>
</html>