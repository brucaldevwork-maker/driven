<?php
require_once 'include/config.php';

// Check if database connection exists
if (!isset($pdo)) {
    die("Database connection error. Please try again later.");
}

// Helper function to format phone number
function formatPhoneNumber($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format Philippine number
    if (strlen($phone) == 11) {
        return '+63 ' . substr($phone, 1, 3) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 4);
    } elseif (strlen($phone) == 10) {
        return '+63 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 4);
    }
    return $phone;
}

// Staff Information with Facebook Links
$staff_members = [
    [
        'name' => 'Edward Miguel',
        'position' => 'Auto Dealer',
        'phone' => '09626902284',
        'email' => 'edward.miguel@drivenauto.com',
        'image' => 'Edward Miguel_Auto Dealer.png',
        'description' => 'Expert in vehicle sourcing and pricing. Available for inquiries about vehicle availability and pricing.',
        'facebook' => 'https://www.facebook.com/psalms08',
        'order' => 1
    ],
    [
        'name' => 'John Cruz',
        'position' => 'Admin',
        'phone' => '09161738095',
        'email' => 'john.cruz@drivenauto.com',
        'image' => 'John Cruz_admin.png',
        'description' => 'Handles administrative concerns, documentation, and account management.',
        'facebook' => 'https://www.facebook.com/johnpauljaycruz?_rdc=1&_rdr#',
        'order' => 2
    ],
    [
        'name' => 'Jude Prejido',
        'position' => 'Sales Consultant',
        'phone' => '09157990806',
        'email' => 'jude.prejido@drivenauto.com',
        'image' => 'Jude Prejido.png',
        'description' => 'Specializes in vehicle financing options and loan assistance.',
        'facebook' => 'https://www.facebook.com/prejido.rush',
        'order' => 3
    ],
    [
        'name' => 'Verwin Canega',
        'position' => 'Auto Dealer',
        'phone' => '09454260948',
        'email' => 'verwin.canega@drivenauto.com',
        'image' => 'Verwin Canega_auto Dealer.png',
        'description' => 'Expert in vehicle trade-ins and special offers.',
        'facebook' => 'https://www.facebook.com/verwin.canega?_rdc=1&_rdr#',
        'order' => 4
    ],
    [
        'name' => 'Welmer Palma',
        'position' => 'Auto Dealer',
        'phone' => '09564721521',
        'email' => 'welmer.palma@drivenauto.com',
        'image' => 'Welmer Palma_Auto Dealer.png',
        'description' => 'Specializes in low down payment offers and promotional deals.',
        'facebook' => 'https://www.facebook.com/driven.wlmr/?_rdc=2&_rdr#',
        'order' => 5
    ]
];

// Sort staff by order
usort($staff_members, function($a, $b) {
    return $a['order'] - $b['order'];
});

// Handle contact form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            // Insert into database using PDO
            $sql = "INSERT INTO contact_inquiries (name, email, phone, subject, message, status, created_at) 
                    VALUES (:name, :email, :phone, :subject, :message, 'new', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $message
            ]);
            
            $success_message = "Thank you for reaching out! Our team will get back to you within 24 hours.";
            
            // Clear form data
            $name = $email = $phone = $subject = $message = '';
            
        } catch (PDOException $e) {
            $error_message = "An error occurred while submitting your inquiry. Please try again later.";
            // Log error for debugging
            error_log("Contact form submission failed: " . $e->getMessage());
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
    <meta name="description" content="Contact Driven Online Auto Sales. Reach out to our team of auto dealers and sales consultants for inquiries about vehicles, financing, and appointments.">
    <title>Contact Us - Driven Auto Sales</title>
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
            --facebook-blue: #1877F2;
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
        }

        .page-header p {
            color: rgba(255,255,255,0.95);
            font-size: 1.125rem;
            position: relative;
            z-index: 1;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 5%;
        }

        /* Contact Info Cards */
        .contact-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .info-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 32px 24px;
            text-align: center;
            border: 1px solid var(--card-border);
            transition: var(--transition-normal);
            animation: fadeInUp 0.5s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
            box-shadow: var(--shadow-md);
        }

        .info-icon {
            width: 70px;
            height: 70px;
            background: rgba(229, 9, 20, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .info-icon i {
            font-size: 32px;
            color: var(--primary-red);
        }

        .info-card h3 {
            font-size: 1.25rem;
            margin-bottom: 12px;
        }

        .info-card p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-size: 0.875rem;
        }

        .info-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .info-link:hover {
            gap: 12px;
        }

        /* Social Links */
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-normal);
            color: var(--text-secondary);
            text-decoration: none;
        }

        .social-link:hover {
            transform: translateY(-3px);
        }

        .social-link.facebook:hover {
            background: var(--facebook-blue);
            color: white;
        }

        .social-link i {
            font-size: 20px;
        }

        /* Staff Section */
        .staff-section {
            margin-bottom: 60px;
        }

        .staff-section h2 {
            font-size: 1.75rem;
            margin-bottom: 40px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .staff-section h2 i {
            color: var(--primary-red);
        }

        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .staff-card {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
            animation: fadeInUp 0.5s ease;
        }

        .staff-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
            box-shadow: var(--shadow-lg);
        }

        .staff-image {
            width: 100%;
            height: 280px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .staff-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            padding: 20px;
        }

        .staff-info {
            padding: 24px;
        }

        .staff-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .staff-position {
            color: var(--primary-red);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 12px;
            display: inline-block;
        }

        .staff-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .staff-contact {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--card-border);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .contact-item i {
            width: 24px;
            color: var(--primary-red);
        }

        .contact-item:hover {
            color: var(--primary-red);
        }

        .facebook-link {
            color: var(--facebook-blue);
        }

        .facebook-link:hover {
            color: var(--facebook-blue);
            opacity: 0.8;
        }

        /* Contact Form Section */
        .contact-form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid var(--card-border);
            margin-bottom: 40px;
        }

        .form-info h2 {
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .form-info p {
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            color: var(--text-secondary);
        }

        .info-list li i {
            width: 24px;
            color: var(--primary-red);
        }

        .contact-form {
            background: var(--dark-bg);
            padding: 24px;
            border-radius: 20px;
        }

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
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: var(--transition-fast);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
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
            border: 1px solid #ff4444;
            color: #ff4444;
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Map Section */
        .map-section {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }

        .map-section iframe {
            width: 100%;
            height: 450px;
            border: none;
            display: block;
        }

        /* Footer */
        .footer {
            background: #050505;
            padding: 48px 5% 32px;
            text-align: center;
            border-top: 1px solid var(--card-border);
            margin-top: 60px;
        }

        .footer-copyright {
            padding-top: 32px;
            border-top: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.875rem;
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

        /* Responsive */
        @media (max-width: 968px) {
            .contact-form-section {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .staff-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 50px 20px 40px;
            }
            
            .main-container {
                padding: 30px 20px;
            }

            .contact-form-section {
                padding: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .staff-image {
                height: 240px;
            }
            
            .map-section iframe {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/nav.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-headset"></i> CONTACT US</h1>
        <p>We're here to help! Reach out to our team for any inquiries</p>
    </div>

    <div class="main-container">
        <!-- Contact Info Cards -->
        <div class="contact-info-grid">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3>Call Us</h3>
                <p>Monday to Saturday<br>9:00 AM - 6:00 PM</p>
                <a href="tel:+63212345678" class="info-link">
                    <i class="fas fa-phone"></i> +63 2 1234 5678
                </a>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Us</h3>
                <p>We'll respond within 24 hours</p>
                <a href="mailto:info@drivenauto.com" class="info-link">
                    <i class="fas fa-envelope"></i> info@drivenauto.com
                </a>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Visit Us</h3>
                <p>Driven Online Auto Sales<br>Quezon City, Metro Manila</p>
                <a href="https://maps.google.com/?q=driven+online+auto+sales" target="_blank" class="info-link">
                    <i class="fas fa-directions"></i> Get Directions
                </a>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Business Hours</h3>
                <p>Mon-Sat: 9:00 AM - 6:00 PM<br>Sunday: Closed</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/drivenauto" target="_blank" class="social-link facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Our Team Section -->
        <div class="staff-section">
            <h2>
                <i class="fas fa-users"></i>
                Meet Our Team
            </h2>
            <div class="staff-grid">
                <?php foreach ($staff_members as $staff): 
                    $imagePath = "img/" . $staff['image'];
                    // Check if image exists, if not use placeholder
                    if (!file_exists($imagePath)) {
                        $imagePath = "img/default-avatar.png";
                    }
                    $formattedPhone = formatPhoneNumber($staff['phone']);
                ?>
                <div class="staff-card">
                    <div class="staff-image" style="background-image: url('<?php echo $imagePath; ?>'); background-size: cover; background-position: center;">
                        <div class="staff-overlay">
                            <div class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></div>
                            <div class="staff-position"><?php echo htmlspecialchars($staff['position']); ?></div>
                        </div>
                    </div>
                    <div class="staff-info">
                        <div class="staff-description">
                            <?php echo htmlspecialchars($staff['description']); ?>
                        </div>
                        <div class="staff-contact">
                            <a href="tel:<?php echo $staff['phone']; ?>" class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo $formattedPhone; ?></span>
                            </a>
                            <a href="mailto:<?php echo $staff['email']; ?>" class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($staff['email']); ?></span>
                            </a>
                            <a href="<?php echo $staff['facebook']; ?>" target="_blank" class="contact-item facebook-link">
                                <i class="fab fa-facebook-f"></i>
                                <span>Message on Facebook</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contact Form Section -->
        <div class="contact-form-section">
            <div class="form-info">
                <h2>Send Us a Message</h2>
                <p>Have questions about our vehicles, financing options, or want to schedule a test drive? Fill out the form and our team will get back to you within 24 hours.</p>
                
                <ul class="info-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Quick response within 24 hours</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Expert assistance from our auto dealers</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Free consultation and advice</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>No obligation inquiries</span>
                    </li>
                </ul>
                
                <div class="social-links" style="justify-content: flex-start; margin-top: 30px;">
                    <a href="https://www.facebook.com/drivenauto" target="_blank" class="social-link facebook" style="background: rgba(24, 119, 242, 0.1);">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
            
            <div class="contact-form">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Subject *</label>
                            <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Message *</label>
                        <textarea name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Map Section with Updated Location -->
        <div class="map-section">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d61762.59763980253!2d121.06350807723469!3d14.646723778333202!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b982a08557a1%3A0x86df2962ea6a5dce!2sdriven%20online%20auto%20sales!5e0!3m2!1sen!2sph!4v1774276077281!5m2!1sen!2sph" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-copyright">
            <p>© <?php echo date('Y'); ?> Driven Online Auto Sales. All rights reserved.</p>
        </div>
    </footer>

    <script>
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
    </script>
</body>
</html>