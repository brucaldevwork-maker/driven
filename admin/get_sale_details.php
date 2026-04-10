<?php
require_once '../include/config.php';
requireAdminLogin();

if (isset($_GET['id'])) {
    $sale_id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT monthly_payment, final_price, down_payment FROM sales WHERE sale_id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if ($sale) {
        // Calculate remaining balance
        $stmt = $pdo->prepare("SELECT SUM(payment_amount) as total_paid FROM payment_transactions WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $payments = $stmt->fetch();
        
        $totalPaid = $sale['down_payment'] + ($payments['total_paid'] ?? 0);
        $remainingBalance = $sale['final_price'] - $totalPaid;
        
        echo json_encode([
            'monthly_payment' => $sale['monthly_payment'],
            'remaining_balance' => $remainingBalance,
            'suggested_payment' => min($sale['monthly_payment'], $remainingBalance)
        ]);
    } else {
        echo json_encode(['monthly_payment' => 0]);
    }
}
?>