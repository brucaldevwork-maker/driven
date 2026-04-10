<?php
require_once 'include/config.php';

// Get featured cars for display with error handling
try {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.category_name 
        FROM cars c 
        LEFT JOIN categories cat ON c.category_id = cat.category_id 
        ORDER BY c.created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', 6, PDO::PARAM_INT);
    $stmt->execute();
    $featuredCars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Featured cars query failed: " . $e->getMessage());
    $featuredCars = [];
}

// Get satisfied customers - 6 most recent entries
try {
    $customersStmt = $pdo->prepare("
        SELECT customer_id, customer_name, image, description, rating 
        FROM satisfied_customers 
        ORDER BY customer_id DESC 
        LIMIT :limit
    ");
    $customersStmt->bindValue(':limit', 6, PDO::PARAM_INT);
    $customersStmt->execute();
    $satisfiedCustomers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Satisfied customers query failed: " . $e->getMessage());
    $satisfiedCustomers = [];
}

// Get statistics for dynamic display
try {
    $statsStmt = $pdo->query("SELECT COUNT(*) as total_cars FROM cars");
    $totalCars = $statsStmt->fetch(PDO::FETCH_ASSOC)['total_cars'] ?? 0;
    
    $customersCountStmt = $pdo->query("SELECT COUNT(*) as happy_customers FROM reservations WHERE status = 'completed'");
    $happyCustomers = $customersCountStmt->fetch(PDO::FETCH_ASSOC)['happy_customers'] ?? 124;
} catch(PDOException $e) {
    $totalCars = 0;
    $happyCustomers = 124;
}

// Helper functions
function formatPrice($price) {
    return '₱ ' . number_format(floatval($price), 0, '.', ',');
}

function safeJsonDecode($json, $default = []) {
    if (empty($json)) return $default;
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

function getCarBadge($price, $mileage) {
    if ($price < 500000 && $mileage < 30000) {
        return '<span class="badge badge-excellent">Great Value</span>';
    } elseif ($mileage < 50000) {
        return '<span class="badge badge-good">Low Mileage</span>';
    }
    return '<span class="badge badge-standard">Quality Checked</span>';
}

// Helper function to display star rating
function displayStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    return $stars;
}

// Helper function to truncate testimonial text
function truncateText($text, $maxLength = 80) {
    if (strlen($text) <= $maxLength) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $maxLength)) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Driven Online Auto Sales - Your trusted partner for quality pre-owned vehicles in the Philippines.">
    <title>Driven Online Auto Sales - Your Dream Car Awaits</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <!-- Tutorial CSS -->
    <link rel="stylesheet" href="css/tutorial.css">
    
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
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
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
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: var(--card-bg);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary-red);
            border-radius: 3px;
        }

        /* Hero Section */
        .hero {
            min-height: 90vh;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.85) 100%), url('img/Background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-content h1 {
            font-size: clamp(2rem, 7vw, 4rem);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .hero-content h1 span {
            color: var(--primary-red);
        }

        .hero-content p {
            font-size: clamp(0.875rem, 4vw, 1.125rem);
            color: var(--text-secondary);
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary, .btn-tutorial, .btn-call-us {
            padding: 12px 28px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: none;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid var(--primary-red);
        }

        .btn-secondary:hover {
            background: rgba(229, 9, 20, 0.1);
        }

        .btn-tutorial {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .btn-tutorial:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        .btn-call-us {
            background: transparent;
            color: white;
            border: 2px solid #28a745;
        }

        .btn-call-us:hover {
            background: #28a745;
            border-color: #28a745;
        }

        /* Section Styles */
        .section {
            padding: 60px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-tag {
            color: var(--primary-red);
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .section-title {
            font-size: clamp(1.5rem, 5vw, 2.25rem);
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #fff 0%, #ccc 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Offers Grid */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .offer-card {
            background: var(--card-bg);
            padding: 30px 20px;
            border-radius: 20px;
            text-align: center;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
        }

        .offer-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
        }

        .offer-icon {
            font-size: 40px;
            color: var(--primary-red);
            margin-bottom: 16px;
        }

        .offer-card h3 {
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        .offer-card p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* Mission & Vision */
        .mv-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }

        .mv-image {
            background: linear-gradient(135deg, rgba(229,9,20,0.2) 0%, rgba(0,0,0,0.6) 100%), url('img/Our_Mission&Vission.png');
            background-size: cover;
            background-position: center;
            min-height: 350px;
        }

        .mv-content {
            padding: 30px;
        }

        .mv-content h2 {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .mv-content .subtitle {
            color: var(--primary-red);
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .mission-box, .vision-box {
            margin-bottom: 20px;
        }

        .mission-box h3, .vision-box h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mission-box p, .vision-box p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* Cars Grid */
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .car-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
        }

        .car-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
        }

        .car-image-wrapper {
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .car-image {
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }

        .car-card:hover .car-image {
            transform: scale(1.05);
        }

        .car-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-excellent { background: #10B981; color: white; }
        .badge-good { background: #F59E0B; color: white; }
        .badge-standard { background: #6B7280; color: white; }

        .car-price {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--primary-red);
        }

        .low-dp-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #FFD700;
            color: #000;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.65rem;
            font-weight: 700;
            z-index: 2;
        }

        .car-info {
            padding: 16px;
        }

        .car-info h3 {
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .car-details {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .car-details span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .car-monthly {
            color: var(--primary-red);
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .view-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: white;
            border: 1px solid var(--card-border);
            border-radius: 30px;
            text-decoration: none;
            display: block;
            text-align: center;
            font-size: 0.85rem;
            transition: var(--transition-fast);
        }

        .view-btn:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #111111 0%, #0A0A0A 100%);
            padding: 40px 5%;
            border-top: 1px solid var(--card-border);
            border-bottom: 1px solid var(--card-border);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        .stat-card p {
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        /* Customers Grid */
        .customers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .customer-card {
            background: var(--card-bg);
            border-radius: 16px;
            text-align: center;
            padding: 20px 16px;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
        }

        .customer-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-red);
        }

        .customer-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 12px;
            border: 2px solid var(--primary-red);
        }

        .customer-card h4 {
            font-size: 0.95rem;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .customer-card .stars {
            color: #FFD700;
            font-size: 0.75rem;
            margin-bottom: 8px;
        }

        .customer-card .testimonial-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            line-height: 1.4;
            font-style: italic;
        }

        /* Empty state for customers */
        .customers-empty {
            text-align: center;
            grid-column: 1/-1;
            padding: 40px;
            color: var(--text-secondary);
        }

        /* CTA Section */
        .cta-section {
            background: var(--gradient-1);
            padding: 50px 5%;
            text-align: center;
            border-radius: 24px;
            margin: 0 5% 50px;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
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

        .cta-section h2 {
            font-size: clamp(1.3rem, 5vw, 2rem);
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .cta-section p {
            margin-bottom: 20px;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .cta-btn {
            background: white;
            color: var(--primary-red);
            padding: 10px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition-normal);
            display: inline-block;
            margin: 0 8px;
            position: relative;
            z-index: 1;
            border: none;
            cursor: pointer;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Call Us Modal Styles */
        .call-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .call-modal-content {
            background-color: var(--card-bg);
            margin: 15% auto;
            width: 90%;
            max-width: 450px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: slideIn 0.3s ease;
            border: 1px solid var(--card-border);
        }

        .call-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--card-border);
        }

        .call-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .call-modal-header h2 i {
            color: #28a745;
            margin-right: 10px;
        }

        .call-close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .call-close-modal:hover {
            color: var(--primary-red);
        }

        .call-modal-body {
            padding: 30px 24px;
            text-align: center;
        }

        .call-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .call-message {
            font-size: 1rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .call-number {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            margin: 10px 0 0;
        }

        .call-number i {
            margin-right: 8px;
        }

        .call-modal-footer {
            display: flex;
            gap: 12px;
            padding: 16px 24px 24px;
            border-top: 1px solid var(--card-border);
        }

        .btn-contact, .btn-close-call {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 40px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }

        .btn-contact {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-close-call {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--card-border);
        }

        .btn-close-call:hover {
            background: rgba(229, 9, 20, 0.1);
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer */
        .footer {
            background: #050505;
            padding: 30px 5% 20px;
            text-align: center;
            border-top: 1px solid var(--card-border);
        }

        .footer-copyright {
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.7rem;
        }

        .empty-state {
            text-align: center;
            grid-column: 1/-1;
            padding: 40px;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                min-height: 80vh;
            }
            
            .hero-buttons {
                gap: 10px;
            }
            
            .btn-primary, .btn-secondary, .btn-tutorial, .btn-call-us {
                padding: 10px 20px;
                font-size: 0.8rem;
                width: 160px;
            }
            
            .section {
                padding: 40px 5%;
            }
            
            .mv-container {
                grid-template-columns: 1fr;
            }
            
            .mv-image {
                min-height: 200px;
            }
            
            .mv-content {
                padding: 20px;
            }
            
            .cta-section {
                margin: 0 20px 40px;
                padding: 40px 20px;
            }
            
            .cta-btn {
                padding: 8px 20px;
                font-size: 0.8rem;
                margin: 5px;
                display: inline-block;
            }
            
            .cars-grid {
                grid-template-columns: 1fr;
            }
            
            .customers-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            
            .call-modal-content {
                margin: 30% auto;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .customers-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .customer-card {
                padding: 12px;
            }
            
            .customer-card img {
                width: 60px;
                height: 60px;
            }
            
            .customer-card h4 {
                font-size: 0.8rem;
            }
        }

        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <?php include 'include/nav.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>DRIVEN <span>ONLINE AUTO SALES</span></h1>
            <p>RIGHT PEOPLE · RIGHT PRICE · RIGHT CAR</p>
            <div class="hero-buttons">
                <a href="available_units.php" class="btn-primary">Browse Cars</a>
                <button class="btn-call-us" id="openCallModalBtn">
                    <i class="fas fa-phone-alt"></i> Call Us Now
                </button>
            </div>
        </div>
    </section>
    
    <!-- What We Offer Section -->
    <section class="section">
        <div class="section-header">
            <span class="section-tag">Why Choose Us</span>
            <h2 class="section-title">WHAT WE OFFER</h2>
            <p class="section-subtitle">We provide opportunities for every Filipino to own their dream vehicle.</p>
        </div>
        
        <div class="offers-grid">
            <div class="offer-card fade-up">
                <div class="offer-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <h3>Easy Financing Options</h3>
                <p>Flexible financing programs for all credit histories.</p>
            </div>
            <div class="offer-card fade-up">
                <div class="offer-icon"><i class="fas fa-check-circle"></i></div>
                <h3>Quality Pre-Owned Vehicles</h3>
                <p>Each unit is carefully inspected for reliability.</p>
            </div>
            <div class="offer-card fade-up">
                <div class="offer-icon"><i class="fas fa-bolt"></i></div>
                <h3>Fast & Hassle Free Approval</h3>
                <p>Get your car sooner with our simplified process.</p>
            </div>
            <div class="offer-card fade-up">
                <div class="offer-icon"><i class="fas fa-heart"></i></div>
                <h3>Family First Service</h3>
                <p>Transparency, care, and genuine support.</p>
            </div>
        </div>
    </section>
    
    <!-- Mission & Vision Section -->
    <section class="section">
        <div class="mv-container fade-up">
            <div class="mv-image"></div>
            <div class="mv-content">
                <h2>OUR VISION & MISSION</h2>
                <div class="subtitle">Driven by Purpose</div>
                <div class="mission-box">
                    <h3><i class="fas fa-bullseye"></i> Mission</h3>
                    <p>To offer quality vehicles and provide honest, transparent service that empowers every Filipino to drive their dreams home.</p>
                </div>
                <div class="vision-box">
                    <h3><i class="fas fa-eye"></i> Vision</h3>
                    <p>To be the most trusted automotive partner, making car ownership possible for everyone.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card fade-up">
                <h3><?php echo number_format($totalCars); ?>+</h3>
                <p>AVAILABLE VEHICLES</p>
            </div>
            <div class="stat-card fade-up">
                <h3><?php echo number_format($happyCustomers); ?>+</h3>
                <p>HAPPY CUSTOMERS</p>
            </div>
            <div class="stat-card fade-up">
                <h3>99%</h3>
                <p>APPROVAL RATE</p>
            </div>
            <div class="stat-card fade-up">
                <h3>24/7</h3>
                <p>CUSTOMER SUPPORT</p>
            </div>
        </div>
    </section>
    
    <!-- Featured Cars Section -->
    <section class="section">
        <div class="section-header">
            <span class="section-tag">Latest Arrivals</span>
            <h2 class="section-title">FEATURED VEHICLES</h2>
            <p class="section-subtitle">Check out our newest additions</p>
        </div>
        
        <div class="cars-grid">
            <?php if (count($featuredCars) > 0): ?>
                <?php foreach ($featuredCars as $index => $car): 
                    $images = safeJsonDecode($car['images']);
                    $firstImage = !empty($images) ? htmlspecialchars($images[0]) : 'placeholder.jpg';
                    $terms = intval($car['terms']);
                    $termsDisplay = $terms > 0 ? $terms . ' mos' : 'N/A';
                ?>
                    <div class="car-card fade-up">
                        <div class="car-image-wrapper">
                            <div class="car-image" style="background-image: url('uploads/<?php echo $firstImage; ?>');">
                                <div class="car-badge"><?php echo getCarBadge($car['price'], $car['mileage']); ?></div>
                                <?php if ($car['is_low_dp'] == 1): ?>
                                    <div class="low-dp-badge"><i class="fas fa-percent"></i> LOW DP</div>
                                <?php endif; ?>
                                <div class="car-price"><?php echo formatPrice($car['price']); ?></div>
                            </div>
                        </div>
                        <div class="car-info">
                            <h3><?php echo htmlspecialchars($car['car_name']); ?></h3>
                            <div class="car-details">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo $termsDisplay; ?></span>
                                <span><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['mileage']); ?> km</span>
                            </div>
                            <div class="car-monthly"><?php echo formatPrice($car['monthly']); ?><span>/mo</span></div>
                            <a href="car_details.php?id=<?php echo $car['car_id']; ?>" class="view-btn">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-car-side"></i>
                    <p>No cars available yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Satisfied Customers Section -->
    <section class="section">
        <div class="section-header">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">SATISFIED CUSTOMERS</h2>
            <p class="section-subtitle">Driven by Trust, Fueled by Happiness</p>
        </div>
        
        <div class="customers-grid">
            <?php if (count($satisfiedCustomers) > 0): ?>
                <?php foreach ($satisfiedCustomers as $index => $customer): ?>
                    <div class="customer-card fade-up">
                        <?php 
                            // The image path is already stored as 'uploads/customers/filename.jpg'
                            // Just use it directly since index.php is in the root directory
                            $imagePath = !empty($customer['image']) ? htmlspecialchars($customer['image']) : 'img/default_avatar.jpg';
                            
                            // Check if the image file actually exists
                            if (!file_exists($imagePath) && $imagePath !== 'img/default_avatar.jpg') {
                                $imagePath = 'img/default_avatar.jpg';
                            }
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                        <h4><?php echo htmlspecialchars($customer['customer_name']); ?></h4>
                        <div class="stars"><?php echo displayStars($customer['rating']); ?></div>
                        <div class="testimonial-text">"<?php echo truncateText($customer['description'], 70); ?>"</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="customers-empty">
                    <i class="fas fa-smile-wink"></i>
                    <p>No testimonials yet. Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <h2>Ready to Drive Your Dream Car?</h2>
        <p>Get pre-approved today and drive home your dream car!</p>
        <a href="available_units.php" class="cta-btn">Browse Available Units</a>
        <button id="ctaCallModalBtn" class="cta-btn" style="background: transparent; border: 2px solid white; color: white;">
            <i class="fas fa-phone-alt"></i> Call Us Now
        </button>
    </section>
    
    <!-- Call Us Modal -->
    <div id="callUsModal" class="call-modal">
        <div class="call-modal-content">
            <div class="call-modal-header">
                <h2>
                    <i class="fas fa-phone-alt"></i>
                    Call Us Now
                </h2>
                <span class="call-close-modal" onclick="closeCallUsModal()">&times;</span>
            </div>
            
            <div class="call-modal-body">
                <div class="call-us-content">
                    <i class="fas fa-headset call-icon"></i>
                    <p class="call-message">Need assistance? Our team is ready to help you with your inquiry or reservation.</p>
                    <p class="call-number"><i class="fas fa-phone"></i> +1 (800) 123-4567</p>
                </div>
            </div>
            
            <div class="call-modal-footer">
                <button class="btn-contact" onclick="window.location.href='contact.php'">
                    <i class="fas fa-envelope"></i> Go to Contact Page
                </button>
                <button class="btn-close-call" onclick="closeCallUsModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-copyright">
            <p>© <?php echo date('Y'); ?> Driven Online Auto Sales. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
        // Call Us Modal Functions
        const callModal = document.getElementById('callUsModal');
        
        function openCallUsModal() {
            callModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeCallUsModal() {
            callModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === callModal) {
                closeCallUsModal();
            }
        }
        
        // Open modal buttons
        document.getElementById('openCallModalBtn').addEventListener('click', openCallUsModal);
        document.getElementById('ctaCallModalBtn').addEventListener('click', openCallUsModal);
        
        // Fade-up animation observer
        const fadeElements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.1 });
        
        fadeElements.forEach(el => observer.observe(el));
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                navbar.style.background = window.scrollY > 100 ? '#0A0A0A' : 'rgba(10, 10, 10, 0.95)';
                navbar.style.backdropFilter = window.scrollY > 100 ? 'blur(0px)' : 'blur(10px)';
            }
        });
    </script>
</body>
</html>