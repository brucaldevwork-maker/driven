<?php
require_once 'include/config.php';

// Initialize variables
$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
$car = null;
$error = '';
$success = false;

// Fetch car details if car_id is provided
if ($car_id > 0) {
    $stmt = $pdo->prepare("SELECT c.*, cat.category_name 
                           FROM cars c 
                           LEFT JOIN categories cat ON c.category_id = cat.category_id 
                           WHERE c.car_id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
}

// Helper function to format price
function formatPrice($price) {
    return '₱ ' . number_format(floatval($price), 0, '.', ',');
}

// Process reservation form submission
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
    
    if (empty($customer_name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($customer_email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($customer_phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($appointment_date)) {
        $errors[] = "Appointment date is required";
    }
    
    if (empty($appointment_time)) {
        $errors[] = "Appointment time is required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO reservations (car_id, customer_name, customer_email, customer_phone, appointment_date, appointment_time, message, type, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$car_id_post, $customer_name, $customer_email, $customer_phone, $appointment_date, $appointment_time, $message, $type]);
            
            $success = true;
            
            // Clear form data
            $customer_name = $customer_email = $customer_phone = $appointment_date = $appointment_time = $message = '';
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    $error = !empty($errors) ? implode("<br>", $errors) : '';
}

// Get minimum date for appointment (today)
$min_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reserve your dream car at Driven Online Auto Sales. Book an appointment to view and test drive our vehicles.">
    <title>Reserve a Vehicle - Book Your Test Drive | Driven Auto Sales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --error-red: #ff4444;
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
            padding: 80px 5% 60px;
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
            font-size: clamp(2rem, 6vw, 3.5rem);
            margin-bottom: 20px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .page-header h1 i {
            margin-right: 15px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .page-header p {
            color: rgba(255,255,255,0.95);
            font-size: 1.125rem;
            position: relative;
            z-index: 1;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 5%;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            animation: slideDown 0.5s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(0, 200, 81, 0.1);
            border: 1px solid var(--success-green);
            color: var(--success-green);
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--error-red);
            color: var(--error-red);
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Two Column Layout */
        .reservation-layout {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 40px;
        }

        /* Car Details Card */
        .car-details-card {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            position: sticky;
            top: 100px;
            animation: fadeInLeft 0.6s ease;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .car-image-wrapper {
            position: relative;
            height: 280px;
            overflow: hidden;
        }

        .car-image {
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }

        .car-details-card:hover .car-image {
            transform: scale(1.05);
        }

        .car-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--gradient-fire);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            animation: pulse 2s infinite;
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

        .car-info {
            padding: 24px;
        }

        .car-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .car-specs {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 16px 0;
            border-top: 1px solid var(--card-border);
            border-bottom: 1px solid var(--card-border);
        }

        .spec-item {
            flex: 1;
            text-align: center;
        }

        .spec-item i {
            font-size: 1.25rem;
            color: var(--primary-red);
            margin-bottom: 8px;
            display: block;
        }

        .spec-item .label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .spec-item .value {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .price-breakdown {
            background: rgba(229, 9, 20, 0.05);
            padding: 20px;
            border-radius: 16px;
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.875rem;
        }

        .price-row.total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--card-border);
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary-red);
        }

        /* Reservation Form */
        .reservation-form-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 32px;
            border: 1px solid var(--card-border);
            animation: fadeInRight 0.6s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .reservation-form-card h2 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .reservation-form-card p {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .form-group label i {
            margin-right: 8px;
            color: var(--primary-red);
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
            font-size: 0.9375rem;
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

        .form-group input[type="date"],
        .form-group input[type="time"] {
            cursor: pointer;
        }

        .form-group input[type="date"]::-webkit-calendar-picker-indicator,
        .form-group input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            padding: 12px 0;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            width: auto;
            cursor: pointer;
            accent-color: var(--primary-red);
        }

        .radio-option label {
            margin-bottom: 0;
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 14px 28px;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition-normal);
            font-weight: 600;
            font-size: 1rem;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* No Car Selected */
        .no-car-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            border: 1px solid var(--card-border);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .no-car-card i {
            font-size: 64px;
            color: var(--primary-red);
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .no-car-card h2 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .no-car-card p {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 28px;
            background: var(--gradient-1);
            color: white;
            text-decoration: none;
            border-radius: 40px;
            transition: var(--transition-normal);
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        /* Responsive */
        @media (max-width: 968px) {
            .reservation-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .car-details-card {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 50px 20px 40px;
            }
            
            .main-container {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .reservation-form-card {
                padding: 24px;
            }

            .car-specs {
                flex-direction: column;
                gap: 12px;
            }

            .spec-item {
                text-align: left;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .spec-item i {
                margin-bottom: 0;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .radio-group {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/nav.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> RESERVE A VEHICLE</h1>
        <p>Book your test drive and take the first step toward owning your dream car</p>
    </div>
    
    <div class="main-container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Reservation Submitted Successfully!</strong><br>
                    Thank you for your interest. We'll contact you shortly to confirm your appointment.
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Please fix the following errors:</strong><br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($car_id > 0 && $car): ?>
            <div class="reservation-layout">
                <!-- Car Details Section -->
                <div class="car-details-card">
                    <?php 
                    $images = safeJsonDecode($car['images']);
                    $firstImage = !empty($images) ? htmlspecialchars($images[0]) : 'placeholder.jpg';
                    $imagePath = "uploads/" . $firstImage;
                    $terms = intval($car['terms']);
                    $termsDisplay = $terms > 0 ? $terms . ' months' : $car['terms'];
                    ?>
                    <div class="car-image-wrapper">
                        <div class="car-image" style="background-image: url('<?php echo $imagePath; ?>'); background-size: cover; background-position: center;"></div>
                        <?php if ($car['is_low_dp']): ?>
                            <div class="car-badge"><i class="fas fa-fire"></i> LOW DP OFFER</div>
                        <?php endif; ?>
                    </div>
                    <div class="car-info">
                        <h2><?php echo htmlspecialchars($car['car_name']); ?></h2>
                        <div class="car-specs">
                            <div class="spec-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div class="label">Terms</div>
                                <div class="value"><?php echo htmlspecialchars($termsDisplay); ?></div>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <div class="label">Mileage</div>
                                <div class="value"><?php echo number_format($car['mileage']); ?> km</div>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-car"></i>
                                <div class="label">Category</div>
                                <div class="value"><?php echo htmlspecialchars($car['category_name'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Vehicle Price:</span>
                                <strong><?php echo formatPrice($car['price']); ?></strong>
                            </div>
                            <?php if ($car['down_payment'] > 0): ?>
                            <div class="price-row">
                                <span>Down Payment:</span>
                                <strong><?php echo formatPrice($car['down_payment']); ?></strong>
                            </div>
                            <?php endif; ?>
                            <div class="price-row total">
                                <span>Monthly Amortization:</span>
                                <strong><?php echo formatPrice($car['monthly']); ?>/mo</strong>
                            </div>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">
                            <i class="fas fa-info-circle"></i> Fill out the form to schedule a test drive or get more information about this vehicle.
                        </p>
                    </div>
                </div>

                <!-- Reservation Form -->
                <div class="reservation-form-card">
                    <h2>Schedule Your Appointment</h2>
                    <p>Fill in your details and preferred appointment time</p>
                    
                    <form method="POST" action="" id="reservationForm">
                        <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                            <input type="text" name="customer_name" placeholder="Enter your full name" 
                                   value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                                <input type="email" name="customer_email" placeholder="your@email.com" 
                                       value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone Number <span class="required">*</span></label>
                                <input type="tel" name="customer_phone" placeholder="+63 XXX XXX XXXX" 
                                       value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Preferred Date <span class="required">*</span></label>
                                <input type="date" name="appointment_date" min="<?php echo $min_date; ?>" 
                                       value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Preferred Time <span class="required">*</span></label>
                                <input type="time" name="appointment_time" 
                                       value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? '10:00'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Appointment Type</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="type" value="appointment" <?php echo (!isset($_POST['type']) || $_POST['type'] == 'appointment') ? 'checked' : ''; ?>>
                                    <span>Test Drive</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="type" value="car_reservation" <?php echo (isset($_POST['type']) && $_POST['type'] == 'car_reservation') ? 'checked' : ''; ?>>
                                    <span>Vehicle Inquiry</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Additional Message (Optional)</label>
                            <textarea name="message" rows="4" placeholder="Any specific questions or requirements?"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-calendar-check"></i> Submit Reservation
                        </button>
                        
                        <p style="text-align: center; margin-top: 16px; font-size: 0.75rem; color: var(--text-muted);">
                            <i class="fas fa-shield-alt"></i> Your information is secure and will only be used to contact you about this reservation.
                        </p>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- No Car Selected -->
            <div class="no-car-card">
                <i class="fas fa-car-side"></i>
                <h2>No Vehicle Selected</h2>
                <p>Please select a vehicle to make a reservation or schedule a test drive.</p>
                <a href="available_units.php" class="btn-primary">
                    <i class="fas fa-car"></i> Browse Available Vehicles
                </a>
                <a href="low_dp_units.php" class="btn-primary" style="background: transparent; border: 1px solid var(--primary-red); margin-left: 12px;">
                    <i class="fas fa-fire"></i> View Low DP Offers
                </a>
            </div>
        <?php endif; ?>
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

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
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
        
        // Auto-dismiss alert after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Form validation before submit
        document.getElementById('reservationForm')?.addEventListener('submit', function(e) {
            const date = document.querySelector('input[name="appointment_date"]').value;
            const time = document.querySelector('input[name="appointment_time"]').value;
            
            if (date && time) {
                const selectedDateTime = new Date(date + ' ' + time);
                const now = new Date();
                
                if (selectedDateTime < now) {
                    e.preventDefault();
                    alert('Please select a future date and time for your appointment.');
                }
            }
        });
        
        // Set minimum time for today's appointments
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
    </script>
</body>
</html>