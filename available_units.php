<?php
require_once 'include/config.php';

// Get category from URL
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get all categories for filter buttons
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order")->fetchAll();

// Build query for cars — exclude low DP units
$sql = "SELECT c.*, cat.category_name 
        FROM cars c 
        LEFT JOIN categories cat ON c.category_id = cat.category_id 
        WHERE c.is_low_dp = 0";

$params = [];

if ($category_id > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (c.car_name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Get current category name and image
$current_category = '';
$current_category_image = '';
if ($category_id > 0) {
    $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $cat = $stmt->fetch();
    $current_category = $cat ? $cat['category_name'] : '';
    
    // Map category name to image file
    $category_image_map = [
        'Sedan' => 'Sedan.png',
        'Hatchback' => 'Hatchback.png',
        'SUV' => 'SUV.png',
        'Crossover' => 'Crossover.png',
        'Luxury Cars' => 'Luxury_cars.png',
        'Pick-Up' => 'Pick-Up.png',
        'Van' => 'Van.png',
        'Commercial Vehicle' => 'Commercial_vehicle.png'
    ];
    
    if (isset($category_image_map[$current_category])) {
        $current_category_image = 'img/units/' . $category_image_map[$current_category];
    }
}

// Function to get category image
function getCategoryImage($category_name) {
    $image_map = [
        'Sedan' => 'Sedan.png',
        'Hatchback' => 'Hatchback.png',
        'SUV' => 'SUV.png',
        'Crossover' => 'Crossover.png',
        'Luxury Cars' => 'Luxury_cars.png',
        'Pick-Up' => 'Pick-Up.png',
        'Van' => 'Van.png',
        'Commercial Vehicle' => 'Commercial_vehicle.png'
    ];
    
    if (isset($image_map[$category_name])) {
        return 'img/units/' . $image_map[$category_name];
    }
    return null;
}

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

$vehicleCount = count($cars);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Available vehicles at Driven Online Auto Sales. Find your perfect ride from our wide selection of quality vehicles. Browse sedans, SUVs, trucks, and more.">
    <title>Available Units - Find Your Perfect Ride | Driven Auto Sales</title>
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

        .page-header h1 span {
            color: white;
            position: relative;
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

        /* Category Banner */
        .category-banner {
            background: var(--card-bg);
            padding: 40px 40px;
            margin-bottom: 50px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
        }

        .category-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(229, 9, 20, 0.05) 0%, transparent 100%);
            pointer-events: none;
        }

        .category-banner-image {
            width: 140px;
            height: 140px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
        }

        .category-banner-content {
            flex: 1;
        }

        .category-banner-content h2 {
            font-size: 2rem;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .category-banner-content h2 span {
            color: var(--primary-red);
        }

        .category-banner-content p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 5%;
        }

        /* Category Filter */
        .category-filter {
            margin-bottom: 50px;
        }

        .filter-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            letter-spacing: 2px;
        }

        .filter-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 18px 24px;
            background: var(--card-bg);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 20px;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
            min-width: 110px;
            cursor: pointer;
        }

        .filter-btn img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .filter-btn i {
            font-size: 48px;
            transition: transform 0.3s;
        }

        .filter-btn span {
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .filter-btn:hover {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .filter-btn:hover img {
            transform: scale(1.05);
            filter: brightness(0) invert(1);
        }

        .filter-btn:hover i {
            transform: scale(1.05);
        }

        .filter-btn.active {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.4);
        }

        .filter-btn.active img {
            filter: brightness(0) invert(1);
        }

        /* Search Section */
        .search-section {
            margin-bottom: 40px;
        }

        .search-form {
            display: flex;
            gap: 12px;
            max-width: 500px;
            margin: 0 auto;
        }

        .search-form input {
            flex: 1;
            padding: 14px 22px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 50px;
            color: white;
            font-size: 0.9375rem;
            transition: var(--transition-fast);
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .search-form button {
            padding: 14px 28px;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition-normal);
            font-weight: 600;
        }

        .search-form button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        /* Results Info */
        .results-info {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-info h2 {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .results-info h2 span {
            color: var(--primary-red);
            font-weight: 700;
        }

        .clear-filter {
            color: var(--primary-red);
            text-decoration: none;
            transition: var(--transition-fast);
            font-weight: 500;
        }

        .clear-filter:hover {
            text-decoration: underline;
        }

        /* Cars Grid */
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
        }

        .car-card {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
            position: relative;
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .car-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-red);
            box-shadow: var(--shadow-lg);
        }

        .car-image-wrapper {
            position: relative;
            height: 240px;
            overflow: hidden;
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

        .car-price {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.9375rem;
            color: var(--primary-red);
        }

        .low-dp-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--gradient-fire);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
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

        .car-info h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .car-details {
            display: flex;
            justify-content: space-between;
            margin: 16px 0;
            color: var(--text-secondary);
            font-size: 0.8125rem;
        }

        .car-details span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .monthly-price {
            color: var(--primary-red);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .view-btn {
            width: 100%;
            padding: 14px;
            background: transparent;
            color: white;
            border: 1px solid var(--card-border);
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: block;
            text-align: center;
            font-weight: 600;
        }

        .view-btn:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
        }

        .view-btn i {
            margin-left: 8px;
            transition: transform 0.2s ease;
        }

        .view-btn:hover i {
            transform: translateX(4px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--card-bg);
            border-radius: 32px;
            border: 1px solid var(--card-border);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--primary-red);
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .empty-state a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
        }

        .empty-state a:hover {
            text-decoration: underline;
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
        @media (max-width: 768px) {
            .page-header {
                padding: 50px 20px 40px;
            }
            
            .main-container {
                padding: 30px 20px;
            }
            
            .cars-grid {
                grid-template-columns: 1fr;
            }
            
            .results-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-btn {
                min-width: 85px;
                padding: 12px 16px;
            }
            
            .filter-btn img,
            .filter-btn i {
                width: 40px;
                height: 40px;
                font-size: 32px;
            }
            
            .filter-btn span {
                font-size: 0.75rem;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .category-banner {
                padding: 30px 20px;
                text-align: center;
                justify-content: center;
            }

            .category-banner-content h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .filter-buttons {
                gap: 12px;
            }
            
            .filter-btn {
                min-width: 75px;
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/nav.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-car"></i> AVAILABLE <span>UNITS</span></h1>
        <p>Find your perfect ride from our wide selection of quality vehicles</p>
    </div>
    
    <div class="main-container">
        <!-- Category Banner (when category selected) -->
        <?php if ($category_id > 0 && $current_category_image): ?>
        <div class="category-banner">
            <div class="category-banner-image" style="background-image: url('<?php echo $current_category_image; ?>');"></div>
            <div class="category-banner-content">
                <h2><span><?php echo htmlspecialchars($current_category); ?></span> Vehicles</h2>
                <p>Explore our collection of premium <?php echo htmlspecialchars($current_category); ?> vehicles. Quality assured, ready for you.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Category Filter -->
        <div class="category-filter">
            <div class="filter-title">
                <i class="fas fa-filter"></i> FILTER BY CATEGORY
            </div>
            <div class="filter-buttons">
                <a href="available_units.php" class="filter-btn <?php echo $category_id == 0 ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>All Vehicles</span>
                </a>
                <?php foreach ($categories as $cat): 
                    $catImage = getCategoryImage($cat['category_name']);
                ?>
                    <a href="?category=<?php echo $cat['category_id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $category_id == $cat['category_id'] ? 'active' : ''; ?>">
                        <?php if ($catImage && file_exists($catImage)): ?>
                            <img src="<?php echo $catImage; ?>" alt="<?php echo htmlspecialchars($cat['category_name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-car"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Search vehicles by name or description..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <!-- Results Info -->
        <div class="results-info">
            <h2>
                <?php if (!empty($search)): ?>
                    <i class="fas fa-search"></i> Search results for "<span><?php echo htmlspecialchars($search); ?></span>"
                <?php elseif ($current_category): ?>
                    <i class="fas fa-tag"></i> <span><?php echo htmlspecialchars($current_category); ?></span> Vehicles
                <?php else: ?>
                    <i class="fas fa-car"></i> All <span>Vehicles</span>
                <?php endif; ?>
                <span style="color: var(--primary-red);"> (<?php echo $vehicleCount; ?> vehicles)</span>
            </h2>
            <?php if ($category_id > 0 || !empty($search)): ?>
                <a href="available_units.php" class="clear-filter"><i class="fas fa-times-circle"></i> Clear Filters</a>
            <?php endif; ?>
        </div>
        
        <!-- Cars Grid -->
        <?php if ($vehicleCount > 0): ?>
            <div class="cars-grid">
                <?php foreach ($cars as $index => $car): 
                    $images = safeJsonDecode($car['images']);
                    $firstImage = !empty($images) ? htmlspecialchars($images[0]) : 'placeholder.jpg';
                    $imagePath = "uploads/" . $firstImage;
                    
                    $terms = intval($car['terms']);
                    $termsDisplay = $terms > 0 ? $terms . ' mos' : $car['terms'];
                ?>
                    <div class="car-card" style="animation-delay: <?php echo $index * 0.05; ?>s">
                        <div class="car-image-wrapper">
                            <div class="car-image" style="background-image: url('<?php echo $imagePath; ?>'); background-size: cover; background-position: center;">
                                <div class="car-price"><?php echo formatPrice($car['price']); ?></div>
                            </div>
                        </div>
                        <div class="car-info">
                            <h3><?php echo htmlspecialchars($car['car_name']); ?></h3>
                            <div class="car-details">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($termsDisplay); ?></span>
                                <span><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['mileage']); ?> km</span>
                                <span><i class="fas fa-car"></i> <?php echo htmlspecialchars($car['category_name'] ?? 'N/A'); ?></span>
                            </div>
                            
                            <div class="monthly-price">
                                <i class="fas fa-calendar-week"></i> <?php echo formatPrice($car['monthly']); ?>/mo for <?php echo htmlspecialchars($termsDisplay); ?>
                            </div>
                            <a href="car_details.php?id=<?php echo $car['car_id']; ?>" class="view-btn">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-car-side"></i>
                <h3>No Vehicles Found</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        We couldn't find any vehicles matching "<strong><?php echo htmlspecialchars($search); ?></strong>".
                    <?php elseif ($current_category): ?>
                        We don't have any vehicles in <strong><?php echo htmlspecialchars($current_category); ?></strong> at the moment.
                    <?php else: ?>
                        We don't have any vehicles available right now.
                    <?php endif; ?>
                </p>
                <p>Check back soon for new arrivals or browse our low down payment offers!</p>
                <a href="low_dp_units.php"><i class="fas fa-fire"></i> View Low DP Offers</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
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
        
        // Add fade-in animation to car cards as they load
        const carCards = document.querySelectorAll('.car-card');
        carCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    </script>
</body>
</html>