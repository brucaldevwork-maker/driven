<?php
require_once '../include/config.php';
requireAdminLogin();

// Initialize variables
$reservations = [];
$error = null;
$success = null;

// Handle POST requests for status updates and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $reservation_id = intval($_POST['reservation_id']);
                    $new_status = $_POST['status'];
                    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                    
                    if (in_array($new_status, $valid_statuses)) {
                        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
                        $stmt->execute([$new_status, $reservation_id]);
                        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Invalid status']);
                    }
                    break;
                    
                case 'bulk_update':
                    $reservation_ids = json_decode($_POST['ids'], true);
                    $new_status = $_POST['status'];
                    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
                    
                    if (in_array($new_status, $valid_statuses) && is_array($reservation_ids) && count($reservation_ids) > 0) {
                        $placeholders = str_repeat('?,', count($reservation_ids) - 1) . '?';
                        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE reservation_id IN ($placeholders)");
                        $params = array_merge([$new_status], $reservation_ids);
                        $stmt->execute($params);
                        echo json_encode(['success' => true, 'message' => count($reservation_ids) . ' reservations updated']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Invalid request']);
                    }
                    break;
                    
                case 'delete':
                    $reservation_id = intval($_POST['reservation_id']);
                    $stmt = $pdo->prepare("DELETE FROM reservations WHERE reservation_id = ?");
                    $stmt->execute([$reservation_id]);
                    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No action specified']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Fetch reservations from database
try {
    $query = "
        SELECT r.*, c.car_name 
        FROM reservations r 
        LEFT JOIN cars c ON r.car_id = c.car_id 
        ORDER BY r.created_at DESC
    ";
    $stmt = $pdo->query($query);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all cars for car names lookup
    $carStmt = $pdo->query("SELECT car_id, car_name FROM cars");
    $cars = $carStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Calculate statistics
    $stats = [
        'total' => count($reservations),
        'pending' => 0,
        'confirmed' => 0,
        'car_reservations' => 0,
        'appointments' => 0,
        'upcoming' => 0
    ];
    
    $today = date('Y-m-d');
    $nextWeek = date('Y-m-d', strtotime('+7 days'));
    
    foreach ($reservations as $res) {
        // Count by status
        if ($res['status'] == 'pending') $stats['pending']++;
        if ($res['status'] == 'confirmed') $stats['confirmed']++;
        
        // Count by type
        if ($res['type'] == 'car_reservation') $stats['car_reservations']++;
        if ($res['type'] == 'appointment') $stats['appointments']++;
        
        // Count upcoming
        if ($res['appointment_date'] >= $today && 
            $res['appointment_date'] <= $nextWeek && 
            in_array($res['status'], ['pending', 'confirmed'])) {
            $stats['upcoming']++;
        }
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'car_reservations' => 0, 'appointments' => 0, 'upcoming' => 0];
    $cars = [];
}

// Handle success message from GET parameter
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'updated') {
        $success = "Reservation status updated successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $success = "Reservation deleted successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Reservation Management | Driven Auto Sales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Keep all your existing CSS styles - they're fine */
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
        
        /* Main Container - Only Reservation Management */
        .reservation-main {
            min-height: 100vh;
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #FFFFFF;
            border-left: 4px solid #E50914;
            padding-left: 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header h1 i {
            color: #E50914;
            font-size: 32px;
        }
        
        .page-header p {
            color: #CCCCCC;
            margin-left: 24px;
            font-size: 14px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            animation: slideDown 0.4s ease;
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
            background: rgba(46, 204, 113, 0.12);
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }
        
        .alert-danger {
            background: rgba(229, 9, 20, 0.12);
            color: #ff6b6b;
            border-left: 4px solid #E50914;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #141414 0%, #0A0A0A 100%);
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid #252525;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #E50914;
        }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 38px;
            opacity: 0.15;
            color: #E50914;
        }
        
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #E50914;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 34px;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 5px;
        }
        
        /* Filter Section */
        .filter-section {
            background: #121212;
            border-radius: 20px;
            padding: 22px 25px;
            margin-bottom: 30px;
            border: 1px solid #252525;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 160px;
        }
        
        .filter-group label {
            display: block;
            font-size: 11px;
            color: #E50914;
            margin-bottom: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 11px 14px;
            background: #0D0D0D;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #E50914;
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Buttons */
        .btn {
            padding: 11px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #E50914;
            color: white;
        }
        
        .btn-primary:hover {
            background: #ff1a2a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
        }
        
        .btn-secondary {
            background: #252525;
            color: #FFFFFF;
        }
        
        .btn-secondary:hover {
            background: #353535;
        }
        
        /* Bulk Actions */
        .bulk-actions {
            background: #121212;
            border-radius: 14px;
            padding: 15px 22px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid #252525;
        }
        
        .bulk-actions.hidden {
            display: none;
        }
        
        /* Data Table */
        .data-table {
            background: #121212;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            border: 1px solid #252525;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th {
            text-align: left;
            padding: 18px 16px;
            background: #0A0A0A;
            color: #E50914;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #E50914;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #252525;
            color: #e0e0e0;
            font-size: 13px;
            vertical-align: middle;
        }
        
        tr:hover {
            background: rgba(229, 9, 20, 0.05);
        }
        
        .checkbox-cell {
            width: 45px;
            text-align: center;
        }
        
        .checkbox-cell input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #E50914;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
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
        
        /* Type Badges */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .type-badge.car-reservation {
            background: rgba(229, 9, 20, 0.15);
            color: #E50914;
        }
        
        .type-badge.appointment {
            background: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            background: #0D0D0D;
            border: 1px solid #2a2a2a;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #e0e0e0;
        }
        
        .btn-icon:hover {
            border-color: #E50914;
            transform: translateY(-1px);
        }
        
        .status-select {
            padding: 6px 10px;
            background: #0D0D0D;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 11px;
            cursor: pointer;
        }
        
        .car-name {
            font-weight: 600;
            color: #E50914;
        }
        
        .message-preview {
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #888;
            font-size: 11px;
        }
        
        .empty-row td {
            text-align: center;
            padding: 60px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #141414 0%, #0A0A0A 100%);
            border-radius: 24px;
            max-width: 750px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            animation: modalSlideUp 0.3s ease;
            border: 1px solid rgba(229, 9, 20, 0.3);
        }
        
        @keyframes modalSlideUp {
            from {
                transform: translateY(40px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #E50914 0%, #b00710 100%);
            padding: 22px 28px;
            border-radius: 24px 24px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }
        
        .modal-header h3 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 28px;
        }
        
        .detail-card {
            background: #0D0D0D;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid #252525;
            overflow: hidden;
        }
        
        .card-header {
            background: rgba(229, 9, 20, 0.08);
            padding: 14px 20px;
            border-bottom: 2px solid #E50914;
        }
        
        .card-header h4 {
            color: #E50914;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 18px 20px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #252525;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-icon {
            width: 38px;
            color: #E50914;
            font-size: 16px;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-value {
            flex: 1;
            color: #FFFFFF;
            font-weight: 500;
        }
        
        .message-box {
            background: #0A0A0A;
            border-radius: 12px;
            padding: 18px;
            border-left: 3px solid #E50914;
        }
        
        .modal-footer {
            padding: 18px 28px;
            background: #0D0D0D;
            border-top: 1px solid #252525;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-radius: 0 0 24px 24px;
        }
        
        .btn-modal {
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-modal-primary {
            background: #E50914;
            color: white;
        }
        
        .btn-modal-primary:hover {
            background: #ff1a2a;
        }
        
        .btn-modal-secondary {
            background: #252525;
            color: white;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .reservation-main {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
            }
            
            .filter-actions .btn {
                flex: 1;
                justify-content: center;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            
            .info-label {
                width: auto;
            }
        }
        
        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 24px;
            }
            
            .stat-value {
                font-size: 26px;
            }
            
            .stat-card {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>
    <div class="reservation-main">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-calendar-check"></i>
                Reservation Management
            </h1>
            <p>View, manage, and update customer reservations and appointments</p>
        </div>
        
        <!-- Success/Error Messages -->
        <div id="alertContainer">
            <?php if ($error): ?>
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
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-label">Total Reservations</div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Confirmed</div>
                <div class="stat-value"><?php echo number_format($stats['confirmed']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-label">Car Reservations</div>
                <div class="stat-value"><?php echo number_format($stats['car_reservations']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-label">Appointments</div>
                <div class="stat-value"><?php echo number_format($stats['appointments']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-label">Upcoming (7d)</div>
                <div class="stat-value"><?php echo number_format($stats['upcoming']); ?></div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" id="searchInput" placeholder="Name, email, phone..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Status</label>
                    <select name="status" id="statusFilter">
                        <option value="all" <?php echo ($_GET['status'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo ($_GET['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Type</label>
                    <select name="type" id="typeFilter">
                        <option value="all" <?php echo ($_GET['type'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="car_reservation" <?php echo ($_GET['type'] ?? '') == 'car_reservation' ? 'selected' : ''; ?>>Car Reservation</option>
                        <option value="appointment" <?php echo ($_GET['type'] ?? '') == 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> Appointment Date</label>
                    <input type="date" name="date" id="dateFilter" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="reservation_management.php" class="btn btn-secondary" id="resetFiltersBtn">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div id="bulkActions" class="bulk-actions hidden">
            <div>
                <i class="fas fa-check-square"></i>
                <span id="selectedCount">0</span> reservations selected
            </div>
            <div>
                <select id="bulkStatusSelect" class="status-select" style="margin-right: 10px;">
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button id="bulkUpdateBtn" class="btn btn-primary" style="padding: 8px 16px;">
                    <i class="fas fa-save"></i> Apply to Selected
                </button>
                <button id="clearSelectionBtn" class="btn btn-secondary" style="padding: 8px 16px;">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
        
        <!-- Reservations Table -->
        <div class="data-table">
            <table id="reservationsTable">
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAllCheckbox">
                        </th>
                        <th>Customer Details</th>
                        <th>Vehicle</th>
                        <th>Appointment</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (count($reservations) > 0): ?>
                        <?php 
                        // Apply filters for display
                        $filteredReservations = $reservations;
                        $search = $_GET['search'] ?? '';
                        $status = $_GET['status'] ?? 'all';
                        $type = $_GET['type'] ?? 'all';
                        $date = $_GET['date'] ?? '';
                        
                        if ($search) {
                            $searchLower = strtolower($search);
                            $filteredReservations = array_filter($filteredReservations, function($r) use ($searchLower) {
                                return stripos($r['customer_name'], $searchLower) !== false ||
                                       stripos($r['customer_email'], $searchLower) !== false ||
                                       stripos($r['customer_phone'], $searchLower) !== false;
                            });
                        }
                        if ($status !== 'all') {
                            $filteredReservations = array_filter($filteredReservations, function($r) use ($status) {
                                return $r['status'] === $status;
                            });
                        }
                        if ($type !== 'all') {
                            $filteredReservations = array_filter($filteredReservations, function($r) use ($type) {
                                return $r['type'] === $type;
                            });
                        }
                        if ($date) {
                            $filteredReservations = array_filter($filteredReservations, function($r) use ($date) {
                                return $r['appointment_date'] === $date;
                            });
                        }
                        ?>
                        <?php foreach ($filteredReservations as $res): ?>
                            <tr data-id="<?php echo $res['reservation_id']; ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="reservation-checkbox" value="<?php echo $res['reservation_id']; ?>">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($res['customer_name']); ?></strong><br>
                                    <small style="color: #888;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($res['customer_email']); ?><br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($res['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="car-name"><?php echo htmlspecialchars($res['car_name'] ?? 'N/A'); ?></span><br>
                                    <small style="color: #888;">Car ID: <?php echo $res['car_id']; ?></small>
                                </td>
                                <td><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($res['appointment_date'])); ?><br><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($res['appointment_time'])); ?></td>
                                <td>
                                    <?php if ($res['type'] == 'car_reservation'): ?>
                                        <span class="type-badge car-reservation"><i class="fas fa-car"></i> Car Reservation</span>
                                    <?php else: ?>
                                        <span class="type-badge appointment"><i class="fas fa-calendar-check"></i> Appointment</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $res['status']; ?>">
                                        <i class="fas <?php echo $res['status'] == 'pending' ? 'fa-clock' : ($res['status'] == 'confirmed' ? 'fa-check-circle' : ($res['status'] == 'completed' ? 'fa-check-double' : 'fa-times-circle')); ?>"></i>
                                        <?php echo ucfirst($res['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($res['message']): ?>
                                        <div class="message-preview" title="<?php echo htmlspecialchars($res['message']); ?>">
                                            <i class="fas fa-comment"></i> <?php echo htmlspecialchars(substr($res['message'], 0, 50)); ?><?php echo strlen($res['message']) > 50 ? '...' : ''; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #555;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <select class="status-select" data-id="<?php echo $res['reservation_id']; ?>" data-current="<?php echo $res['status']; ?>">
                                            <option value="">Change Status</option>
                                            <option value="pending" <?php echo $res['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $res['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?php echo $res['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $res['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button class="btn-icon view-btn" data-id="<?php echo $res['reservation_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                        <button class="btn-icon delete-btn" data-id="<?php echo $res['reservation_id']; ?>" style="background: rgba(229, 9, 20, 0.2);"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="8">
                                <i class="fas fa-calendar-alt" style="font-size: 48px; color: #333; margin-bottom: 15px; display: block;"></i>
                                No reservations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-ticket-alt"></i> Reservation Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Close</button>
                <button class="btn-modal btn-modal-primary" onclick="closeModal()"><i class="fas fa-check"></i> Done</button>
            </div>
        </div>
    </div>
    
    <script>
        // Car names lookup from PHP
        const carNames = <?php echo json_encode($cars); ?>;
        
        let selectedReservations = [];
        
        // Helper Functions
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        function formatTime(timeStr) {
            if (!timeStr) return 'N/A';
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
        
        function getStatusBadgeClass(status) {
            const classes = { 'pending': 'status-pending', 'confirmed': 'status-confirmed', 'completed': 'status-completed', 'cancelled': 'status-cancelled' };
            return classes[status] || '';
        }
        
        function getStatusIcon(status) {
            const icons = { 'pending': 'fa-clock', 'confirmed': 'fa-check-circle', 'completed': 'fa-check-double', 'cancelled': 'fa-times-circle' };
            return icons[status] || 'fa-question-circle';
        }
        
        function getCarName(carId) {
            return carNames[carId] || `Car #${carId}`;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showAlert(type, message) {
            const container = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const alertHtml = `<div class="alert ${alertClass}"><i class="fas ${icon}"></i>${message}</div>`;
            container.innerHTML = alertHtml;
            setTimeout(() => { 
                const alerts = container.querySelectorAll('.alert');
                alerts.forEach(alert => alert.remove());
            }, 4000);
        }
        
        async function updateStatus(reservationId, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('reservation_id', reservationId);
                formData.append('status', newStatus);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    // Update the status badge in the table
                    const row = document.querySelector(`tr[data-id="${reservationId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('td:nth-child(6)');
                        const statusBadge = statusCell.querySelector('.status-badge');
                        const statusIcon = getStatusIcon(newStatus);
                        statusBadge.innerHTML = `<i class="fas ${statusIcon}"></i> ${newStatus.toUpperCase()}`;
                        statusBadge.className = `status-badge status-${newStatus}`;
                        
                        // Update the select dropdown
                        const select = row.querySelector('.status-select');
                        if (select) {
                            select.value = newStatus;
                            select.dataset.current = newStatus;
                        }
                    }
                    // Reload to update stats
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert('danger', result.error || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating status');
            }
        }
        
        async function deleteReservation(reservationId) {
            if (!confirm('Are you sure you want to delete this reservation? This action cannot be undone.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('reservation_id', reservationId);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    // Remove the row from the table
                    const row = document.querySelector(`tr[data-id="${reservationId}"]`);
                    if (row) {
                        row.remove();
                    }
                    // Reload to update stats
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert('danger', result.error || 'Failed to delete reservation');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting reservation');
            }
        }
        
        async function bulkUpdateStatus(reservationIds, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'bulk_update');
                formData.append('ids', JSON.stringify(reservationIds));
                formData.append('status', newStatus);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    // Reload to update all data
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert('danger', result.error || 'Failed to update reservations');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating reservations');
            }
        }
        
        function viewReservation(reservationId) {
            // Fetch reservation details via AJAX or use PHP data
            // For simplicity, we'll reload the page with a GET parameter to show modal
            window.location.href = `${window.location.pathname}?view=${reservationId}`;
        }
        
        function updateBulkActionsUI() {
            const bulkDiv = document.getElementById('bulkActions');
            const countSpan = document.getElementById('selectedCount');
            const selectAll = document.getElementById('selectAllCheckbox');
            
            if (selectedReservations.length > 0) {
                bulkDiv.classList.remove('hidden');
                countSpan.innerText = selectedReservations.length;
            } else {
                bulkDiv.classList.add('hidden');
            }
            
            const allCheckboxes = document.querySelectorAll('.reservation-checkbox');
            const allChecked = allCheckboxes.length > 0 && Array.from(allCheckboxes).every(cb => cb.checked);
            if (selectAll) selectAll.checked = allChecked;
        }
        
        function clearSelection() {
            selectedReservations = [];
            document.querySelectorAll('.reservation-checkbox').forEach(cb => cb.checked = false);
            updateBulkActionsUI();
        }
        
        // Check if we need to show view modal from URL parameter
        <?php if (isset($_GET['view']) && is_numeric($_GET['view'])): 
            $viewId = intval($_GET['view']);
            $viewReservation = null;
            foreach ($reservations as $res) {
                if ($res['reservation_id'] == $viewId) {
                    $viewReservation = $res;
                    break;
                }
            }
            if ($viewReservation):
        ?>
        document.addEventListener('DOMContentLoaded', function() {
            const reservation = <?php echo json_encode($viewReservation); ?>;
            const modalBody = document.getElementById('modalBody');
            const formattedDate = new Date(reservation.appointment_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const formattedCreated = new Date(reservation.created_at).toLocaleString();
            const typeText = reservation.type === 'car_reservation' ? 'Car Reservation' : 'Appointment';
            const typeIcon = reservation.type === 'car_reservation' ? 'fa-car' : 'fa-calendar-check';
            
            modalBody.innerHTML = `
                <div class="detail-card"><div class="card-header"><h4><i class="fas fa-user-circle"></i> Customer Information</h4></div><div class="card-body">
                    <div class="info-row"><div class="info-icon"><i class="fas fa-user"></i></div><div class="info-label">Full Name</div><div class="info-value"><strong>${escapeHtml(reservation.customer_name)}</strong></div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-envelope"></i></div><div class="info-label">Email</div><div class="info-value">${escapeHtml(reservation.customer_email)}</div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-phone"></i></div><div class="info-label">Phone</div><div class="info-value">${escapeHtml(reservation.customer_phone)}</div></div>
                </div></div>
                <div class="detail-card"><div class="card-header"><h4><i class="fas fa-car"></i> Vehicle Information</h4></div><div class="card-body">
                    <div class="info-row"><div class="info-icon"><i class="fas fa-car"></i></div><div class="info-label">Car ID</div><div class="info-value"><strong class="car-name">${reservation.car_id}</strong></div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-car"></i></div><div class="info-label">Car Model</div><div class="info-value">${escapeHtml(getCarName(reservation.car_id))}</div></div>
                </div></div>
                <div class="detail-card"><div class="card-header"><h4><i class="fas fa-calendar-check"></i> Appointment Details</h4></div><div class="card-body">
                    <div class="info-row"><div class="info-icon"><i class="fas fa-calendar-alt"></i></div><div class="info-label">Date</div><div class="info-value">${formattedDate}</div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-clock"></i></div><div class="info-label">Time</div><div class="info-value">${formatTime(reservation.appointment_time)}</div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas ${typeIcon}"></i></div><div class="info-label">Type</div><div class="info-value"><span class="type-badge ${reservation.type === 'car_reservation' ? 'car-reservation' : 'appointment'}"><i class="fas ${typeIcon}"></i> ${typeText}</span></div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-flag-checkered"></i></div><div class="info-label">Status</div><div class="info-value"><span class="status-indicator ${getStatusBadgeClass(reservation.status)}"><i class="fas ${getStatusIcon(reservation.status)}"></i> ${reservation.status.toUpperCase()}</span></div></div>
                </div></div>
                ${reservation.message ? `<div class="detail-card"><div class="card-header"><h4><i class="fas fa-comment-dots"></i> Customer Message</h4></div><div class="card-body"><div class="message-box"><i class="fas fa-quote-left" style="color:#E50914;opacity:0.5;margin-bottom:10px;display:block;"></i><p>${escapeHtml(reservation.message)}</p></div></div></div>` : ''}
                <div class="detail-card"><div class="card-header"><h4><i class="fas fa-info-circle"></i> Additional Information</h4></div><div class="card-body">
                    <div class="info-row"><div class="info-icon"><i class="fas fa-id-card"></i></div><div class="info-label">Reservation ID</div><div class="info-value"><strong>#${reservation.reservation_id}</strong></div></div>
                    <div class="info-row"><div class="info-icon"><i class="fas fa-clock"></i></div><div class="info-label">Created</div><div class="info-value">${formattedCreated}</div></div>
                </div></div>
            `;
            document.getElementById('viewModal').classList.add('active');
            
            // Remove the view parameter from URL without reloading
            const url = new URL(window.location.href);
            url.searchParams.delete('view');
            window.history.replaceState({}, '', url);
        });
        <?php endif; endif; ?>
        
        // Initialize Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Status select change
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function(e) {
                    const id = parseInt(this.dataset.id);
                    const newStatus = this.value;
                    if (newStatus && newStatus !== this.dataset.current) {
                        updateStatus(id, newStatus);
                    }
                });
            });
            
            // View buttons
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    viewReservation(id);
                });
            });
            
            // Delete buttons
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    deleteReservation(id);
                });
            });
            
            // Checkbox selection
            document.querySelectorAll('.reservation-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const id = parseInt(this.value);
                    if (this.checked) {
                        if (!selectedReservations.includes(id)) selectedReservations.push(id);
                    } else {
                        selectedReservations = selectedReservations.filter(sid => sid !== id);
                    }
                    updateBulkActionsUI();
                });
            });
            
            // Select all checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.reservation-checkbox');
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                        const id = parseInt(cb.value);
                        if (this.checked) {
                            if (!selectedReservations.includes(id)) selectedReservations.push(id);
                        } else {
                            selectedReservations = selectedReservations.filter(sid => sid !== id);
                        }
                    });
                    updateBulkActionsUI();
                });
            }
            
            // Clear selection button
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) {
                clearSelectionBtn.addEventListener('click', clearSelection);
            }
            
            // Bulk update button
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            if (bulkUpdateBtn) {
                bulkUpdateBtn.addEventListener('click', () => {
                    const newStatus = document.getElementById('bulkStatusSelect').value;
                    if (!newStatus) {
                        showAlert('danger', 'Please select a status to apply.');
                        return;
                    }
                    if (selectedReservations.length === 0) {
                        showAlert('danger', 'Please select at least one reservation.');
                        return;
                    }
                    bulkUpdateStatus(selectedReservations, newStatus);
                });
            }
        });
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>