<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* ========== RESET & BASE ========== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        overflow-x: hidden;
    }

    /* ========== MODERN NAVIGATION BAR ========== */
    .admin-nav {
        background: #0a0a0a;
        padding: 0 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        border-bottom: 2px solid #e50914;
        transition: all 0.2s ease;
    }

    /* Left section */
    .nav-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .admin-nav .logo a {
        text-decoration: none;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.8px;
        transition: all 0.2s;
        background: linear-gradient(135deg, #fff 30%, #e50914 80%);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
    }

    .admin-nav .logo span {
        color: #e50914;
        background: none;
        -webkit-background-clip: unset;
        background-clip: unset;
    }

    /* ========== HAMBURGER - DESIGN 2: SOLID RED SQUARE ========== */
    .hamburger {
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 44px;
        height: 44px;
        background: #e50914;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        gap: 5px;
        padding: 12px;
        transition: all 0.2s ease;
        z-index: 1001;
    }

    .hamburger:hover {
        background: #ff2e3a;
        transform: scale(1.05);
        box-shadow: 0 6px 18px rgba(229, 9, 20, 0.45);
    }

    .hamburger span {
        display: block;
        width: 20px;
        height: 2px;
        background: #ffffff;
        border-radius: 4px;
        transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hamburger.active span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    .hamburger.active span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    .hamburger.active {
        background: #c20811;
        box-shadow: 0 4px 14px rgba(229, 9, 20, 0.5);
    }

    /* Navigation Links */
    .admin-nav .nav-links {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .admin-nav .nav-links a {
        color: #e0e0e0;
        text-decoration: none;
        padding: 20px 20px;
        transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        font-weight: 550;
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        border-radius: 12px 12px 0 0;
    }

    .admin-nav .nav-links a i {
        font-size: 1.2rem;
        transition: transform 0.2s;
    }

    .admin-nav .nav-links a:hover {
        color: #ffffff;
        background: rgba(229, 9, 20, 0.12);
    }

    .admin-nav .nav-links a:hover i {
        transform: translateY(-2px);
    }

    .admin-nav .nav-links a.active {
        color: #e50914;
        font-weight: 600;
        background: rgba(229, 9, 20, 0.08);
    }

    .admin-nav .nav-links a.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 15%;
        width: 70%;
        height: 3px;
        background: #e50914;
        border-radius: 3px 3px 0 0;
        box-shadow: 0 0 8px rgba(229, 9, 20, 0.6);
    }

    /* User area */
    .admin-nav .user-info {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .admin-nav .user-name {
        color: #ccc;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.05);
        padding: 6px 14px;
        border-radius: 40px;
    }

    .admin-nav .user-name strong {
        color: white;
        font-weight: 600;
    }

    .logout-btn {
        background: #e50914;
        padding: 8px 20px;
        border-radius: 40px;
        color: white !important;
        text-decoration: none;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 13px;
        letter-spacing: 0.3px;
    }

    .logout-btn:hover {
        background: #ff2e3a;
        transform: scale(1.02);
        box-shadow: 0 6px 14px rgba(229, 9, 20, 0.35);
    }

    /* Overlay */
    .nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(5px);
        z-index: 998;
        transition: 0.2s;
    }

    .nav-overlay.active {
        display: block;
    }

    /* ========== MOBILE STYLES ========== */
    @media (max-width: 1024px) {
        .admin-nav .nav-links a {
            padding: 18px 14px;
            font-size: 13px;
        }
    }

    @media (max-width: 880px) {
        .hamburger {
            display: flex;
        }

        .admin-nav {
            padding: 0 20px;
        }

        .admin-nav .nav-links {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: #0f0f12;
            flex-direction: column;
            align-items: flex-start;
            gap: 0;
            padding: 85px 0 30px 0;
            transition: left 0.28s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: 8px 0 28px rgba(0, 0, 0, 0.5);
            z-index: 999;
            overflow-y: auto;
            border-right: 2px solid #e50914;
        }

        .admin-nav .nav-links.active {
            left: 0;
        }

        .admin-nav .nav-links a {
            width: 100%;
            padding: 16px 28px;
            font-size: 15px;
            border-radius: 0;
            border-bottom: 1px solid rgba(229, 9, 20, 0.15);
        }

        .admin-nav .nav-links a.active::after {
            left: 0;
            width: 4px;
            height: 100%;
            bottom: auto;
            top: 0;
            border-radius: 0 4px 4px 0;
        }

        .admin-nav .user-name {
            font-size: 12px;
            padding: 5px 12px;
        }

        .logout-btn {
            padding: 6px 14px;
        }
    }

    @media (max-width: 640px) {
        .admin-nav .user-name span {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .nav-left {
            gap: 12px;
        }

        .admin-nav .logo a {
            font-size: 18px;
        }

        .hamburger {
            width: 40px;
            height: 40px;
            padding: 10px;
            border-radius: 10px;
        }

        .hamburger span {
            width: 18px;
        }

        .admin-nav .nav-links {
            width: 260px;
            left: -260px;
        }
    }

    body.menu-open {
        overflow: hidden;
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-nav">
    <div class="nav-left">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="logo">
            <a href="/Driven/admin/admin_dashboard.php">DRIVEN<span>AUTO</span></a>
        </div>
    </div>

    <div class="nav-links" id="navLinks">
        <a href="/Driven/admin/admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-user"></i> Dashboard
        </a>
        <a href="/Driven/admin/manage_cars.php" class="<?php echo $current_page == 'manage_cars.php' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i> Manage Cars
        </a>
        <a href="/Driven/admin/sale_management.php" class="<?php echo $current_page == 'sale_management.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-simple"></i> Sale Management
        </a>
        <a href="/Driven/admin/sales_report.php" class="<?php echo $current_page == 'sales_report.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Sales Report
        </a>
        <a href="/Driven/admin/reservation_management.php" class="<?php echo $current_page == 'reservation_management.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Reservations
        </a>
        <a href="/Driven/admin/satisfied_customer.php" class="<?php echo $current_page == 'satisfied_customer.php' ? 'active' : ''; ?>">
            <i class="fas fa-smile"></i> Satisfied Customers
        </a>
    </div>

    <div class="user-info">
        <div class="user-name">
            <i class="fas fa-user-astronaut"></i>
            <span>Welcome,</span>
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
        </div>
        <a href="/Driven/auth/admin_logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>

<div class="nav-overlay" id="navOverlay"></div>

<script>
    const hamburger = document.getElementById('hamburgerBtn');
    const navLinks = document.getElementById('navLinks');
    const navOverlay = document.getElementById('navOverlay');
    const body = document.body;

    function toggleMenu() {
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
        navOverlay.classList.toggle('active');
        body.classList.toggle('menu-open');
    }

    function closeMenu() {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
        navOverlay.classList.remove('active');
        body.classList.remove('menu-open');
    }

    hamburger.addEventListener('click', toggleMenu);
    navOverlay.addEventListener('click', closeMenu);

    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 880) setTimeout(closeMenu, 120);
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 880) closeMenu();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && navLinks.classList.contains('active')) closeMenu();
    });
</script>