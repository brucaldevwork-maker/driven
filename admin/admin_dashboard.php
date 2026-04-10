<?php
require_once '../include/config.php';
requireAdminLogin();

// Get statistics for dashboard
try {
    // Total cars count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cars");
    $totalCars = $stmt->fetch()['total'];
    
    // Total reservations count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations");
    $totalReservations = $stmt->fetch()['total'];
    
    // Pending reservations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
    $pendingReservations = $stmt->fetch()['total'];
    
    // Total sales count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sales");
    $totalSales = $stmt->fetch()['total'];
    
    // Total sales value
    $stmt = $pdo->query("SELECT SUM(final_price) as total_value FROM sales");
    $salesValue = $stmt->fetch()['total_value'];
    $totalSalesValue = $salesValue ? $salesValue : 0;
    
    // Low DP cars count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cars WHERE is_low_dp = 1");
    $lowDpCars = $stmt->fetch()['total'];
    
    // Recent cars (last 5)
    $recentCars = $pdo->query("
        SELECT c.*, cat.category_name 
        FROM cars c 
        LEFT JOIN categories cat ON c.category_id = cat.category_id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Recent reservations (last 5)
    $recentReservations = $pdo->query("
        SELECT r.*, c.car_name 
        FROM reservations r 
        LEFT JOIN cars c ON r.car_id = c.car_id 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Handle success messages
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $success = "New car added successfully!";
    } elseif ($_GET['success'] == 'updated') {
        $success = "Car updated successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $success = "Car deleted successfully!";
    } elseif ($_GET['success'] == 'reservation_updated') {
        $success = "Reservation status updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Driven Auto Sales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0D0D0D;
            color: #FFFFFF;
        }
        
        /* Main Container */
        .dashboard-container {
            padding: 20px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #FFFFFF;
            border-left: 4px solid #E50914;
            padding-left: 15px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #CCCCCC;
            margin-left: 19px;
            font-size: 14px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
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
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }
        
        .alert-danger {
            background: rgba(229, 9, 20, 0.15);
            color: #ff6b6b;
            border-left: 4px solid #E50914;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid #2a2a2a;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #E50914;
            box-shadow: 0 10px 30px rgba(229, 9, 20, 0.2);
        }
        
        .stat-icon {
            position: absolute;
            right: 25px;
            top: 25px;
            font-size: 48px;
            opacity: 0.2;
            color: #E50914;
        }
        
        .stat-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #E50914;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 42px;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 10px;
        }
        
        .stat-trend {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-trend i {
            font-size: 12px;
        }
        
        .trend-up {
            color: #2ecc71;
        }
        
        .trend-down {
            color: #e74c3c;
        }
        
        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            margin-top: 40px;
        }
        
        .section-header:first-of-type {
            margin-top: 0;
        }
        
        .section-header h2 {
            font-size: 22px;
            color: #FFFFFF;
            border-left: 3px solid #E50914;
            padding-left: 12px;
        }
        
        .section-header a {
            color: #E50914;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-header a:hover {
            color: #FF2A2A;
            transform: translateX(3px);
        }
        
        /* Tables */
        .data-table {
            background: #1A1A1A;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 18px 16px;
            background: #0D0D0D;
            color: #E50914;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #E50914;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #2a2a2a;
            color: #e0e0e0;
            font-size: 14px;
            vertical-align: middle;
        }
        
        tr:hover {
            background: rgba(229, 9, 20, 0.06);
            transition: background 0.2s;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .status-confirmed {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .status-completed {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .status-cancelled {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            border: 1px solid #2a2a2a;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .action-btn:hover {
            border-color: #E50914;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.2);
        }
        
        .action-btn i {
            color: #E50914;
            font-size: 16px;
        }
        
        /* Empty State */
        .empty-row td {
            text-align: center;
            padding: 60px 20px;
            color: #777;
            font-size: 16px;
        }
        
        .empty-row i {
            font-size: 48px;
            color: #333;
            margin-bottom: 15px;
            display: block;
        }
        
        /* Low DP Badge */
        .low-dp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(120deg, #ff416c, #ff4b2b);
            color: white;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 40px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .price-cell {
            font-weight: 700;
            color: #E50914;
        }
        
        /* Action Icons */
        .action-icons {
            display: flex;
            gap: 12px;
        }
        
        .action-icon {
            cursor: pointer;
            transition: all 0.2s;
            font-size: 16px;
        }
        
        .action-icon.edit {
            color: #3498db;
        }
        
        .action-icon.edit:hover {
            color: #5dade2;
            transform: scale(1.1);
        }
        
        .action-icon.delete {
            color: #e74c3c;
        }
        
        .action-icon.delete:hover {
            color: #ec7063;
            transform: scale(1.1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-value {
                font-size: 32px;
            }
            
            th, td {
                padding: 12px 10px;
                font-size: 12px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>
    
    <div class="dashboard-container">
        <div class="page-header">
            <h1><i class="fas fa-chalkboard-user"></i> Dashboard</h1>
            <p>Welcome back! Here's what's happening with your dealership today.</p>
        </div>
        
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="manage_cars.php" class="action-btn">
                <i class="fas fa-car"></i> Manage Cars
            </a>
            <a href="manage_cars.php" class="action-btn" onclick="openAddModalFromDashboard(); return false;">
                <i class="fas fa-plus-circle"></i> Add New Car
            </a>
            <a href="reservation_management.php" class="action-btn">
                <i class="fas fa-calendar-check"></i> Manage Reservations
            </a>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-label">Total Vehicles</div>
                <div class="stat-value"><?php echo number_format($totalCars); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-tag"></i>
                    <span><?php echo $lowDpCars; ?> with Low DP</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-label">Total Reservations</div>
                <div class="stat-value"><?php echo number_format($totalReservations); ?></div>
                <div class="stat-trend">
                    <?php if ($pendingReservations > 0): ?>
                        <i class="fas fa-clock trend-up"></i>
                        <span><?php echo $pendingReservations; ?> pending</span>
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i>
                        <span>No pending</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value"><?php echo number_format($totalSales); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                    <span>Completed transactions</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-label">Sales Value</div>
                <div class="stat-value">₱ <?php echo number_format($totalSalesValue, 0); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                    <span>Total revenue</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Cars -->
        <div class="section-header">
            <h2><i class="fas fa-car"></i> Recently Added Vehicles</h2>
            <a href="manage_cars.php">
                Manage All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Car Name</th>
                        <th>Category</th>
                        <th>Price (₱)</th>
                        <th>Monthly (₱)</th>
                        <th>Low DP</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentCars) > 0): ?>
                        <?php foreach ($recentCars as $car): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($car['car_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($car['category_name']); ?></td>
                                <td class="price-cell">₱ <?php echo number_format($car['price'], 0); ?></td>
                                <td>₱ <?php echo number_format($car['monthly'], 0); ?></td>
                                <td>
                                    <?php if ($car['is_low_dp']): ?>
                                        <span class="low-dp-badge">
                                            <i class="fas fa-bolt"></i> LOW DP
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #555;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($car['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fas fa-car-side"></i>
                                No cars found. Start by adding your first vehicle!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Reservations -->
        <div class="section-header">
            <h2><i class="fas fa-calendar-check"></i> Recent Reservations</h2>
            <a href="reservation_management.php">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Appointment</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentReservations) > 0): ?>
                        <?php foreach ($recentReservations as $reservation): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($reservation['customer_phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['car_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($reservation['appointment_date'])); ?><br>
                                    <small><?php echo date('g:i A', strtotime($reservation['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <?php echo ucfirst(str_replace('_', ' ', $reservation['type'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <i class="fas <?php echo $reservation['status'] == 'pending' ? 'fa-clock' : ($reservation['status'] == 'confirmed' ? 'fa-check-circle' : ($reservation['status'] == 'completed' ? 'fa-check-double' : 'fa-times-circle')); ?>"></i>
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <a href="reservation_management.php?edit=<?php echo $reservation['reservation_id']; ?>" class="action-icon edit" title="Edit Reservation">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="reservation_management.php?view=<?php echo $reservation['reservation_id']; ?>" class="action-icon" title="View Details" style="color: #2ecc71;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fas fa-calendar-alt"></i>
                                No reservations found. Customers haven't made any reservations yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Function to redirect to add car modal from dashboard
        function openAddModalFromDashboard() {
            // Store in sessionStorage that we want to open the modal when manage_cars.php loads
            sessionStorage.setItem('openAddModal', 'true');
            window.location.href = 'manage_cars.php';
        }
        
        // Check if we need to open the add modal when manage_cars.php loads
        if (window.location.pathname.includes('manage_cars.php') && sessionStorage.getItem('openAddModal') === 'true') {
            sessionStorage.removeItem('openAddModal');
            // This will be handled by manage_cars.php
        }
    </script>
</body>
</html>