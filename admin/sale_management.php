<?php
// Include config and authentication
require_once '../include/config.php';
requireAdminLogin();

// Database connection (using existing PDO from config)
// Note: Assuming $pdo is already defined in config.php

// ─── AJAX Handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Add new sale
    if ($_POST['action'] === 'add_sale') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sales (car_id, customer_name, customer_email, customer_phone,
                    final_price, down_payment, monthly_payment, terms_months,
                    sale_date, payment_method, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $final_price   = floatval($_POST['final_price']);
            $down_payment  = floatval($_POST['down_payment']);
            $balance       = $final_price - $down_payment;
            $terms         = $_POST['terms_months'];
            
            // ✅ FIXED: Use manual monthly if provided, otherwise calculate
            $monthly = !empty($_POST['monthly_payment']) && $_POST['monthly_payment'] !== ''
                ? round(floatval($_POST['monthly_payment']), 2)
                : (($terms !== 'N/A' && intval($terms) > 0) ? round($balance / intval($terms), 2) : 0);

            $stmt->execute([
                $_POST['car_id'],
                $_POST['customer_name'],
                $_POST['customer_email'],
                $_POST['customer_phone'],
                $final_price,
                $down_payment,
                $monthly,
                $terms,
                $_POST['sale_date'],
                $_POST['payment_method'],
                $_POST['notes'] ?? null
            ]);
            $sale_id = $pdo->lastInsertId();

            // Auto-record down payment transaction if > 0
            if ($down_payment > 0) {
                $pt = $pdo->prepare("
                    INSERT INTO payment_transactions
                        (sale_id, payment_number, payment_amount, payment_date,
                         payment_method, reference_number, status, notes, recorded_by)
                    VALUES (?, 1, ?, ?, ?, ?, 'paid', 'Down payment', ?)
                ");
                $pt->execute([
                    $sale_id, $down_payment,
                    $_POST['sale_date'] . ' 00:00:00',
                    $_POST['payment_method'],
                    $_POST['reference_number'] ?? null,
                    $_SESSION['admin'] ?? 'admin'
                ]);
            }

            echo json_encode(['success' => true, 'sale_id' => $sale_id, 'monthly' => $monthly]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Record a monthly payment
    if ($_POST['action'] === 'add_payment') {
        try {
            // Get next payment number
            $cnt = $pdo->prepare("SELECT COUNT(*)+1 FROM payment_transactions WHERE sale_id=?");
            $cnt->execute([$_POST['sale_id']]);
            $next_num = $cnt->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions
                    (sale_id, payment_number, payment_amount, payment_date,
                     payment_method, reference_number, status, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['sale_id'],
                $next_num,
                $_POST['payment_amount'],
                $_POST['payment_date'],
                $_POST['payment_method'],
                $_POST['reference_number'] ?? null,
                $_POST['status'],
                $_POST['notes'] ?? null,
                $_SESSION['admin'] ?? 'admin'
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Delete sale
    if ($_POST['action'] === 'delete_sale') {
        try {
            $stmt = $pdo->prepare("DELETE FROM sales WHERE sale_id=?");
            $stmt->execute([$_POST['sale_id']]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// ─── Fetch Data for Page ──────────────────────────────────────────────────────
// Cars for dropdown
$cars = $pdo->query("SELECT car_id, car_name FROM cars ORDER BY car_name")->fetchAll(PDO::FETCH_ASSOC);

// All sales with payment summary
$sales = $pdo->query("
    SELECT s.*,
           c.car_name,
           COALESCE(SUM(pt.payment_amount), 0) AS total_paid,
           COUNT(pt.transaction_id) AS payment_count,
           SUM(CASE WHEN pt.status = 'paid' THEN 1 ELSE 0 END) AS paid_tx_count,
           SUM(CASE WHEN pt.status = 'partial' THEN 1 ELSE 0 END) AS partial_tx_count,
           SUM(CASE WHEN pt.status = 'pending' THEN 1 ELSE 0 END) AS pending_tx_count
    FROM sales s
    LEFT JOIN cars c ON s.car_id = c.car_id
    LEFT JOIN payment_transactions pt ON s.sale_id = pt.sale_id AND pt.status IN ('paid', 'partial')
    GROUP BY s.sale_id
    ORDER BY s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Payment transactions per sale (for modal)
$transactions = $pdo->query("
    SELECT pt.*, s.customer_name
    FROM payment_transactions pt
    JOIN sales s ON pt.sale_id = s.sale_id
    ORDER BY pt.payment_date DESC, pt.transaction_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Group by sale_id
$txBySale = [];
foreach ($transactions as $tx) {
    $txBySale[$tx['sale_id']][] = $tx;
}

// Handle success messages
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'sale_added') {
        $success = "New sale recorded successfully!";
    } elseif ($_GET['success'] == 'payment_added') {
        $success = "Payment recorded successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $success = "Sale deleted successfully!";
    }
}

// Helper functions
function getPaymentBadgeClass($method) {
    return match($method) {
        'Cash' => 'cash',
        'Bank Loan' => 'bank-loan',
        'In-House Financing' => 'in-house-financing',
        default => 'other'
    };
}

function getPaymentMethodIcon($method) {
    return match($method) {
        'Cash' => 'fa-money-bill-wave',
        'Bank Loan' => 'fa-university',
        'In-House Financing' => 'fa-building',
        default => 'fa-credit-card'
    };
}

function getTxStatusBadgeClass($status) {
    return match($status) {
        'paid' => 'badge-paid',
        'partial' => 'badge-partial',
        'pending' => 'badge-pending',
        'overdue' => 'badge-overdue',
        default => 'badge-other'
    };
}

function getTxStatusIcon($status) {
    return match($status) {
        'paid' => 'fa-check-circle',
        'partial' => 'fa-circle-half-stroke',
        'pending' => 'fa-clock',
        'overdue' => 'fa-exclamation-triangle',
        default => 'fa-circle'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - Driven Auto Sales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0D0D0D; color: #FFFFFF; }
        .dashboard-container { padding: 20px 30px; max-width: 1400px; margin: 0 auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #FFFFFF; border-left: 4px solid #E50914; padding-left: 15px; margin-bottom: 10px; }
        .page-header p { color: #CCCCCC; margin-left: 19px; font-size: 14px; }
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; animation: slideDown 0.5s ease; display: flex; align-items: center; gap: 12px; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border-left: 4px solid #2ecc71; }
        .alert-danger { background: rgba(229, 9, 20, 0.15); color: #ff6b6b; border-left: 4px solid #E50914; }
        .alert i { font-size: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%); border-radius: 20px; padding: 25px; transition: all 0.3s ease; border: 1px solid #2a2a2a; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); border-color: #E50914; box-shadow: 0 10px 30px rgba(229, 9, 20, 0.2); }
        .stat-icon { position: absolute; right: 25px; top: 25px; font-size: 48px; opacity: 0.2; color: #E50914; }
        .stat-label { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #E50914; font-weight: 600; margin-bottom: 10px; }
        .stat-value { font-size: 42px; font-weight: 800; color: #FFFFFF; margin-bottom: 10px; }
        .stat-trend { font-size: 13px; color: #888; display: flex; align-items: center; gap: 5px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 40px; }
        .section-header:first-of-type { margin-top: 0; }
        .section-header h2 { font-size: 22px; color: #FFFFFF; border-left: 3px solid #E50914; padding-left: 12px; }
        .section-header a { color: #E50914; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s; display: flex; align-items: center; gap: 8px; }
        .section-header a:hover { color: #FF2A2A; transform: translateX(3px); }
        .data-table { background: #1A1A1A; border-radius: 20px; overflow-x: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 16px; background: #0D0D0D; color: #E50914; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 2px solid #E50914; }
        td { padding: 16px; border-bottom: 1px solid #2a2a2a; color: #e0e0e0; font-size: 14px; vertical-align: middle; }
        tr:hover { background: rgba(229, 9, 20, 0.06); transition: background 0.2s; }
        
        /* Status Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-cash { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .badge-bank-loan { background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); }
        .badge-in-house-financing { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .badge-paid { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .badge-pending { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .badge-overdue { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
        .badge-other { background: rgba(155, 89, 182, 0.15); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.3); }
        .badge-partial {
            background: rgba(241, 196, 15, 0.15);
            color: #f1c40f;
            border: 1px solid rgba(241, 196, 15, 0.3);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.85; } }
        
        /* Progress Bar */
        .progress-wrap { width: 100%; background: #2a2a2a; border-radius: 4px; height: 6px; overflow: hidden; }
        .progress-bar { height: 100%; border-radius: 4px; transition: width 0.5s; }
        .progress-bar.success { background: #2ecc71; }
        .progress-bar.warning { background: #f39c12; }
        .progress-bar.danger { background: #e74c3c; }
        .progress-bar.partial {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            animation: shimmer 2s infinite linear;
            background-size: 200% 100%;
        }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        
        /* Quick Actions, Toolbar, Modals, Forms */
        .quick-actions { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .action-btn { background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%); border: 1px solid #2a2a2a; color: #FFFFFF; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .action-btn:hover { border-color: #E50914; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(229, 9, 20, 0.2); }
        .action-btn i { color: #E50914; font-size: 16px; }
        .toolbar { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .search-wrap { position: relative; flex: 1; max-width: 400px; }
        .search-wrap input, .toolbar select { width: 100%; background: #1A1A1A; border: 1px solid #2a2a2a; border-radius: 12px; padding: 10px 15px 10px 40px; color: #FFFFFF; font-size: 14px; transition: all 0.3s; }
        .search-wrap input:focus, .toolbar select:focus { outline: none; border-color: #E50914; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; }
        .toolbar select { padding: 10px 15px; width: 200px; cursor: pointer; }
        .empty-row td { text-align: center; padding: 60px 20px; color: #777; font-size: 16px; }
        .empty-row i { font-size: 48px; color: #333; margin-bottom: 15px; display: block; }
        .price-cell { font-weight: 700; color: #E50914; }
        .amount { font-weight: 600; }
        .amount.green { color: #2ecc71; }
        .amount.red { color: #e74c3c; }
        
        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); display: none; justify-content: center; align-items: center; z-index: 1000; animation: fadeIn 0.3s ease; }
        .modal-overlay.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal { background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%); border-radius: 20px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; border: 1px solid #2a2a2a; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #2a2a2a; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 22px; color: #FFFFFF; border-left: 3px solid #E50914; padding-left: 12px; }
        .close-modal { background: none; border: none; color: #888; font-size: 24px; cursor: pointer; transition: color 0.3s; }
        .close-modal:hover { color: #E50914; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #2a2a2a; display: flex; justify-content: flex-end; gap: 15px; }
        
        /* Form Styles */
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 600; color: #E50914; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select, textarea { background: #0D0D0D; border: 1px solid #2a2a2a; border-radius: 10px; padding: 10px 14px; color: #FFFFFF; font-size: 14px; transition: all 0.3s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #E50914; }
        textarea { resize: vertical; min-height: 80px; }

        /* ── Car Search Dropdown ── */
        .car-search-wrap { position: relative; }
        .car-search-wrap input[type="text"] {
            width: 100%;
            padding-left: 36px;
        }
        .car-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #E50914;
            font-size: 13px;
            pointer-events: none;
            z-index: 1;
        }
        .car-search-wrap.has-value input[type="text"] {
            border-color: #2ecc71;
            color: #2ecc71;
        }
        .car-clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            font-size: 14px;
            padding: 2px 6px;
            border-radius: 4px;
            display: none;
            transition: color 0.2s;
            z-index: 2;
        }
        .car-clear-btn:hover { color: #E50914; }
        .car-search-wrap.has-value .car-clear-btn { display: block; }
        #carDropdown {
            display: none;
            position: absolute;
            top: calc(100% + 2px);
            left: 0; right: 0;
            background: #1A1A1A;
            border: 1px solid #E50914;
            border-radius: 10px;
            max-height: 220px;
            overflow-y: auto;
            z-index: 9999;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        #carDropdown.open { display: block; }
        #carDropdown::-webkit-scrollbar { width: 4px; }
        #carDropdown::-webkit-scrollbar-track { background: #0D0D0D; }
        #carDropdown::-webkit-scrollbar-thumb { background: #E50914; border-radius: 4px; }
        .car-option {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 14px;
            color: #e0e0e0;
            border-bottom: 1px solid #2a2a2a;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .car-option:last-child { border-bottom: none; }
        .car-option:hover, .car-option.highlighted { background: rgba(229, 9, 20, 0.12); }
        .car-option i { color: #E50914; font-size: 12px; flex-shrink: 0; }
        .car-option .car-match { color: #E50914; font-weight: 700; }
        #carNoResults {
            display: none;
            padding: 16px 14px;
            color: #888;
            font-size: 13px;
            text-align: center;
        }
        #carNoResults i { color: #E50914; margin-right: 6px; }
        
        /* ✅ UPDATED: Calc box with editable monthly */
        .calc-box { 
            background: #0D0D0D; 
            border-radius: 12px; 
            padding: 20px; 
            margin-top: 20px; 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px; 
            border: 1px solid #2a2a2a; 
        }
        .calc-item { text-align: center; }
        .calc-item label { font-size: 11px; color: #888; }
        .calc-item .val { font-size: 20px; font-weight: 700; margin-top: 5px; }
        .calc-item .val.gold { color: #E50914; }
        .calc-item .val.blue { color: #3498db; }
        .calc-item .val.green { color: #2ecc71; }
        .calc-item input[type="number"] {
            width: 100%;
            text-align: center;
            padding: 6px 10px;
            background: #0D0D0D;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }
        .calc-item input[type="number"]:focus {
            border-color: #E50914;
            outline: none;
        }
        .calc-item small {
            color: #888;
            font-size: 10px;
            margin-top: 4px;
            display: block;
        }
        
        .section-divider { font-size: 12px; font-weight: 600; color: #E50914; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0 15px; display: flex; align-items: center; gap: 10px; }
        .section-divider::after { content: ''; flex: 1; height: 1px; background: #2a2a2a; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #E50914; color: white; }
        .btn-primary:hover { background: #ff0a1a; transform: translateY(-2px); }
        .btn-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .btn-success:hover { background: rgba(46, 204, 113, 0.3); }
        .btn-danger { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
        .btn-danger:hover { background: rgba(231, 76, 60, 0.3); }
        .btn-ghost { background: rgba(255, 255, 255, 0.05); color: #CCCCCC; border: 1px solid #2a2a2a; }
        .btn-ghost:hover { border-color: #E50914; color: #E50914; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-icon { padding: 8px; }
        
        /* Payment History */
        .payment-list { display: flex; flex-direction: column; gap: 12px; }
        .payment-row { background: #0D0D0D; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px; border: 1px solid #2a2a2a; }
        .pay-num { width: 40px; height: 40px; background: #E50914; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; }
        .pay-info { flex: 1; }
        .pay-amount { font-size: 18px; font-weight: 700; color: #2ecc71; }
        
        /* Toast */
        #toast { position: fixed; bottom: 30px; right: 30px; background: #1A1A1A; border-left: 4px solid #2ecc71; padding: 15px 20px; border-radius: 10px; color: white; z-index: 2000; transform: translateX(400px); transition: transform 0.3s ease; display: flex; align-items: center; gap: 10px; }
        #toast.show { transform: translateX(0); }
        #toast.toast-err { border-left-color: #e74c3c; }
        
        /* Client Detail Modal */
        .client-header { display: flex; gap: 20px; background: #0D0D0D; border-radius: 15px; padding: 20px; margin-bottom: 20px; border: 1px solid #2a2a2a; }
        .client-avatar { width: 60px; height: 60px; background: linear-gradient(135deg, #E50914, #ff6a6a); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: white; }
        .client-meta { flex: 1; }
        .client-fullname { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .client-contacts { display: flex; gap: 20px; flex-wrap: wrap; }
        .client-contact-item { display: flex; align-items: center; gap: 6px; color: #888; font-size: 13px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-card { background: #0D0D0D; border-radius: 12px; padding: 15px; border: 1px solid #2a2a2a; }
        .info-card-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-card-value { font-size: 18px; font-weight: 700; margin-top: 5px; }
        .info-card-value.gold { color: #E50914; }
        .info-card-value.green { color: #2ecc71; }
        .info-card-value.danger { color: #e74c3c; }
        .balance-bar-wrap { margin: 20px 0; }
        .balance-bar-labels { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; color: #888; }
        .balance-bar-track { height: 8px; background: #2a2a2a; border-radius: 4px; overflow: hidden; }
        .balance-bar-fill { height: 100%; border-radius: 4px; transition: width 0.5s; }
        .timeline { display: flex; flex-direction: column; gap: 0; }
        .timeline-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #2a2a2a; }
        .timeline-item:last-child { border-bottom: none; }
        .tl-dot-wrap { display: flex; flex-direction: column; align-items: center; }
        .tl-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
        .tl-dot.dp { background: rgba(229, 9, 20, 0.2); color: #E50914; border: 1px solid rgba(229, 9, 20, 0.3); }
        .tl-dot.paid { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .tl-dot.partial { background: rgba(241, 196, 15, 0.2); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .tl-line { width: 2px; flex: 1; background: #2a2a2a; margin-top: 4px; }
        .tl-content { flex: 1; }
        .tl-title { font-weight: 600; margin-bottom: 4px; }
        .tl-sub { font-size: 12px; color: #888; }
        .tl-amount { font-weight: 700; text-align: right; }
        .tl-date { font-size: 11px; color: #888; text-align: right; margin-top: 4px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; gap: 15px; }
            .stat-value { font-size: 32px; }
            th, td { padding: 12px 10px; font-size: 12px; }
            .form-grid { grid-template-columns: 1fr; }
            .calc-box { grid-template-columns: 1fr; }
            .quick-actions { flex-direction: column; }
            .toolbar { flex-direction: column; }
            .search-wrap { max-width: 100%; }
            .toolbar select { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>
    
    <div class="dashboard-container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Sales Management</h1>
            <p>Record sales, down payments, and track monthly installments.</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="action-btn" onclick="openModal('addSaleModal')">
                <i class="fas fa-plus-circle"></i> New Sale
            </button>
            <a href="admin_dashboard.php" class="action-btn">
                <i class="fas fa-chalkboard-user"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Statistics Cards -->
        <?php
        $totalSales    = count($sales);
        $totalRevenue  = array_sum(array_column($sales, 'final_price'));
        $totalCollected = 0;
        $totalBalance = 0;
        foreach ($sales as $s) {
            $totalCollected += $s['total_paid'];
            $remaining = $s['final_price'] - $s['total_paid'];
            if ($remaining > 0) $totalBalance += $remaining;
        }
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value"><?php echo number_format($totalSales); ?></div>
                <div class="stat-trend"><i class="fas fa-chart-line"></i><span>Completed transactions</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱ <?php echo number_format($totalRevenue, 0); ?></div>
                <div class="stat-trend"><i class="fas fa-tag"></i><span>Total sales value</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-label">Total Collected</div>
                <div class="stat-value">₱ <?php echo number_format($totalCollected, 0); ?></div>
                <div class="stat-trend"><i class="fas fa-check-circle"></i><span>Payments received (paid + partial)</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-label">Outstanding Balance</div>
                <div class="stat-value">₱ <?php echo number_format($totalBalance, 0); ?></div>
                <div class="stat-trend"><i class="fas fa-exclamation-triangle"></i><span>Pending payments</span></div>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Search by customer or car..." onkeyup="filterTable()">
            </div>
            <select id="filterMethod" onchange="filterTable()">
                <option value="">All Payment Methods</option>
                <option value="Cash">Cash</option>
                <option value="Bank Loan">Bank Loan</option>
                <option value="In-House Financing">In-House Financing</option>
            </select>
        </div>
        
        <!-- Sales Table -->
        <div class="section-header">
            <h2><i class="fas fa-list"></i> All Sales Transactions</h2>
        </div>
        
        <div class="data-table">
            <table id="salesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Final Price</th>
                        <th>Down Payment</th>
                        <th>Balance</th>
                        <th>Progress</th>
                        <th>Method</th>
                        <th>Payments</th>
                        <th>Sale Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr class="empty-row">
                            <td colspan="11">
                                <i class="fas fa-chart-line"></i>
                                No sales recorded yet. Click "New Sale" to get started!
                            </td>
                        </tr>
                    <?php else: foreach ($sales as $s):
                        $collected = $s['total_paid'] ?? 0;
                        $balance = max(0, $s['final_price'] - $collected);
                        $pct = $s['final_price'] > 0 ? min(100, round($collected / $s['final_price'] * 100)) : 0;
                        
                        if ($pct >= 100) {
                            $progressClass = 'success';
                        } elseif ($pct > 0) {
                            $progressClass = 'partial';
                        } else {
                            $progressClass = 'danger';
                        }
                        
                        $badgeClass = getPaymentBadgeClass($s['payment_method']);
                        $iconClass = getPaymentMethodIcon($s['payment_method']);
                        
                        $hasPartial = ($s['partial_tx_count'] ?? 0) > 0;
                        $hasPending = ($s['pending_tx_count'] ?? 0) > 0;
                    ?>
                        <tr data-customer="<?php echo htmlspecialchars(strtolower($s['customer_name'])); ?>"
                            data-car="<?php echo htmlspecialchars(strtolower($s['car_name'] ?? '')); ?>"
                            data-method="<?php echo htmlspecialchars($s['payment_method']); ?>">
                            <td>#<?php echo $s['sale_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['customer_name']); ?></strong><br>
                                <small style="color: #888;"><?php echo htmlspecialchars($s['customer_phone']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['car_name'] ?? 'N/A'); ?><br>
                                <small><?php echo $s['terms_months'] !== 'N/A' ? $s['terms_months'] . ' months' : 'Cash'; ?></small>
                            </td>
                            <td class="price-cell">₱ <?php echo number_format($s['final_price'], 2); ?></td>
                            <td>₱ <?php echo number_format($s['down_payment'], 2); ?></td>
                            <td>
                                <?php if ($balance <= 0): ?>
                                    <span class="status-badge badge-paid">
                                        <i class="fas fa-check-circle"></i> Fully Paid
                                    </span>
                                <?php elseif ($hasPartial): ?>
                                    <span class="status-badge badge-partial">
                                        <i class="fas fa-circle-half-stroke"></i> Partial
                                    </span>
                                <?php else: ?>
                                    <span class="amount red">₱ <?php echo number_format($balance, 2); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 100px;">
                                <div style="font-size: 11px; color: #888; margin-bottom: 5px;"><?php echo $pct; ?>% paid</div>
                                <div class="progress-wrap">
                                    <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge badge-<?php echo $badgeClass; ?>">
                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                    <?php echo htmlspecialchars($s['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($hasPartial || $hasPending): ?>
                                    <span style="font-size: 11px; color: #888;">
                                        <?php if ($hasPartial): ?><span class="badge-partial" style="padding:2px 8px;border-radius:10px;font-size:10px;"><i class="fas fa-circle-half-stroke"></i> Partial</span><?php endif; ?>
                                        <?php if ($hasPending): ?><span class="badge-pending" style="padding:2px 8px;border-radius:10px;font-size:10px;margin-left:4px;"><i class="fas fa-clock"></i> Pending</span><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: #888;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($s['sale_date'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button class="btn btn-ghost btn-sm" onclick="openClientModal(<?php echo htmlspecialchars(json_encode($s)); ?>)" title="View Details">
                                        <i class="fas fa-user"></i> Details
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="openPaymentModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                        <i class="fas fa-plus"></i> Payment
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="openDetailModal(<?php echo $s['sale_id']; ?>)">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteSale(<?php echo $s['sale_id']; ?>, '<?php echo htmlspecialchars(addslashes($s['customer_name'])); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ── Add Sale Modal ── -->
    <div class="modal-overlay" id="addSaleModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Record New Sale</h3>
                <button class="close-modal" onclick="closeModal('addSaleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addSaleForm">
                    <div class="section-divider"><i class="fas fa-user"></i> Customer Information</div>
                    <div class="form-grid">
                        <div class="form-group"><label>Customer Name *</label><input type="text" name="customer_name" required placeholder="Full name"></div>
                        <div class="form-group"><label>Phone *</label><input type="text" name="customer_phone" required placeholder="09XXXXXXXXX"></div>
                        <div class="form-group full-width"><label>Email *</label><input type="email" name="customer_email" required placeholder="email@example.com"></div>
                    </div>

                    <div class="section-divider"><i class="fas fa-car"></i> Vehicle & Sale Details</div>
                    <div class="form-grid">

                        <!-- ── SEARCHABLE CAR DROPDOWN ── -->
                        <div class="form-group">
                            <label>Car *</label>
                            <div class="car-search-wrap" id="carSearchWrap">
                                <i class="fas fa-car car-search-icon"></i>
                                <input
                                    type="text"
                                    id="carSearchInput"
                                    placeholder="Type to search car..."
                                    autocomplete="off"
                                    oninput="filterCarDropdown()"
                                    onfocus="openCarDropdown()"
                                    onkeydown="handleCarKey(event)"
                                >
                                <!-- Hidden field that actually submits the car_id -->
                                <input type="hidden" name="car_id" id="carIdHidden">
                                <!-- Clear button -->
                                <button type="button" class="car-clear-btn" id="carClearBtn" onclick="clearCarSelection()" title="Clear selection">
                                    <i class="fas fa-times"></i>
                                </button>
                                <!-- Dropdown list -->
                                <div id="carDropdown">
                                    <?php foreach ($cars as $c): ?>
                                        <div
                                            class="car-option"
                                            data-id="<?php echo $c['car_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['car_name'], ENT_QUOTES); ?>"
                                            onclick="selectCar(this)"
                                        >
                                            <i class="fas fa-car"></i>
                                            <span><?php echo htmlspecialchars($c['car_name']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="carNoResults">
                                        <i class="fas fa-search-minus"></i> No cars found
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ── END SEARCHABLE CAR DROPDOWN ── -->

                        <div class="form-group"><label>Sale Date *</label><input type="date" name="sale_date" required value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="form-group"><label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Loan">Bank Loan</option>
                                <option value="In-House Financing">In-House Financing</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Terms (months)</label>
                            <select name="terms_months" id="termsMonths">
                                <option value="N/A">N/A (Cash)</option>
                                <option value="12">12 months</option>
                                <option value="24">24 months</option>
                                <option value="36">36 months</option>
                                <option value="48">48 months</option>
                                <option value="60">60 months</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-divider"><i class="fas fa-money-bill-wave"></i> Payment Details</div>
                    <div class="form-grid">
                        <div class="form-group"><label>Final Price (₱) *</label><input type="number" name="final_price" id="finalPrice" required min="0" step="0.01" placeholder="0.00" oninput="calcBalance()"></div>
                        <div class="form-group"><label>Down Payment (₱) *</label><input type="number" name="down_payment" id="downPayment" required min="0" step="0.01" placeholder="0.00" oninput="calcBalance()"></div>
                        <div class="form-group full-width"><label>Reference Number</label><input type="text" name="reference_number" placeholder="Optional reference number"></div>
                    </div>
                    
                    <!-- Calc box with editable monthly payment -->
                    <div class="calc-box" id="calcBox">
                        <div class="calc-item">
                            <label>Balance</label>
                            <div class="val blue" id="calcBalance">₱0.00</div>
                        </div>
                        <div class="calc-item">
                            <label>Terms</label>
                            <div class="val" id="calcTerms">—</div>
                        </div>
                        <div class="calc-item">
                            <label>Monthly Due *</label>
                            <input type="number" name="monthly_payment" id="monthlyPayment" 
                                   min="0" step="0.01" placeholder="0.00" 
                                   oninput="validateMonthly()">
                            <small><i class="fas fa-calculator"></i> Auto: <span id="autoMonthly">₱0.00</span></small>
                        </div>
                    </div>

                    <!-- Override checkbox -->
                    <div class="form-group full-width" style="margin-top:10px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="overrideMonthly" onchange="toggleMonthlyOverride()" style="width:auto;">
                            <span style="font-size:12px;color:#888;">Manually set monthly payment (override calculation)</span>
                        </label>
                    </div>
                    
                    <div class="form-group full-width" style="margin-top: 20px;"><label>Notes</label><textarea name="notes" placeholder="Optional notes about the sale..."></textarea></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addSaleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitSale()"><i class="fas fa-save"></i> Save Sale</button>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Modal -->
    <div class="modal-overlay" id="addPaymentModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-money-bill-wave"></i> Record Payment</h3>
                <button class="close-modal" onclick="closeModal('addPaymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="payModalSummary" style="background: #0D0D0D; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #2a2a2a;"></div>
                <form id="addPaymentForm">
                    <input type="hidden" name="sale_id" id="paymentSaleId">
                    <div class="form-grid">
                        <div class="form-group"><label>Payment Amount (₱) *</label><input type="number" name="payment_amount" id="paymentAmount" required min="0" step="0.01" placeholder="0.00"></div>
                        <div class="form-group"><label>Payment Date *</label><input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="form-group"><label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                                <option value="GCash">GCash</option>
                                <option value="PayMaya">PayMaya</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status">
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="form-group full-width"><label>Reference Number</label><input type="text" name="reference_number" placeholder="Check no., GCash ref, etc."></div>
                        <div class="form-group full-width"><label>Notes</label><textarea name="notes" placeholder="Optional notes..."></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('addPaymentModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitPayment()"><i class="fas fa-save"></i> Save Payment</button>
            </div>
        </div>
    </div>
    
    <!-- Payment History Modal -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Payment History</h3>
                <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body" id="detailModalBody"></div>
        </div>
    </div>
    
    <!-- Client Detail Modal -->
    <div class="modal-overlay" id="clientModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> Client Details</h3>
                <button class="close-modal" onclick="closeModal('clientModal')">&times;</button>
            </div>
            <div class="modal-body" id="clientModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('clientModal')">Close</button>
                <button class="btn btn-primary" id="clientPayBtn" onclick="">+ Record Payment</button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div id="toast"><i class="fas fa-check-circle"></i><span>Message</span></div>
    
    <script>
        const txBySale = <?php echo json_encode($txBySale); ?>;
        const salesData = <?php echo json_encode(array_column($sales, null, 'sale_id')); ?>;

        // ── Modal helpers ──────────────────────────────────────────────────────
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            // Reset car search when closing the add sale modal
            if (id === 'addSaleModal') resetCarSearch();
        }
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', e => {
                if (e.target === el) {
                    el.classList.remove('active');
                    if (el.id === 'addSaleModal') resetCarSearch();
                }
            });
        });

        // ── Car Search Dropdown ────────────────────────────────────────────────
        let carHighlightIndex = -1;

        function openCarDropdown() {
            document.getElementById('carDropdown').classList.add('open');
            filterCarDropdown();
        }

        function closeCarDropdown() {
            document.getElementById('carDropdown').classList.remove('open');
            carHighlightIndex = -1;
        }

        function filterCarDropdown() {
            const query = document.getElementById('carSearchInput').value.toLowerCase().trim();
            const options = document.querySelectorAll('.car-option');
            const noResults = document.getElementById('carNoResults');
            let visibleCount = 0;
            carHighlightIndex = -1;

            options.forEach(opt => {
                const name = opt.dataset.name.toLowerCase();
                if (name.includes(query)) {
                    opt.style.display = 'flex';
                    // Highlight matching text
                    const span = opt.querySelector('span');
                    if (query) {
                        const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                        span.innerHTML = opt.dataset.name.replace(regex, '<span class="car-match">$1</span>');
                    } else {
                        span.textContent = opt.dataset.name;
                    }
                    visibleCount++;
                } else {
                    opt.style.display = 'none';
                }
            });

            noResults.style.display = visibleCount === 0 ? 'block' : 'none';

            // If the user typed something, clear the hidden value so validation works
            const hiddenInput = document.getElementById('carIdHidden');
            const wrap = document.getElementById('carSearchWrap');
            if (!query) {
                hiddenInput.value = '';
                wrap.classList.remove('has-value');
            }
        }

        function selectCar(el) {
            const id   = el.dataset.id;
            const name = el.dataset.name;
            document.getElementById('carSearchInput').value = name;
            document.getElementById('carIdHidden').value    = id;
            document.getElementById('carSearchWrap').classList.add('has-value');
            closeCarDropdown();
            // Reset highlighted text back to plain name
            el.querySelector('span').textContent = name;
        }

        function clearCarSelection() {
            document.getElementById('carSearchInput').value = '';
            document.getElementById('carIdHidden').value    = '';
            document.getElementById('carSearchWrap').classList.remove('has-value');
            document.getElementById('carSearchInput').focus();
            filterCarDropdown();
            openCarDropdown();
        }

        function resetCarSearch() {
            clearCarSelection();
            closeCarDropdown();
        }

        // Keyboard navigation inside the car dropdown
        function handleCarKey(e) {
            const dropdown = document.getElementById('carDropdown');
            const options  = [...dropdown.querySelectorAll('.car-option:not([style*="display: none"])')];

            if (!dropdown.classList.contains('open')) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') openCarDropdown();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                carHighlightIndex = Math.min(carHighlightIndex + 1, options.length - 1);
                updateCarHighlight(options);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                carHighlightIndex = Math.max(carHighlightIndex - 1, 0);
                updateCarHighlight(options);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (carHighlightIndex >= 0 && options[carHighlightIndex]) {
                    selectCar(options[carHighlightIndex]);
                }
            } else if (e.key === 'Escape') {
                closeCarDropdown();
            }
        }

        function updateCarHighlight(options) {
            options.forEach((opt, i) => {
                opt.classList.toggle('highlighted', i === carHighlightIndex);
                if (i === carHighlightIndex) opt.scrollIntoView({ block: 'nearest' });
            });
        }

        // Close car dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('carSearchWrap');
            if (wrap && !wrap.contains(e.target)) {
                closeCarDropdown();
                // If user typed but didn't select, revert to last valid selection or clear
                const hidden = document.getElementById('carIdHidden');
                if (!hidden.value) {
                    document.getElementById('carSearchInput').value = '';
                    document.getElementById('carSearchWrap').classList.remove('has-value');
                }
            }
        });

        // ── Calc / Monthly Payment ─────────────────────────────────────────────
        function calcBalance() {
            const price = parseFloat(document.getElementById('finalPrice').value) || 0;
            const dp = parseFloat(document.getElementById('downPayment').value) || 0;
            const termsEl = document.getElementById('termsMonths');
            const terms = termsEl ? parseInt(termsEl.value) || 0 : 0;
            
            const balance = Math.max(0, price - dp);
            const autoMonthly = (terms > 0 && balance > 0) ? (balance / terms) : 0;
            
            document.getElementById('calcBalance').textContent = '₱' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('autoMonthly').textContent = '₱' + autoMonthly.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('calcTerms').textContent = terms > 0 ? terms + ' months' : '—';
            
            if (!document.getElementById('overrideMonthly').checked) {
                document.getElementById('monthlyPayment').value = autoMonthly > 0 ? autoMonthly.toFixed(2) : '';
            }
        }

        function toggleMonthlyOverride() {
            const input = document.getElementById('monthlyPayment');
            const isOverride = document.getElementById('overrideMonthly').checked;
            if (isOverride) {
                input.focus();
                input.select();
            } else {
                calcBalance();
            }
        }

        function validateMonthly() {
            const monthly = parseFloat(document.getElementById('monthlyPayment').value) || 0;
            const balanceText = document.getElementById('calcBalance').textContent;
            const balance = parseFloat(balanceText.replace(/[^0-9.]/g, '')) || 0;
            const terms = parseInt(document.getElementById('termsMonths').value) || 0;
            if (terms > 0 && monthly * terms > balance * 1.01) {
                document.getElementById('monthlyPayment').style.borderColor = '#e74c3c';
            } else {
                document.getElementById('monthlyPayment').style.borderColor = '#2a2a2a';
            }
        }
        
        document.addEventListener('change', e => { 
            if (['final_price', 'down_payment', 'terms_months'].includes(e.target.name)) {
                calcBalance(); 
            }
        });

        // ── Submit Sale ────────────────────────────────────────────────────────
        function submitSale() {
            // Validate car selection manually since it's a hidden input
            const carId = document.getElementById('carIdHidden').value;
            if (!carId) {
                document.getElementById('carSearchInput').focus();
                document.getElementById('carSearchInput').style.borderColor = '#e74c3c';
                toast('Please select a car from the list.', 'err');
                return;
            }
            document.getElementById('carSearchInput').style.borderColor = '';

            const form = document.getElementById('addSaleForm');
            if (!form.reportValidity()) return;
            
            const data = new FormData(form);
            data.append('action', 'add_sale');
            
            const monthlyVal = document.getElementById('monthlyPayment').value;
            if (monthlyVal === '') {
                data.set('monthly_payment', '0');
            }
            
            fetch('', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) { 
                        toast('Sale recorded successfully!', 'ok'); 
                        closeModal('addSaleModal'); 
                        setTimeout(() => location.reload(), 800); 
                    } else { 
                        toast(res.error || 'Error saving sale', 'err'); 
                    }
                })
                .catch(err => toast('Network error: ' + err.message, 'err'));
        }

        // ── Payment Modal ──────────────────────────────────────────────────────
        function openPaymentModal(sale) {
            document.getElementById('paymentSaleId').value = sale.sale_id;
            const totalPaid = parseFloat(sale.total_paid) || 0;
            const balance = Math.max(0, parseFloat(sale.final_price) - totalPaid);
            const monthly = parseFloat(sale.monthly_payment) || 0;
            document.getElementById('paymentAmount').value = monthly > 0 && balance > 0 ? Math.min(monthly, balance).toFixed(2) : balance.toFixed(2);
            document.getElementById('payModalSummary').innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div><div style="font-size: 11px; color: #888;">Customer</div><div style="font-weight: 600;">${sale.customer_name}</div></div>
                    <div><div style="font-size: 11px; color: #888;">Balance</div><div style="font-weight: 600; color: #e74c3c;">₱${balance.toLocaleString('en-US', {minimumFractionDigits: 2})}</div></div>
                    <div><div style="font-size: 11px; color: #888;">Monthly Due</div><div style="font-weight: 600; color: #E50914;">${monthly > 0 ? '₱' + monthly.toLocaleString('en-US', {minimumFractionDigits: 2}) : '—'}</div></div>
                </div>`;
            openModal('addPaymentModal');
        }
        
        function submitPayment() {
            const form = document.getElementById('addPaymentForm');
            if (!form.reportValidity()) return;
            const data = new FormData(form);
            data.append('action', 'add_payment');
            fetch('', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) { toast('Payment recorded!', 'ok'); closeModal('addPaymentModal'); setTimeout(() => location.reload(), 800); }
                    else { toast(res.error || 'Error recording payment', 'err'); }
                });
        }

        // ── Detail / History Modal ─────────────────────────────────────────────
        function openDetailModal(saleId) {
            const txs = txBySale[saleId] || [];
            let html = '';
            if (txs.length === 0) {
                html = '<div class="empty-row"><td colspan="1"><i class="fas fa-credit-card"></i>No payment records yet.</td></div>';
            } else {
                html = '<div class="payment-list">';
                txs.forEach(tx => {
                    const statusClass = getTxStatusBadgeClass(tx.status);
                    const statusIcon  = getTxStatusIcon(tx.status);
                    html += `
                        <div class="payment-row">
                            <div class="pay-num">#${tx.payment_number}</div>
                            <div class="pay-info">
                                <div style="font-weight: 600;">${tx.payment_method}${tx.reference_number ? ' · ' + tx.reference_number : ''}</div>
                                <div style="font-size: 12px; color: #888;">${new Date(tx.payment_date).toLocaleDateString('en-PH', {month: 'short', day: 'numeric', year: 'numeric'})}</div>
                                ${tx.notes ? '<div style="font-size: 12px; color: #888;">' + tx.notes + '</div>' : ''}
                            </div>
                            <div style="text-align: right;">
                                <div class="pay-amount">₱${parseFloat(tx.payment_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                <div><span class="status-badge ${statusClass}"><i class="fas ${statusIcon}"></i> ${tx.status}</span></div>
                            </div>
                        </div>`;
                });
                html += '</div>';
            }
            document.getElementById('detailModalBody').innerHTML = html;
            openModal('detailModal');
        }

        // ── Delete Sale ────────────────────────────────────────────────────────
        function deleteSale(id, name) {
            if (!confirm(`Delete sale for "${name}"? This will remove all payment records too.`)) return;
            const data = new FormData();
            data.append('action', 'delete_sale');
            data.append('sale_id', id);
            fetch('', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) { toast('Sale deleted.', 'ok'); setTimeout(() => location.reload(), 600); }
                    else { toast(res.error || 'Error deleting sale', 'err'); }
                });
        }

        // ── Table Filter ───────────────────────────────────────────────────────
        function filterTable() {
            const q      = document.getElementById('searchInput').value.toLowerCase();
            const method = document.getElementById('filterMethod').value;
            document.querySelectorAll('#salesTable tbody tr[data-customer]').forEach(row => {
                const matchQ = row.dataset.customer.includes(q) || row.dataset.car.includes(q);
                const matchM = !method || row.dataset.method === method;
                row.style.display = (matchQ && matchM) ? '' : 'none';
            });
        }

        // ── Client Modal ───────────────────────────────────────────────────────
        function openClientModal(sale) {
            const txs        = txBySale[sale.sale_id] || [];
            const finalPrice = parseFloat(sale.final_price) || 0;
            const downPay    = parseFloat(sale.down_payment) || 0;
            const totalPaid  = parseFloat(sale.total_paid) || 0;
            const balance    = Math.max(0, finalPrice - totalPaid);
            const monthly    = parseFloat(sale.monthly_payment) || 0;
            const pct        = finalPrice > 0 ? Math.min(100, Math.round(totalPaid / finalPrice * 100)) : 0;
            const initials   = sale.customer_name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
            
            let timelineHtml = '';
            if (txs.length === 0) {
                timelineHtml = '<div class="empty-row"><td colspan="1"><i class="fas fa-credit-card"></i>No transactions yet.</td></div>';
            } else {
                const sorted = [...txs].sort((a, b) => a.payment_number - b.payment_number);
                sorted.forEach((tx, i) => {
                    const isDP      = i === 0 && tx.notes && tx.notes.toLowerCase().includes('down');
                    const dotCls    = isDP ? 'dp' : (tx.status === 'partial' ? 'partial' : 'paid');
                    const label     = isDP ? 'DP' : '#' + tx.payment_number;
                    const hasLine   = i < sorted.length - 1;
                    const txDate    = new Date(tx.payment_date).toLocaleDateString('en-PH', {month: 'short', day: 'numeric', year: 'numeric'});
                    const statusBadge = getTxStatusBadgeClass(tx.status);
                    const statusIcon  = getTxStatusIcon(tx.status);
                    timelineHtml += `
                        <div class="timeline-item">
                            <div class="tl-dot-wrap">
                                <div class="tl-dot ${dotCls}">${label}</div>
                                ${hasLine ? '<div class="tl-line"></div>' : ''}
                            </div>
                            <div class="tl-content">
                                <div class="tl-title">${isDP ? 'Down Payment' : 'Monthly Payment #' + tx.payment_number}</div>
                                <div class="tl-sub">${tx.payment_method}${tx.reference_number ? ' · Ref: ' + tx.reference_number : ''}${tx.notes && !isDP ? ' · ' + tx.notes : ''}</div>
                            </div>
                            <div>
                                <div class="tl-amount" style="color: #2ecc71;">₱${parseFloat(tx.payment_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                <div class="tl-date">${txDate}</div>
                                <div><span class="status-badge ${statusBadge}" style="margin-top:4px;font-size:10px;padding:2px 6px;"><i class="fas ${statusIcon}"></i> ${tx.status}</span></div>
                            </div>
                        </div>`;
                });
            }
            
            const html = `
                <div class="client-header">
                    <div class="client-avatar">${initials}</div>
                    <div class="client-meta">
                        <div class="client-fullname">${sale.customer_name}</div>
                        <div class="client-contacts">
                            <div class="client-contact-item"><i class="fas fa-phone"></i> ${sale.customer_phone}</div>
                            <div class="client-contact-item"><i class="fas fa-envelope"></i> ${sale.customer_email}</div>
                            <div class="client-contact-item"><i class="fas fa-calendar"></i> Sale: ${new Date(sale.sale_date).toLocaleDateString('en-PH')}</div>
                        </div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-card"><div class="info-card-label">Car</div><div class="info-card-value">${sale.car_name || 'N/A'}</div></div>
                    <div class="info-card"><div class="info-card-label">Final Price</div><div class="info-card-value gold">₱${finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2})}</div></div>
                    <div class="info-card"><div class="info-card-label">Down Payment</div><div class="info-card-value">₱${downPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</div></div>
                    <div class="info-card"><div class="info-card-label">Total Paid</div><div class="info-card-value green">₱${totalPaid.toLocaleString('en-US', {minimumFractionDigits: 2})}</div></div>
                    <div class="info-card"><div class="info-card-label">Outstanding Balance</div><div class="info-card-value ${balance <= 0 ? 'green' : 'danger'}">₱${balance.toLocaleString('en-US', {minimumFractionDigits: 2})}</div></div>
                    <div class="info-card"><div class="info-card-label">Monthly Payment</div><div class="info-card-value gold">${monthly > 0 ? '₱' + monthly.toLocaleString('en-US', {minimumFractionDigits: 2}) : '—'}</div></div>
                </div>
                <div class="balance-bar-wrap">
                    <div class="balance-bar-labels">
                        <span>Payment Progress</span>
                        <span style="color: ${balance <= 0 ? '#2ecc71' : '#E50914'}">${pct}% paid</span>
                    </div>
                    <div class="balance-bar-track">
                        <div class="balance-bar-fill" style="width: ${pct}%; background: ${pct >= 100 ? '#2ecc71' : (pct > 0 ? 'linear-gradient(90deg, #f39c12, #f1c40f)' : '#e74c3c')};"></div>
                    </div>
                </div>
                ${sale.notes ? `<div style="background: #0D0D0D; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #2a2a2a;"><strong style="color: #E50914;">Notes:</strong><br>${sale.notes}</div>` : ''}
                <div class="section-divider"><i class="fas fa-history"></i> Payment Timeline</div>
                <div class="timeline">${timelineHtml}</div>`;
            
            document.getElementById('clientModalBody').innerHTML = html;
            const payBtn = document.getElementById('clientPayBtn');
            payBtn.style.display = balance <= 0 ? 'none' : '';
            payBtn.onclick = () => { closeModal('clientModal'); openPaymentModal(sale); };
            openModal('clientModal');
        }

        // ── Helper JS functions (mirror PHP) ──────────────────────────────────
        function getTxStatusBadgeClass(status) {
            const map = { paid: 'badge-paid', partial: 'badge-partial', pending: 'badge-pending', overdue: 'badge-overdue' };
            return map[status] || 'badge-other';
        }
        function getTxStatusIcon(status) {
            const map = { paid: 'fa-check-circle', partial: 'fa-circle-half-stroke', pending: 'fa-clock', overdue: 'fa-exclamation-triangle' };
            return map[status] || 'fa-circle';
        }

        // ── Toast ──────────────────────────────────────────────────────────────
        function toast(msg, type = 'ok') {
            const el = document.getElementById('toast');
            el.className = type === 'err' ? 'toast-err' : '';
            el.querySelector('span').textContent = msg;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>