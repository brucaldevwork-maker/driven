<?php
require_once '../include/config.php';
requireAdminLogin();

// ── Helper Functions ─────────────────────────────────────────────────────────
// ✅ NEW: Get transaction status badge class
function getTxStatusBadgeClass($status) {
    return match($status) {
        'paid'     => 'badge-paid',
        'partial'  => 'badge-partial',
        'pending'  => 'badge-pending',
        'overdue'  => 'badge-overdue',
        default    => 'badge-other'
    };
}

// ✅ NEW: Get transaction status icon
function getTxStatusIcon($status) {
    return match($status) {
        'paid'     => 'fa-check-circle',
        'partial'  => 'fa-circle-half-stroke',
        'pending'  => 'fa-clock',
        'overdue'  => 'fa-exclamation-triangle',
        default    => 'fa-circle'
    };
}

// ── KPI Queries ───────────────────────────────────────────────────────────────
$kpi = $pdo->query("
    SELECT
        COUNT(*)                        AS total_sales,
        SUM(final_price)                AS total_value,
        SUM(down_payment)               AS total_down,
        SUM(monthly_payment)            AS total_monthly,
        SUM(CASE WHEN payment_status='completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN payment_status='active'    THEN 1 ELSE 0 END) AS active_cnt,
        SUM(CASE WHEN payment_status='defaulted' THEN 1 ELSE 0 END) AS defaulted
    FROM sales
")->fetch();

$txKpi = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN status IN ('paid', 'partial') THEN payment_amount ELSE 0 END),0) AS total_collected,
        COALESCE(SUM(CASE WHEN status='pending' THEN payment_amount ELSE 0 END),0) AS total_pending,
        COALESCE(SUM(CASE WHEN status='overdue' THEN payment_amount ELSE 0 END),0) AS total_overdue,
        COUNT(*) AS total_tx
    FROM payment_transactions
")->fetch();

// ── Sales by Payment Method ───────────────────────────────────────────────────
$byMethod = $pdo->query("
    SELECT payment_method, COUNT(*) AS cnt, SUM(final_price) AS total
    FROM sales
    GROUP BY payment_method
    ORDER BY total DESC
")->fetchAll();

// ── Sales by Month ────────────────────────────────────────────────────────────
$byMonth = $pdo->query("
    SELECT DATE_FORMAT(sale_date,'%b %Y') AS month_label,
           DATE_FORMAT(sale_date,'%Y-%m') AS month_sort,
           COUNT(*) AS cnt, SUM(final_price) AS total
    FROM sales
    GROUP BY month_sort, month_label
    ORDER BY month_sort ASC
")->fetchAll();

// ── Payments Collected by Month ───────────────────────────────────────────────
$txByMonth = $pdo->query("
    SELECT DATE_FORMAT(payment_date,'%b %Y') AS month_label,
           DATE_FORMAT(payment_date,'%Y-%m') AS month_sort,
           SUM(CASE WHEN status IN ('paid', 'partial') THEN payment_amount ELSE 0 END) AS collected,
           SUM(CASE WHEN status='pending' THEN payment_amount ELSE 0 END) AS pending_amt
    FROM payment_transactions
    GROUP BY month_sort, month_label
    ORDER BY month_sort ASC
")->fetchAll();

// ── Sales by Vehicle Category ─────────────────────────────────────────────────
$byCategory = $pdo->query("
    SELECT cat.category_name, COUNT(s.sale_id) AS cnt, SUM(s.final_price) AS total
    FROM sales s
    JOIN cars c ON s.car_id = c.car_id
    JOIN categories cat ON c.category_id = cat.category_id
    GROUP BY cat.category_name
    ORDER BY total DESC
")->fetchAll();

// ── Collected by Transaction Payment Method ───────────────────────────────────
$txByMethod = $pdo->query("
    SELECT payment_method, COUNT(*) AS cnt, SUM(payment_amount) AS total
    FROM payment_transactions
    WHERE status IN ('paid', 'partial')
    GROUP BY payment_method
    ORDER BY total DESC
")->fetchAll();

// ── Top 5 Sales ───────────────────────────────────────────────────────────────
// ✅ FIXED: Include payment status breakdown for visual indicators
$topSales = $pdo->query("
    SELECT s.sale_id, s.customer_name, s.customer_phone, s.final_price,
           s.down_payment, s.monthly_payment, s.terms_months,
           s.sale_date, s.payment_method, s.payment_status,
           c.car_name,
           COALESCE(SUM(pt.payment_amount),0) AS collected,
           -- Count transactions by status
           SUM(CASE WHEN pt.status='paid' THEN 1 ELSE 0 END) AS paid_count,
           SUM(CASE WHEN pt.status='partial' THEN 1 ELSE 0 END) AS partial_count,
           SUM(CASE WHEN pt.status='pending' THEN 1 ELSE 0 END) AS pending_count
    FROM sales s
    LEFT JOIN cars c ON s.car_id = c.car_id
    LEFT JOIN payment_transactions pt ON s.sale_id = pt.sale_id AND pt.status IN ('paid', 'partial', 'pending', 'overdue')
    GROUP BY s.sale_id
    ORDER BY s.final_price DESC
    LIMIT 5
")->fetchAll();

// ── All Sales ─────────────────────────────────────────────────────────────────
// ✅ FIXED: Include payment status breakdown
$allSales = $pdo->query("
    SELECT s.*, c.car_name,
           COALESCE(SUM(pt.payment_amount),0) AS collected,
           -- ✅ NEW: Count payments by status for visual indicators
           SUM(CASE WHEN pt.status='paid' THEN 1 ELSE 0 END) AS paid_count,
           SUM(CASE WHEN pt.status='partial' THEN 1 ELSE 0 END) AS partial_count,
           SUM(CASE WHEN pt.status='pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN pt.status='overdue' THEN 1 ELSE 0 END) AS overdue_count
    FROM sales s
    LEFT JOIN cars c ON s.car_id = c.car_id
    LEFT JOIN payment_transactions pt ON s.sale_id = pt.sale_id AND pt.status IN ('paid', 'partial', 'pending', 'overdue')
    GROUP BY s.sale_id
    ORDER BY s.sale_date DESC
")->fetchAll();

// ── Recent Transactions ───────────────────────────────────────────────────────
$recentTx = $pdo->query("
    SELECT pt.*, s.customer_name, c.car_name
    FROM payment_transactions pt
    JOIN sales s ON pt.sale_id = s.sale_id
    LEFT JOIN cars c ON s.car_id = c.car_id
    ORDER BY pt.payment_date DESC, pt.transaction_id DESC
    LIMIT 10
")->fetchAll();

// ── JS chart data ─────────────────────────────────────────────────────────────
$monthLabels    = json_encode(array_column($byMonth,    'month_label'));
$monthTotals    = json_encode(array_column($byMonth,    'total'));
$txMonthLabels  = json_encode(array_column($txByMonth,  'month_label'));
$txCollected    = json_encode(array_column($txByMonth,  'collected'));
$txPending      = json_encode(array_column($txByMonth,  'pending_amt'));
$methodLabels   = json_encode(array_column($byMethod,   'payment_method'));
$methodTotals   = json_encode(array_column($byMethod,   'total'));
$catLabels      = json_encode(array_column($byCategory, 'category_name'));
$catTotals      = json_encode(array_column($byCategory, 'total'));
$txMLabels      = json_encode(array_column($txByMethod, 'payment_method'));
$txMTotals      = json_encode(array_column($txByMethod, 'total'));
?>
<?php require_once '../include/admin_nav.php'; ?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
        --bg-primary: #000000; --bg-secondary: #0a0a0a; --bg-card: #111111;
        --bg-elevated: #1a1a1a; --border-color: #2a2a2a;
        --accent-primary: #dc2626; --accent-secondary: #ef4444; --accent-dark: #b91c1c;
        --text-primary: #ffffff; --text-secondary: #9ca3af; --text-muted: #6b7280;
        --success: #10b981; --info: #3b82f6; --warning: #f59e0b; --danger: #ef4444;
        --partial: #818cf8;
    }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg-primary); color: var(--text-primary); line-height: 1.5; }
    @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap');
    
    /* Scrollbar */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg-secondary); border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--accent-primary); }

    .sr-page { padding: 28px 32px 48px; max-width: 1600px; margin: 0 auto; min-height: calc(100vh - 70px); }
    .sr-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 16px; }
    .sr-title { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #fff 0%, var(--accent-primary) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: -0.3px; }
    .sr-title span { background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .sr-sub { color: var(--text-secondary); font-size: 14px; margin-top: 8px; display: flex; align-items: center; gap: 8px; }
    .sr-sub i { color: var(--accent-primary); font-size: 12px; }
    .sr-date { background: var(--bg-card); padding: 8px 18px; border-radius: 40px; font-size: 12px; font-weight: 500; color: var(--text-secondary); border: 1px solid var(--border-color); }

    /* KPI Grid */
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
    .kpi-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 20px 24px; position: relative; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5); }
    .kpi-card:hover { transform: translateY(-4px); border-color: var(--accent-primary); box-shadow: 0 12px 28px rgba(220, 38, 38, 0.25); }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--accent-primary); border-radius: 20px 0 0 20px; }
    .kpi-card.green::before { background: var(--success); }
    .kpi-card.blue::before { background: var(--info); }
    .kpi-card.amber::before { background: var(--warning); }
    .kpi-card.partial::before { background: var(--partial); }

    .kpi-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; color: var(--text-secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .kpi-label i { font-size: 14px; color: var(--accent-primary); }
    .kpi-value { font-size: 34px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; margin-bottom: 6px; }
    .kpi-value.red { color: var(--accent-primary); } .kpi-value.green { color: var(--success); }
    .kpi-value.blue { color: var(--info); } .kpi-value.amber { color: var(--warning); }
    .kpi-value.partial { color: var(--partial); }
    .kpi-sub { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
    .kpi-pills { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
    .kpi-pill { font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 30px; background: rgba(220, 38, 38, 0.12); color: var(--accent-primary); }
    .kpi-pill.active { background: rgba(16, 185, 129, 0.12); color: var(--success); }
    .kpi-pill.completed { background: rgba(59, 130, 246, 0.12); color: var(--info); }
    .kpi-pill.defaulted { background: rgba(239, 68, 68, 0.12); color: var(--danger); }
    .kpi-pill.partial { background: rgba(129, 140, 248, 0.12); color: var(--partial); }

    /* Section Label */
    .section-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--accent-primary); margin-bottom: 20px; margin-top: 8px; display: flex; align-items: center; gap: 12px; }
    .section-label::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, var(--border-color), transparent); }

    /* Charts */
    .chart-grid-2 { display: grid; grid-template-columns: 1.7fr 1fr; gap: 20px; margin-bottom: 28px; }
    .chart-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; }
    .chart-card, .chart-card-full { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; padding: 20px; transition: all 0.2s ease; }
    .chart-card:hover, .chart-card-full:hover { border-color: var(--accent-primary); }
    .chart-card-full { margin-bottom: 28px; }
    .chart-title { font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .chart-subtitle { font-size: 11px; color: var(--text-muted); margin-bottom: 16px; }
    .chart-wrap { position: relative; width: 100%; min-height: 200px; }
    .legend { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 12px; font-size: 11px; color: var(--text-secondary); }
    .legend-item { display: flex; align-items: center; gap: 6px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* Method Summary */
    .method-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
    .method-item { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; transition: all 0.2s; }
    .method-item:hover { border-color: var(--accent-primary); transform: translateY(-2px); }
    .method-name { font-size: 11px; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.6px; display: flex; align-items: center; gap: 6px; }
    .method-val { font-size: 18px; font-weight: 700; }
    .method-count { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    /* Tables */
    .table-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; overflow: hidden; margin-bottom: 28px; }
    .table-head { padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .table-title { font-size: 14px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .table-count { font-size: 12px; color: var(--text-muted); background: var(--bg-secondary); padding: 4px 10px; border-radius: 20px; }
    .tbl-overflow { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    thead th { background: var(--bg-secondary); padding: 12px 16px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--accent-primary); font-weight: 700; white-space: nowrap; border-bottom: 1px solid var(--border-color); }
    tbody td { padding: 12px 16px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: rgba(220, 38, 38, 0.05); }
    .td-main { color: var(--text-primary); font-weight: 500; }
    .td-muted { color: var(--text-muted); font-size: 12px; margin-top: 2px; }
    .amount { font-weight: 600; color: var(--text-primary); }
    .amt-red { color: var(--accent-primary); } .amt-green { color: var(--success); } .amt-amber { color: var(--warning); }

    /* Badges */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 30px; font-size: 10px; font-weight: 600; letter-spacing: 0.3px; }
    .badge-active { background: rgba(16, 185, 129, 0.12); color: var(--success); }
    .badge-completed { background: rgba(59, 130, 246, 0.12); color: var(--info); }
    .badge-defaulted { background: rgba(239, 68, 68, 0.12); color: var(--danger); }
    .badge-paid { background: rgba(16, 185, 129, 0.12); color: var(--success); }
    .badge-pending { background: rgba(245, 158, 11, 0.12); color: var(--warning); }
    .badge-overdue { background: rgba(239, 68, 68, 0.12); color: var(--danger); }
    .badge-partial { background: rgba(129, 140, 248, 0.12); color: var(--partial); animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.85; } }
    .badge-other { background: rgba(155, 89, 182, 0.12); color: #9b59b6; }
    .badge-cash { background: rgba(220, 38, 38, 0.12); color: var(--accent-primary); }
    .badge-bank { background: rgba(59, 130, 246, 0.12); color: var(--info); }
    .badge-inhouse { background: rgba(245, 158, 11, 0.12); color: var(--warning); }

    /* Progress Bar */
    .prog-wrap { width: 90px; height: 5px; background: var(--bg-secondary); border-radius: 4px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 6px; }
    .prog-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
    .prog-fill.success { background: var(--success); }
    .prog-fill.warning { background: var(--warning); }
    .prog-fill.danger { background: var(--danger); }
    .prog-fill.partial {
        background: linear-gradient(90deg, var(--warning) 0%, var(--partial) 100%);
        animation: shimmer 2s infinite linear;
        background-size: 200% 100%;
    }
    @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }

    /* Payment Status Indicators */
    .payment-status { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; }
    .payment-status .dot { width: 6px; height: 6px; border-radius: 50%; }
    .payment-status .dot.paid { background: var(--success); }
    .payment-status .dot.partial { background: var(--partial); animation: pulse 2s infinite; }
    .payment-status .dot.pending { background: var(--warning); }
    .payment-status .dot.overdue { background: var(--danger); }

    /* Rank Column */
    .rank-cell { font-weight: 700; font-size: 18px; color: var(--accent-primary); text-align: center; }

    /* Responsive */
    @media (max-width: 1200px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .chart-grid-2 { grid-template-columns: 1fr; }
        .chart-grid-3 { grid-template-columns: repeat(2, 1fr); }
        .method-summary { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .sr-page { padding: 20px 16px 32px; }
        .kpi-grid { grid-template-columns: 1fr; gap: 12px; }
        .chart-grid-3 { grid-template-columns: 1fr; }
        .method-summary { grid-template-columns: 1fr; }
        .sr-header { flex-direction: column; align-items: flex-start; }
        .table-head { flex-direction: column; align-items: flex-start; }
        thead th, tbody td { padding: 10px 12px; font-size: 11px; }
    }
</style>

<div class="sr-page">
    <!-- Page Header -->
    <div class="sr-header">
        <div>
            <div class="sr-title">
                <i class="fas fa-chart-line" style="font-size: 26px; margin-right: 8px; color: var(--accent-primary);"></i>
                Sales <span>Analytics</span>
            </div>
            <div class="sr-sub">
                <i class="fas fa-chart-pie"></i> Full overview of sales activity and payment collections
            </div>
        </div>
        <div class="sr-date">
            <i class="far fa-calendar-alt"></i> <?= date('F j, Y · g:i A') ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-chart-simple"></i> Total Sales Value</div>
            <div class="kpi-value red">₱<?= number_format($kpi['total_value'] ?? 0, 0) ?></div>
            <div class="kpi-sub"><i class="fas fa-exchange-alt"></i> <?= $kpi['total_sales'] ?? 0 ?> transactions</div>
            <div class="kpi-pills">
                <span class="kpi-pill active"><i class="fas fa-play-circle"></i> <?= $kpi['active_cnt'] ?? 0 ?> active</span>
                <span class="kpi-pill completed"><i class="fas fa-check-circle"></i> <?= $kpi['completed'] ?? 0 ?> completed</span>
                <?php if (!empty($kpi['defaulted']) && $kpi['defaulted'] > 0): ?>
                <span class="kpi-pill defaulted"><i class="fas fa-exclamation-triangle"></i> <?= $kpi['defaulted'] ?> defaulted</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-label"><i class="fas fa-coins"></i> Total Collected</div>
            <div class="kpi-value green">₱<?= number_format($txKpi['total_collected'] ?? 0, 0) ?></div>
            <div class="kpi-sub"><i class="fas fa-receipt"></i> <?= $txKpi['total_tx'] ?? 0 ?> payment records (paid + partial)</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-label"><i class="fas fa-hand-holding-usd"></i> Down Payments</div>
            <div class="kpi-value blue">₱<?= number_format($kpi['total_down'] ?? 0, 0) ?></div>
            <div class="kpi-sub"><i class="fas fa-chart-line"></i> Across all sales</div>
        </div>
        <div class="kpi-card amber">
            <div class="kpi-label"><i class="fas fa-clock"></i> Pending / Overdue</div>
            <div class="kpi-value amber">₱<?= number_format(($txKpi['total_pending'] ?? 0) + ($txKpi['total_overdue'] ?? 0), 0) ?></div>
            <div class="kpi-sub">
                <?= ($txKpi['total_overdue'] ?? 0) > 0 ? '<i class="fas fa-exclamation-circle"></i> ₱'.number_format($txKpi['total_overdue']).' overdue' : '<i class="fas fa-check"></i> No overdue payments' ?>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="section-label"><i class="fas fa-chart-line"></i> Sales Performance</div>
    <div class="chart-grid-2">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-column"></i> Monthly Sales Value</div>
            <div class="chart-subtitle">Total deal value closed per month</div>
            <div class="chart-wrap" style="height: 240px"><canvas id="monthlySalesChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-chart-pie"></i> Sales by Payment Method</div>
            <div class="chart-subtitle">Share of total deal value</div>
            <div class="legend">
                <?php $mColors = ['#dc2626', '#3b82f6', '#f59e0b'];
                foreach ($byMethod as $i => $m): ?>
                <div class="legend-item">
                    <span class="legend-dot" style="background:<?= $mColors[$i % 3] ?>"></span>
                    <?= htmlspecialchars($m['payment_method']) ?> — ₱<?= number_format(($m['total'] ?? 0)/1000000,1) ?>M
                </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-wrap" style="height: 180px"><canvas id="methodDonut"></canvas></div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="section-label"><i class="fas fa-hand-holding-usd"></i> Payment Analytics</div>
    <div class="chart-grid-3">
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-hand-holding-usd"></i> Payments Collected</div>
            <div class="chart-subtitle">Paid + Partial vs Pending by month</div>
            <div class="legend">
                <div class="legend-item"><span class="legend-dot" style="background:#10b981"></span> Collected (paid+partial)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#f59e0b"></span> Pending</div>
            </div>
            <div class="chart-wrap" style="height: 180px"><canvas id="txMonthChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-car"></i> Sales by Category</div>
            <div class="chart-subtitle">Value per vehicle type</div>
            <div class="chart-wrap" style="height: 220px"><canvas id="categoryChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-credit-card"></i> Collection Method Mix</div>
            <div class="chart-subtitle">How paid+partial transactions came in</div>
            <div class="chart-wrap" style="height: 220px"><canvas id="txMethodChart"></canvas></div>
        </div>
    </div>

    <!-- Method Summary -->
    <div class="section-label"><i class="fas fa-chart-simple"></i> Payment Method Breakdown</div>
    <div class="chart-card-full">
        <div class="chart-title"><i class="fas fa-chart-simple"></i> Payment Method Breakdown</div>
        <div class="chart-subtitle">Total value and count per payment method</div>
        <div class="method-summary">
            <?php $mStyles = ['Cash'=>['#dc2626'], 'Bank Loan'=>['#3b82f6'], 'In-House Financing'=>['#f59e0b']];
            foreach ($byMethod as $m): $color = $mStyles[$m['payment_method']][0] ?? '#dc2626'; ?>
            <div class="method-item">
                <div class="method-name"><i class="fas fa-circle" style="font-size: 8px; color: <?= $color ?>;"></i> <?= htmlspecialchars($m['payment_method']) ?></div>
                <div class="method-val" style="color:<?= $color ?>">₱<?= number_format($m['total'] ?? 0) ?></div>
                <div class="method-count"><?= $m['cnt'] ?? 0 ?> sale<?= ($m['cnt'] ?? 0)>1?'s':'' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top 5 Sales Table -->
    <div class="section-label"><i class="fas fa-trophy"></i> Top Sales</div>
    <div class="table-card">
        <div class="table-head">
            <div class="table-title"><i class="fas fa-crown"></i> Highest Value Sales</div>
            <div class="table-count">Top 5 by final price</div>
        </div>
        <div class="tbl-overflow">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center;">Rank</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Final Price</th>
                        <th>Down Payment</th>
                        <th>Collected</th>
                        <th>Payment Status</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topSales as $i => $s):
                    $mBadge = match($s['payment_method']) {
                        'Cash' => 'badge-cash', 'Bank Loan' => 'badge-bank', 'In-House Financing' => 'badge-inhouse', default => 'badge-cash'
                    };
                    $hasPartial = ($s['partial_count'] ?? 0) > 0;
                    $hasPending = ($s['pending_count'] ?? 0) > 0;
                ?>
                    <tr>
                        <td class="rank-cell">#<?= $i+1 ?></td>
                        <td>
                            <div class="td-main"><?= htmlspecialchars($s['customer_name']) ?></div>
                            <div class="td-muted"><?= htmlspecialchars($s['customer_phone']) ?></div>
                        </td>
                        <td class="td-main"><?= htmlspecialchars($s['car_name'] ?? 'N/A') ?></td>
                        <td><span class="amount amt-red">₱<?= number_format($s['final_price']) ?></span></td>
                        <td>₱<?= number_format($s['down_payment']) ?></td>
                        <td><span class="amount amt-green">₱<?= number_format($s['collected']) ?></span></td>
                        <!-- ✅ NEW: Payment status indicator -->
                        <td>
                            <?php if ($hasPartial): ?>
                                <span class="badge badge-partial"><i class="fas fa-circle-half-stroke"></i> Partial</span>
                            <?php elseif ($hasPending): ?>
                                <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                            <?php else: ?>
                                <span class="badge badge-paid"><i class="fas fa-check-circle"></i> Paid</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $mBadge ?>"><?= $s['payment_method'] ?></span></td>
                        <td class="td-muted"><?= date('M d, Y', strtotime($s['sale_date'])) ?></td>
                        <td><span class="badge badge-<?= $s['payment_status'] ?>"><?= ucfirst($s['payment_status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Sales Table -->
    <div class="section-label"><i class="fas fa-database"></i> Complete Sales Record</div>
    <div class="table-card">
        <div class="table-head">
            <div class="table-title"><i class="fas fa-list"></i> All Sales</div>
            <div class="table-count"><?= count($allSales) ?> total records</div>
        </div>
        <div class="tbl-overflow">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Customer</th><th>Vehicle</th><th>Final Price</th>
                        <th>Down Payment</th><th>Monthly</th><th>Terms</th><th>Balance</th>
                        <th>Progress</th><th>Method</th><th>Payment Status</th><th>Date</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allSales as $s):
                    $collected = $s['collected'] ?? 0;
                    $balance = max(0, $s['final_price'] - $s['down_payment'] - $collected);
                    $pct = $s['final_price'] > 0 ? min(100, round(($s['down_payment'] + $collected) / $s['final_price'] * 100)) : 0;
                    
                    // ✅ FIXED: Progress class for partial payments
                    if ($pct >= 100) { $progClass = 'success'; $progColor = 'var(--success)'; }
                    elseif ($pct > 0) { $progClass = 'partial'; $progColor = 'linear-gradient(90deg, var(--warning), var(--partial))'; }
                    else { $progClass = 'danger'; $progColor = 'var(--danger)'; }
                    
                    $mBadge = match($s['payment_method']) {
                        'Cash' => 'badge-cash', 'Bank Loan' => 'badge-bank', 'In-House Financing' => 'badge-inhouse', default => 'badge-cash'
                    };
                    
                    $hasPartial = ($s['partial_count'] ?? 0) > 0;
                    $hasPending = ($s['pending_count'] ?? 0) > 0;
                    $hasOverdue = ($s['overdue_count'] ?? 0) > 0;
                ?>
                    <tr>
                        <td class="td-muted">#<?= $s['sale_id'] ?></td>
                        <td>
                            <div class="td-main"><?= htmlspecialchars($s['customer_name']) ?></div>
                            <div class="td-muted"><?= htmlspecialchars($s['customer_email']) ?></div>
                        </td>
                        <td class="td-main"><?= htmlspecialchars($s['car_name'] ?? 'N/A') ?></td>
                        <td><span class="amount amt-red">₱<?= number_format($s['final_price']) ?></span></td>
                        <td>₱<?= number_format($s['down_payment']) ?></td>
                        <td><?= $s['monthly_payment'] > 0 ? '₱'.number_format($s['monthly_payment']) : '—' ?></td>
                        <td><?= $s['terms_months'] !== 'N/A' ? $s['terms_months'].' mos' : 'Cash' ?></td>
                        <td>
                            <?php if ($balance <= 0): ?>
                                <span style="color:var(--success); font-size:12px;"><i class="fas fa-check-circle"></i> Fully paid</span>
                            <?php elseif ($hasPartial): ?>
                                <span class="badge badge-partial"><i class="fas fa-circle-half-stroke"></i> Partial</span>
                            <?php else: ?>
                                <span class="amt-red">₱<?= number_format($balance) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <div class="prog-wrap">
                                    <div class="prog-fill <?= $progClass ?>" style="width: <?= $pct ?>%; background: <?= $progColor ?>; <?= $progClass==='partial'?'animation: shimmer 2s infinite linear;':'' ?>"></div>
                                </div>
                                <span style="font-size: 11px; color: var(--text-muted);"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge <?= $mBadge ?>"><?= $s['payment_method'] ?></span></td>
                        <!-- ✅ NEW: Payment status summary -->
                        <td>
                            <?php if ($hasPartial || $hasPending || $hasOverdue): ?>
                                <div class="payment-status">
                                    <?php if ($hasPartial): ?><span class="dot partial" title="Partial payments"></span><?php endif; ?>
                                    <?php if ($hasPending): ?><span class="dot pending" title="Pending payments"></span><?php endif; ?>
                                    <?php if ($hasOverdue): ?><span class="dot overdue" title="Overdue payments"></span><?php endif; ?>
                                    <span style="font-size:10px;color:var(--text-muted);">
                                        <?= $hasPartial?'Partial':'' ?><?= $hasPending&&$hasPartial?' + ':'' ?><?= $hasPending?'Pending':'' ?><?= $hasOverdue?' + Overdue':'' ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="font-size:10px;color:var(--success);"><i class="fas fa-check"></i> Paid</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-muted"><?= date('M d, Y', strtotime($s['sale_date'])) ?></td>
                        <td><span class="badge badge-<?= $s['payment_status'] ?>"><?= ucfirst($s['payment_status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section-label"><i class="fas fa-history"></i> Recent Transactions</div>
    <div class="table-card">
        <div class="table-head">
            <div class="table-title"><i class="fas fa-clock"></i> Latest 10 Payment Transactions</div>
            <div class="table-count">Most recent activity</div>
        </div>
        <div class="tbl-overflow">
            <table>
                <thead>
                    <tr>
                        <th>Tx #</th><th>Customer</th><th>Vehicle</th><th>Amount</th>
                        <th>Method</th><th>Reference</th><th>Date</th><th>Status</th><th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentTx as $tx): 
                    $statusClass = getTxStatusBadgeClass($tx['status']);
                    $statusIcon = getTxStatusIcon($tx['status']);
                ?>
                    <tr>
                        <td class="td-muted">#<?= $tx['transaction_id'] ?></td>
                        <td class="td-main"><?= htmlspecialchars($tx['customer_name']) ?></td>
                        <td class="td-muted"><?= htmlspecialchars($tx['car_name'] ?? 'N/A') ?></td>
                        <td><span class="amount amt-green">₱<?= number_format($tx['payment_amount']) ?></span></td>
                        <td><span class="badge badge-cash"><?= htmlspecialchars($tx['payment_method']) ?></span></td>
                        <td class="td-muted"><?= $tx['reference_number'] ? htmlspecialchars($tx['reference_number']) : '—' ?></td>
                        <td class="td-muted"><?= date('M d, Y', strtotime($tx['payment_date'])) ?></td>
                        <!-- ✅ FIXED: Status badge with icon -->
                        <td><span class="badge <?= $statusClass ?>"><i class="fas <?= $statusIcon ?>"></i> <?= ucfirst($tx['status']) ?></span></td>
                        <td class="td-muted" style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= $tx['notes'] ? htmlspecialchars($tx['notes']) : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
// Chart.js Global Defaults
Chart.defaults.color = '#9ca3af';
Chart.defaults.borderColor = '#2a2a2a';
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.font.size = 11;

// Format helpers
const fmt = v => '₱' + Number(v || 0).toLocaleString('en-PH', {maximumFractionDigits: 0});
const fmtK = v => {
    const val = Number(v || 0);
    return '₱' + (val >= 1000000 ? (val/1000000).toFixed(1)+'M' : (val/1000).toFixed(0)+'K');
};

// ── 1. Monthly Sales Value Chart (Line) ─────────────────────────────────────
const monthlySalesCtx = document.getElementById('monthlySalesChart');
if (monthlySalesCtx) {
    new Chart(monthlySalesCtx, {
        type: 'line',
        data: {  // ✅ FIXED: Added "data:" key
            labels: <?= $monthLabels ?? '[]' ?>,
            datasets: [{
                label: 'Sales value',
                data: <?= $monthTotals ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                backgroundColor: 'rgba(220, 38, 38, 0.1)',
                borderColor: '#dc2626',
                borderWidth: 3,
                pointBackgroundColor: '#dc2626',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => fmt(ctx.raw) },
                    backgroundColor: '#111111',
                    titleColor: '#ffffff',
                    bodyColor: '#9ca3af',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', maxRotation: 45, minRotation: 45 }
                },
                y: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', callback: value => fmtK(value) },
                    beginAtZero: true
                }
            }
        }
    });
}

// ── 2. Sales by Payment Method (Donut) ──────────────────────────────────────
const methodDonutCtx = document.getElementById('methodDonut');
if (methodDonutCtx) {
    new Chart(methodDonutCtx, {
        type: 'doughnut',
        data: {  // ✅ FIXED: Added "data:" key
            labels: <?= $methodLabels ?? '[]' ?>,
            datasets: [{
                data: <?= $methodTotals ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                backgroundColor: ['#dc2626', '#3b82f6', '#f59e0b'],
                borderColor: '#111111',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ctx.label + ': ' + fmt(ctx.raw) },
                    backgroundColor: '#111111',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }
            }
        }
    });
}

// ── 3. Payments Collected by Month (Stacked Bar) ─────────────────────────────
const txMonthCtx = document.getElementById('txMonthChart');
if (txMonthCtx) {
    new Chart(txMonthCtx, {
        type: 'bar',
        data: {  // ✅ FIXED: Added "data:" key
            labels: <?= $txMonthLabels ?? '[]' ?>,
            datasets: [
                {
                    label: 'Collected',
                    data: <?= $txCollected ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6,
                },
                {
                    label: 'Pending',
                    data: <?= $txPending ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: '#f59e0b',
                    borderWidth: 1,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ctx.dataset.label + ': ' + fmt(ctx.raw) },
                    backgroundColor: '#111111',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', maxRotation: 45, minRotation: 45 }
                },
                y: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', callback: value => fmtK(value) },
                    beginAtZero: true,
                    stacked: true
                },
                // Enable stacked bars
                x: { stacked: true }
            }
        }
    });
}

// ── 4. Sales by Category (Horizontal Bar) ────────────────────────────────────
const categoryCtx = document.getElementById('categoryChart');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'bar',
        data: {  // ✅ FIXED: Added "data:" key
            labels: <?= $catLabels ?? '[]' ?>,
            datasets: [{
                label: 'Sales value',
                data: <?= $catTotals ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                backgroundColor: 'rgba(220, 38, 38, 0.7)',
                borderColor: '#dc2626',
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => fmt(ctx.raw) },
                    backgroundColor: '#111111',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', callback: value => fmtK(value) },
                    beginAtZero: true
                },
                y: {
                    grid: { color: '#1a1a1a' },
                    ticks: { color: '#9ca3af', font: { size: 11 } }
                }
            }
        }
    });
}

// ── 5. Collection Method Mix (Polar Area) ────────────────────────────────────
const txMethodCtx = document.getElementById('txMethodChart');
if (txMethodCtx) {
    new Chart(txMethodCtx, {
        type: 'polarArea',
        data: {  // ✅ FIXED: Added "data:" key
            labels: <?= $txMLabels ?? '[]' ?>,
            datasets: [{
                data: <?= $txMTotals ?? '[]' ?>,  // ✅ FIXED: Added "data:" key
                backgroundColor: [
                    'rgba(220, 38, 38, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                ],
                borderColor: '#111111',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#9ca3af',
                        font: { size: 10 },
                        boxWidth: 10,
                        padding: 8
                    }
                },
                tooltip: {
                    callbacks: { label: ctx => ctx.label + ': ' + fmt(ctx.raw) },
                    backgroundColor: '#111111',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }
            },
            scales: {
                r: {
                    grid: { color: '#1a1a1a' },
                    ticks: { display: false, stepSize: 1000000 },
                    beginAtZero: true
                }
            }
        }
    });
}
</script>