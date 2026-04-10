<?php
// /admin/add_modal_sale.php
// Standalone New Sale Modal - Complete with HTML, CSS, and JS

// Include config to access database and cars data
require_once '../include/config.php';
requireAdminLogin();

// Fetch cars for dropdown
$cars = $pdo->query("SELECT car_id, car_name FROM cars ORDER BY car_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Sale - Driven Auto Sales</title>
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
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Modal Container */
        .modal-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Modal Styles */
        .modal {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            border-radius: 20px;
            border: 1px solid #2a2a2a;
            overflow: hidden;
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
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #2a2a2a;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .modal-header h3 {
            font-size: 24px;
            color: #FFFFFF;
            border-left: 4px solid #E50914;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h3 i {
            color: #E50914;
        }
        
        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #2a2a2a;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: rgba(0, 0, 0, 0.2);
        }
        
        /* Form Styles */
        .section-divider {
            font-size: 13px;
            font-weight: 600;
            color: #E50914;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 25px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-divider:first-of-type {
            margin-top: 0;
        }
        
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #2a2a2a;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            font-size: 12px;
            font-weight: 600;
            color: #E50914;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, select, textarea {
            background: #0D0D0D;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            padding: 12px 14px;
            color: #FFFFFF;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #E50914;
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Calculator Box */
        .calc-box {
            background: #0D0D0D;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            border: 1px solid #2a2a2a;
        }
        
        .calc-item {
            text-align: center;
        }
        
        .calc-item label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
        }
        
        .calc-item .val {
            font-size: 20px;
            font-weight: 700;
            margin-top: 8px;
        }
        
        .calc-item .val.gold {
            color: #E50914;
        }
        
        .calc-item .val.blue {
            color: #3498db;
        }
        
        .calc-item .val.green {
            color: #2ecc71;
        }
        
        /* Button Styles */
        .btn {
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 14px;
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
            background: #ff0a1a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
        }
        
        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            color: #CCCCCC;
            border: 1px solid #2a2a2a;
        }
        
        .btn-ghost:hover {
            border-color: #E50914;
            color: #E50914;
            transform: translateY(-2px);
        }
        
        /* Scrollbar */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: #0D0D0D;
            border-radius: 10px;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: #E50914;
            border-radius: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-body {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .calc-box {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .modal-header h3 {
                font-size: 20px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
        
        /* Toast Notification */
        #toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1A1A1A;
            border-left: 4px solid #2ecc71;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            z-index: 2000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        #toast.show {
            transform: translateX(0);
        }
        
        #toast.toast-err {
            border-left-color: #e74c3c;
        }
        
        /* Loading Spinner */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="modal-container">
        <div class="modal">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Record New Sale
                </h3>
            </div>
            <div class="modal-body">
                <form id="addSaleForm">
                    <div class="section-divider">
                        <i class="fas fa-user"></i> Customer Information
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" id="customerName" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="text" name="customer_phone" id="customerPhone" required placeholder="09XXXXXXXXX">
                        </div>
                        <div class="form-group full-width">
                            <label>Email *</label>
                            <input type="email" name="customer_email" id="customerEmail" required placeholder="email@example.com">
                        </div>
                    </div>
                    
                    <div class="section-divider">
                        <i class="fas fa-car"></i> Vehicle & Sale Details
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Car *</label>
                            <select name="car_id" id="carId" required>
                                <option value="">— Select Car —</option>
                                <?php foreach ($cars as $c): ?>
                                    <option value="<?php echo $c['car_id']; ?>"><?php echo htmlspecialchars($c['car_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sale Date *</label>
                            <input type="date" name="sale_date" id="saleDate" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" id="paymentMethod" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Loan">Bank Loan</option>
                                <option value="In-House Financing">In-House Financing</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Terms (months)</label>
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
                    
                    <div class="section-divider">
                        <i class="fas fa-money-bill-wave"></i> Payment Details
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Final Price (₱) *</label>
                            <input type="number" name="final_price" id="finalPrice" required min="0" step="0.01" placeholder="0.00" oninput="calculateBalance()">
                        </div>
                        <div class="form-group">
                            <label>Down Payment (₱) *</label>
                            <input type="number" name="down_payment" id="downPayment" required min="0" step="0.01" placeholder="0.00" oninput="calculateBalance()">
                        </div>
                        <div class="form-group full-width">
                            <label>Reference Number</label>
                            <input type="text" name="reference_number" id="referenceNumber" placeholder="Optional reference number">
                        </div>
                    </div>
                    
                    <div class="calc-box" id="calcBox">
                        <div class="calc-item">
                            <label>Balance</label>
                            <div class="val blue" id="calcBalance">₱0.00</div>
                        </div>
                        <div class="calc-item">
                            <label>Monthly Payment</label>
                            <div class="val gold" id="calcMonthly">₱0.00</div>
                        </div>
                        <div class="calc-item">
                            <label>Total Installments</label>
                            <div class="val" id="calcTerms">—</div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width" style="margin-top: 20px;">
                        <label>Notes</label>
                        <textarea name="notes" id="notes" placeholder="Optional notes about the sale..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="window.close()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-primary" onclick="submitSale()" id="submitBtn">
                    <i class="fas fa-save"></i> Save Sale
                </button>
            </div>
        </div>
    </div>
    
    <div id="toast">
        <i class="fas fa-check-circle"></i>
        <span>Message</span>
    </div>
    
    <script>
        // Calculate balance and monthly payment
        function calculateBalance() {
            const price = parseFloat(document.getElementById('finalPrice').value) || 0;
            const dp = parseFloat(document.getElementById('downPayment').value) || 0;
            const termsEl = document.getElementById('termsMonths');
            const terms = termsEl ? parseInt(termsEl.value) || 0 : 0;
            const balance = Math.max(0, price - dp);
            const monthly = (terms > 0 && balance > 0) ? (balance / terms) : 0;
            
            document.getElementById('calcBalance').textContent = '₱' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('calcMonthly').innerHTML = monthly > 0 ? '₱' + monthly.toLocaleString('en-US', {minimumFractionDigits: 2}) : '—';
            document.getElementById('calcTerms').innerHTML = terms > 0 ? terms + ' months' : '—';
        }
        
        // Recalculate when terms change
        document.getElementById('termsMonths').addEventListener('change', calculateBalance);
        
        // Submit sale form
        async function submitSale() {
            const form = document.getElementById('addSaleForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'add_sale');
            formData.append('car_id', document.getElementById('carId').value);
            formData.append('customer_name', document.getElementById('customerName').value);
            formData.append('customer_email', document.getElementById('customerEmail').value);
            formData.append('customer_phone', document.getElementById('customerPhone').value);
            formData.append('final_price', document.getElementById('finalPrice').value);
            formData.append('down_payment', document.getElementById('downPayment').value);
            formData.append('terms_months', document.getElementById('termsMonths').value);
            formData.append('sale_date', document.getElementById('saleDate').value);
            formData.append('payment_method', document.getElementById('paymentMethod').value);
            formData.append('reference_number', document.getElementById('referenceNumber').value);
            formData.append('notes', document.getElementById('notes').value);
            
            try {
                const response = await fetch('sales_management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Sale recorded successfully!', 'success');
                    setTimeout(() => {
                        // Close window and refresh parent
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.reload();
                        }
                        window.close();
                    }, 1500);
                } else {
                    showToast(result.error || 'Error saving sale', 'error');
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            }
        }
        
        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.className = type === 'error' ? 'toast toast-err' : 'toast';
            const icon = toast.querySelector('i');
            icon.className = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            toast.querySelector('span').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
        
        // Auto-calculate on page load
        calculateBalance();
    </script>
</body>
</html>