<?php
// Navigation bar for frontend (no login required)
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(13, 13, 13, 0.98);
            backdrop-filter: blur(12px);
            padding: 12px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(229, 9, 20, 0.3);
            animation: slideDown 0.5s ease;
            transition: all 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Logo */
        .logo {
            z-index: 1001;
        }
        
        .logo a {
            text-decoration: none;
        }
        
        .logo h1 {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #FFFFFF 0%, #E50914 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo span {
            color: #E50914;
            -webkit-text-fill-color: #E50914;
        }
        
        .logo p {
            font-size: 9px;
            letter-spacing: 2px;
            color: #888;
            margin-top: 2px;
        }
        
        /* Desktop Navigation Links */
        .nav-links {
            display: flex;
            gap: 35px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .nav-links a {
            color: #CCCCCC;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            position: relative;
            padding: 8px 0;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #E50914;
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::before,
        .nav-links a.active::before {
            width: 100%;
        }
        
        .nav-links a:hover {
            color: #E50914;
        }
        
        .nav-links a.active {
            color: #E50914;
        }
        
        /* Search Icon */
        .search-icon {
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            color: #CCCCCC;
        }
        
        .search-icon:hover {
            color: #E50914;
            transform: scale(1.1);
        }
        
        /* Hamburger Menu Button */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            z-index: 1001;
            gap: 5px;
        }
        
        .hamburger span {
            width: 25px;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
        
        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-menu-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 70%;
            max-width: 320px;
            height: 100%;
            background: #0D0D0D;
            z-index: 999;
            transition: right 0.3s ease;
            padding: 80px 30px 30px;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
            border-left: 1px solid rgba(229, 9, 20, 0.3);
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu a {
            display: block;
            color: #CCCCCC;
            text-decoration: none;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        .mobile-menu a:hover {
            color: #E50914;
            padding-left: 10px;
        }
        
        .mobile-menu a.active {
            color: #E50914;
        }
        
        /* Mobile Search */
        .search-mobile {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #333;
            border-radius: 30px;
            padding: 10px 15px;
            background: #1A1A1A;
        }
        
        .search-mobile input {
            flex: 1;
            background: transparent;
            border: none;
            color: white;
            outline: none;
            font-size: 14px;
        }
        
        .search-mobile input::placeholder {
            color: #666;
        }
        
        .search-mobile button {
            background: none;
            border: none;
            color: #E50914;
            cursor: pointer;
            font-size: 16px;
        }
        
        /* Desktop Search Modal */
        .search-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        .search-modal.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .search-modal-content {
            width: 90%;
            max-width: 600px;
            background: #1A1A1A;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(229, 9, 20, 0.3);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .search-modal-content h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .search-modal-content form {
            display: flex;
            gap: 10px;
        }
        
        .search-modal-content input {
            flex: 1;
            padding: 15px 20px;
            background: #0D0D0D;
            border: 1px solid #333;
            border-radius: 40px;
            color: white;
            font-size: 16px;
            outline: none;
        }
        
        .search-modal-content input:focus {
            border-color: #E50914;
        }
        
        .search-modal-content button {
            padding: 15px 30px;
            background: #E50914;
            border: none;
            border-radius: 40px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .search-modal-content button:hover {
            background: #FF2A2A;
            transform: translateY(-2px);
        }
        
        .close-search {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 30px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close-search:hover {
            color: #E50914;
            transform: rotate(90deg);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .navbar {
                padding: 12px 30px;
            }
            
            .nav-links {
                gap: 25px;
            }
            
            .nav-links a {
                font-size: 13px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 20px;
            }
            
            .nav-links {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .logo h1 {
                font-size: 22px;
            }
            
            .logo p {
                font-size: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .mobile-menu {
                width: 85%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <h1>DRIVEN<span>AUTO</span></h1>
                <p>RIGHT PEOPLE · RIGHT PRICE · RIGHT CAR</p>
            </a>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="nav-links">
            <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">HOME</a>
            <a href="available_units.php" class="<?php echo $current_page == 'available_units.php' ? 'active' : ''; ?>">AVAILABLE UNITS</a>
            <a href="low_dp_units.php" class="<?php echo $current_page == 'low_dp_units.php' ? 'active' : ''; ?>">LOW DP UNITS</a>
            <a href="satisfied_customer.php" class="<?php echo $current_page == 'satisfied_customer.php' ? 'active' : ''; ?>">SATISFIED CUSTOMERS</a>
            <a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">CONTACT</a>
            <i class="fas fa-search search-icon" onclick="openSearchModal()"></i>
        </div>
        
        <!-- Hamburger Menu Button -->
        <div class="hamburger" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" onclick="closeMobileMenu()"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">HOME</a>
        <a href="available_units.php" class="<?php echo $current_page == 'available_units.php' ? 'active' : ''; ?>">AVAILABLE UNITS</a>
        <a href="low_dp_units.php" class="<?php echo $current_page == 'low_dp_units.php' ? 'active' : ''; ?>">LOW DP UNITS</a>
        <a href="reserve.php" class="<?php echo $current_page == 'reserve.php' ? 'active' : ''; ?>">RESERVE</a>
        <a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">CONTACT</a>
        
        <!-- Mobile Search -->
        <form action="available_units.php" method="GET" class="search-mobile">
            <input type="text" name="search" placeholder="Search cars..." autocomplete="off">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <!-- Desktop Search Modal -->
    <div class="search-modal" id="searchModal">
        <div class="close-search" onclick="closeSearchModal()">&times;</div>
        <div class="search-modal-content">
            <h3>Search Vehicles</h3>
            <form action="available_units.php" method="GET">
                <input type="text" name="search" placeholder="Enter car name, model, or keyword..." autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle Mobile Menu
        function toggleMobileMenu() {
            const hamburger = document.querySelector('.hamburger');
            const mobileMenu = document.querySelector('.mobile-menu');
            const overlay = document.querySelector('.mobile-menu-overlay');
            
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Close Mobile Menu
        function closeMobileMenu() {
            const hamburger = document.querySelector('.hamburger');
            const mobileMenu = document.querySelector('.mobile-menu');
            const overlay = document.querySelector('.mobile-menu-overlay');
            
            hamburger.classList.remove('active');
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Open Search Modal
        function openSearchModal() {
            const modal = document.getElementById('searchModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus on input
            setTimeout(() => {
                const input = modal.querySelector('input');
                if (input) input.focus();
            }, 100);
        }
        
        // Close Search Modal
        function closeSearchModal() {
            const modal = document.getElementById('searchModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close search modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSearchModal();
                closeMobileMenu();
            }
        });
        
        // Close search modal on click outside
        document.getElementById('searchModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSearchModal();
            }
        });
        
        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(13, 13, 13, 0.98)';
                navbar.style.padding = '10px 50px';
            } else {
                navbar.style.background = 'rgba(13, 13, 13, 0.98)';
                navbar.style.padding = '12px 50px';
            }
            
            // Responsive padding
            if (window.innerWidth <= 768) {
                navbar.style.padding = window.scrollY > 50 ? '10px 20px' : '12px 20px';
            } else {
                navbar.style.padding = window.scrollY > 50 ? '10px 50px' : '12px 50px';
            }
        });
        
        // Close mobile menu on window resize (if screen becomes desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
            
            // Reset navbar padding
            const navbar = document.querySelector('.navbar');
            if (window.innerWidth <= 768) {
                navbar.style.padding = window.scrollY > 50 ? '10px 20px' : '12px 20px';
            } else {
                navbar.style.padding = window.scrollY > 50 ? '10px 50px' : '12px 50px';
            }
        });
    </script>
</body>
</html>