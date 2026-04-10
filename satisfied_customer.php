<?php
require_once 'include/config.php';

// Get all satisfied customers
try {
    $stmt = $pdo->prepare("
        SELECT sc.*, c.car_name 
        FROM satisfied_customers sc
        LEFT JOIN cars c ON sc.car_id = c.car_id
        WHERE sc.rating >= 1
        ORDER BY sc.customer_id DESC
    ");
    $stmt->execute();
    $satisfiedCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Satisfied customers query failed: " . $e->getMessage());
    $satisfiedCustomers = [];
}

// Get statistics
try {
    $totalTestimonials = count($satisfiedCustomers);
    
    // Calculate average rating
    $avgRatingStmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM satisfied_customers");
    $avgRating = round($avgRatingStmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0, 1);
    
    // Count 5-star ratings
    $fiveStarStmt = $pdo->query("SELECT COUNT(*) as five_star FROM satisfied_customers WHERE rating = 5");
    $fiveStarCount = $fiveStarStmt->fetch(PDO::FETCH_ASSOC)['five_star'] ?? 0;
    
} catch(PDOException $e) {
    $totalTestimonials = 0;
    $avgRating = 0;
    $fiveStarCount = 0;
}

// Helper function to display star rating
function displayStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    return $stars;
}

// Helper function to truncate text
function truncateText($text, $maxLength = 150) {
    if (strlen($text) <= $maxLength) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $maxLength)) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read what our satisfied customers have to say about their experience with Driven Online Auto Sales.">
    <title>Customer Testimonials - Driven Online Auto Sales</title>
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

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.9) 100%), url('img/testimonials-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 80px 5% 60px;
            text-align: center;
            position: relative;
        }

        .page-header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 16px;
            animation: fadeInUp 0.6s ease;
        }

        .page-header h1 span {
            color: var(--primary-red);
        }

        .page-header p {
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease 0.1s backwards;
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

        /* Stats Section */
        .stats-section {
            padding: 40px 5%;
            background: linear-gradient(135deg, #111111 0%, #0A0A0A 100%);
            border-bottom: 1px solid var(--card-border);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .stat-box {
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-red);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-rating {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 10px;
            color: #FFD700;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 5%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
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

        /* Testimonials Grid */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition-normal);
            border: 1px solid var(--card-border);
            animation: fadeInUp 0.6s ease backwards;
            animation-delay: calc(var(--index, 0) * 0.05s);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-red);
            box-shadow: var(--shadow-md);
        }

        .testimonial-header {
            padding: 25px 25px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid var(--card-border);
        }

        .testimonial-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-red);
        }

        .testimonial-avatar-placeholder {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-red-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            border: 2px solid var(--primary-red);
        }

        .testimonial-info {
            flex: 1;
        }

        .testimonial-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .testimonial-car {
            font-size: 0.75rem;
            color: var(--primary-red);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 8px;
        }

        .testimonial-rating {
            display: flex;
            gap: 3px;
            color: #FFD700;
            font-size: 0.8rem;
        }

        .testimonial-body {
            padding: 20px 25px;
        }

        .testimonial-text {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 0.9rem;
            font-style: italic;
            position: relative;
        }

        .testimonial-text::before {
            content: '"';
            font-size: 40px;
            color: var(--primary-red);
            opacity: 0.3;
            position: absolute;
            top: -15px;
            left: -10px;
            font-family: serif;
        }

        .testimonial-footer {
            padding: 15px 25px 25px;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: flex-end;
        }

        .testimonial-date {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--card-border);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 30px;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--text-secondary);
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition-fast);
            font-size: 0.85rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-red);
            border-color: var(--primary-red);
            color: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 50px;
            flex-wrap: wrap;
        }

        .page-btn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-secondary);
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--primary-red);
            border-color: var(--primary-red);
            color: white;
        }

        /* Footer */
        .footer {
            background: #050505;
            padding: 30px 5% 20px;
            text-align: center;
            border-top: 1px solid var(--card-border);
            margin-top: 60px;
        }

        .footer-copyright {
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.7rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .testimonials-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .page-header {
                padding: 60px 5% 40px;
            }
            
            .main-content {
                padding: 40px 5%;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .testimonial-header {
                padding: 20px;
            }
            
            .testimonial-body {
                padding: 15px 20px;
            }
            
            .filter-section {
                gap: 10px;
            }
            
            .filter-btn {
                padding: 6px 15px;
                font-size: 0.75rem;
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
    
    <!-- Page Header -->
    <section class="page-header">
        <h1>SATISFIED <span>CUSTOMERS</span></h1>
        <p>Real stories from real people who found their dream cars with us</p>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-container">
            <div class="stat-box fade-up">
                <div class="stat-number"><?php echo $totalTestimonials; ?>+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-box fade-up">
                <div class="stat-number"><?php echo $fiveStarCount; ?></div>
                <div class="stat-label">5-Star Reviews</div>
            </div>
            <div class="stat-box fade-up">
                <div class="stat-number"><?php echo $avgRating; ?></div>
                <div class="stat-label">Average Rating</div>
                <div class="stat-rating"><?php echo displayStars(round($avgRating)); ?></div>
            </div>
            <div class="stat-box fade-up">
                <div class="stat-number">99%</div>
                <div class="stat-label">Would Recommend</div>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="section-header">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">WHAT OUR CUSTOMERS SAY</h2>
            <p class="section-subtitle">Don't just take our word for it - hear from our happy customers</p>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <button class="filter-btn active" data-filter="all">All Reviews</button>
            <button class="filter-btn" data-filter="5">★★★★★ (5 Star)</button>
            <button class="filter-btn" data-filter="4">★★★★☆ (4 Star)</button>
            <button class="filter-btn" data-filter="3">★★★☆☆ (3 Star)</button>
        </div>
        
        <!-- Testimonials Grid -->
        <div class="testimonials-grid" id="testimonialsGrid">
            <?php if (count($satisfiedCustomers) > 0): ?>
                <?php foreach ($satisfiedCustomers as $index => $customer): 
                    $imagePath = !empty($customer['image']) && file_exists($customer['image']) 
                        ? htmlspecialchars($customer['image']) 
                        : null;
                ?>
                    <div class="testimonial-card fade-up" data-rating="<?php echo $customer['rating']; ?>" style="--index: <?php echo $index; ?>">
                        <div class="testimonial-header">
                            <?php if ($imagePath): ?>
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($customer['customer_name']); ?>" class="testimonial-avatar">
                            <?php else: ?>
                                <div class="testimonial-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="testimonial-info">
                                <div class="testimonial-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                <?php if (!empty($customer['car_name'])): ?>
                                    <div class="testimonial-car">
                                        <i class="fas fa-car"></i> <?php echo htmlspecialchars($customer['car_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="testimonial-rating">
                                    <?php echo displayStars($customer['rating']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-body">
                            <div class="testimonial-text">
                                <?php echo nl2br(htmlspecialchars($customer['description'])); ?>
                            </div>
                        </div>
                        <div class="testimonial-footer">
                            <div class="testimonial-date">
                                <i class="fas fa-calendar-alt"></i> Verified Buyer
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-smile-wink"></i>
                    <h3>No Testimonials Yet</h3>
                    <p>Be the first to share your experience with us!</p>
                    <a href="contact.php" class="btn-primary">Share Your Story</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (count($satisfiedCustomers) > 6): ?>
        <div class="pagination" id="pagination">
            <button class="page-btn" data-page="prev">Previous</button>
            <button class="page-btn active" data-page="1">1</button>
            <button class="page-btn" data-page="2">2</button>
            <button class="page-btn" data-page="3">3</button>
            <button class="page-btn" data-page="next">Next</button>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-copyright">
            <p>© <?php echo date('Y'); ?> Driven Online Auto Sales. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Fade-up animation observer
        const fadeElements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.1 });
        
        fadeElements.forEach(el => observer.observe(el));
        
        // Filter functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const testimonials = document.querySelectorAll('.testimonial-card');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const filterValue = btn.getAttribute('data-filter');
                
                testimonials.forEach(testimonial => {
                    if (filterValue === 'all') {
                        testimonial.style.display = '';
                    } else {
                        const rating = testimonial.getAttribute('data-rating');
                        if (rating === filterValue) {
                            testimonial.style.display = '';
                        } else {
                            testimonial.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // Simple pagination (shows first 6 by default)
        let currentPage = 1;
        const itemsPerPage = 6;
        const totalItems = testimonials.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        function showPage(page) {
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            
            testimonials.forEach((item, index) => {
                if (index >= start && index < end && item.style.display !== 'none') {
                    item.style.display = '';
                } else if (item.style.display !== 'none') {
                    item.style.display = 'none';
                }
            });
            
            // Update active page button
            document.querySelectorAll('.page-btn[data-page]').forEach(btn => {
                if (btn.getAttribute('data-page') == page) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }
        
        // Pagination controls
        const paginationContainer = document.getElementById('pagination');
        if (paginationContainer) {
            const prevBtn = paginationContainer.querySelector('[data-page="prev"]');
            const nextBtn = paginationContainer.querySelector('[data-page="next"]');
            const pageBtns = paginationContainer.querySelectorAll('[data-page]');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        showPage(currentPage);
                    }
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        showPage(currentPage);
                    }
                });
            }
            
            pageBtns.forEach(btn => {
                const pageNum = btn.getAttribute('data-page');
                if (pageNum !== 'prev' && pageNum !== 'next') {
                    btn.addEventListener('click', () => {
                        currentPage = parseInt(pageNum);
                        showPage(currentPage);
                    });
                }
            });
            
            // Initialize
            showPage(1);
        }
        
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