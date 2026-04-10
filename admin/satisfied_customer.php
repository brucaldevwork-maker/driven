<?php
require_once '../include/config.php';
requireAdminLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$success = '';
$error = '';

try {
    $cars = $pdo->query("SELECT car_id, car_name FROM cars ORDER BY car_name")->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $customer_name = trim($_POST['customer_name']);
            $car_id        = !empty($_POST['car_id']) ? $_POST['car_id'] : null;
            $description   = trim($_POST['description']);
            $rating        = (int)$_POST['rating'];

            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/customers/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename   = uniqid() . '.' . $file_extension;
                $image_path = 'uploads/customers/' . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path);
            }

            $stmt = $pdo->prepare("INSERT INTO satisfied_customers (customer_name, car_id, image, description, rating) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$customer_name, $car_id, $image_path, $description, $rating]);
            $success = "Customer testimonial added successfully!";

        } elseif ($_POST['action'] === 'edit') {
            $customer_id   = $_POST['customer_id'];
            $customer_name = trim($_POST['customer_name']);
            $car_id        = !empty($_POST['car_id']) ? $_POST['car_id'] : null;
            $description   = trim($_POST['description']);
            $rating        = (int)$_POST['rating'];

            $stmt = $pdo->prepare("SELECT image FROM satisfied_customers WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $current_image = $stmt->fetch()['image'];
            $image_path    = $current_image;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/customers/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                if ($current_image && file_exists('../' . $current_image)) unlink('../' . $current_image);
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename   = uniqid() . '.' . $file_extension;
                $image_path = 'uploads/customers/' . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path);
            }

            $stmt = $pdo->prepare("UPDATE satisfied_customers SET customer_name=?, car_id=?, image=?, description=?, rating=? WHERE customer_id=?");
            $stmt->execute([$customer_name, $car_id, $image_path, $description, $rating, $customer_id]);
            $success = "Customer testimonial updated successfully!";
        }
    }

    if (isset($_GET['delete'])) {
        $customer_id = $_GET['delete'];
        $stmt = $pdo->prepare("SELECT image FROM satisfied_customers WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $image = $stmt->fetch()['image'];
        if ($image && file_exists('../' . $image)) unlink('../' . $image);
        $stmt = $pdo->prepare("DELETE FROM satisfied_customers WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $success = "Customer testimonial deleted successfully!";
    }

    $customers = $pdo->query("
        SELECT sc.*, c.car_name
        FROM satisfied_customers sc
        LEFT JOIN cars c ON sc.car_id = c.car_id
        ORDER BY sc.customer_id DESC
    ")->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$edit_customer = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM satisfied_customers WHERE customer_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_customer = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Satisfied Customers - Driven Auto Sales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0D0D0D;
            color: #FFFFFF;
        }

        .customers-container { padding: 20px 30px; max-width: 1400px; margin: 0 auto; }

        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #FFFFFF; border-left: 4px solid #E50914; padding-left: 15px; margin-bottom: 10px; }
        .page-header p  { color: #CCCCCC; margin-left: 19px; font-size: 14px; }

        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; animation: slideDown 0.5s ease; display: flex; align-items: center; gap: 12px; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
        .alert-success { background: rgba(46,204,113,0.15); color: #2ecc71; border-left: 4px solid #2ecc71; }
        .alert-danger  { background: rgba(229,9,20,0.15);  color: #ff6b6b; border-left: 4px solid #E50914; }
        .alert i { font-size: 20px; }

        .quick-actions { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .action-btn {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            border: 1px solid #2a2a2a; color: #FFFFFF;
            padding: 12px 24px; border-radius: 12px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; text-decoration: none;
        }
        .action-btn:hover { border-color: #E50914; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(229,9,20,0.2); }
        .action-btn i { color: #E50914; font-size: 16px; }

        /* Improved Modal Styles - Mobile Friendly */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            margin: 20px auto;
            padding: 0;
            border-radius: 24px;
            width: 90%;
            max-width: 550px;
            border: 1px solid rgba(229, 9, 20, 0.3);
            animation: slideUp 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(229, 9, 20, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(229,9,20,0.1) 0%, rgba(0,0,0,0) 100%);
            border-radius: 24px 24px 0 0;
        }

        .modal-header h2 {
            color: #FFFFFF;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: #E50914;
            font-size: 1.4rem;
        }

        .close {
            color: #888;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            color: #E50914;
            background: rgba(229, 9, 20, 0.1);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            max-height: calc(90vh - 140px);
            overflow-y: auto;
        }

        /* Custom scrollbar for modal body */
        .modal-body::-webkit-scrollbar {
            width: 4px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #1a1a1a;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #E50914;
            border-radius: 10px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group > label {
            display: block;
            margin-bottom: 8px;
            color: #E50914;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group > label i {
            margin-right: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: #0D0D0D;
            border: 2px solid #2a2a2a;
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #E50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* File Upload Styling */
        .form-group input[type="file"] {
            padding: 10px;
            cursor: pointer;
        }

        .form-group input[type="file"]::-webkit-file-upload-button {
            background: #E50914;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            margin-right: 12px;
            transition: all 0.3s ease;
        }

        .form-group input[type="file"]::-webkit-file-upload-button:hover {
            background: #ff0a1a;
            transform: translateY(-1px);
        }

        /* Star Rating Widget */
        .star-rating-widget {
            display: flex;
            flex-direction: row;
            gap: 8px;
            align-items: center;
            padding: 10px 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .star-rating-widget .star {
            font-size: 40px;
            color: #444;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
            user-select: none;
        }

        .star-rating-widget .star.lit {
            color: #ffc107;
            text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        .star-rating-widget .star:hover {
            transform: scale(1.2);
            color: #ffc107;
        }

        /* Current Image Display */
        .current-image {
            margin-top: 12px;
            padding: 12px;
            background: #0D0D0D;
            border-radius: 12px;
            text-align: center;
            border: 1px dashed #2a2a2a;
        }

        .current-image label {
            display: block;
            margin-bottom: 8px;
            color: #E50914;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .current-image img {
            max-width: 80px;
            border-radius: 50%;
            border: 2px solid #E50914;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(42, 42, 42, 0.5);
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #E50914 0%, #ff0a1a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .btn-secondary {
            background: rgba(42, 42, 42, 0.8);
            color: #FFFFFF;
            border: 1px solid #2a2a2a;
        }

        .btn-secondary:hover {
            background: rgba(229, 9, 20, 0.2);
            border-color: #E50914;
            transform: translateY(-2px);
        }

        /* Cards */
        .customers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-top: 30px; }
        .customer-card { background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%); border-radius: 20px; padding: 25px; transition: all 0.3s ease; border: 1px solid #2a2a2a; position: relative; }
        .customer-card:hover { transform: translateY(-5px); border-color: #E50914; box-shadow: 0 10px 30px rgba(229,9,20,0.2); }
        .customer-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .customer-avatar { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #E50914; }
        .customer-info { flex: 1; }
        .customer-name { font-size: 18px; font-weight: 700; color: #FFFFFF; margin-bottom: 5px; }
        .customer-car  { font-size: 13px; color: #E50914; display: flex; align-items: center; gap: 5px; }
        .rating { display: flex; gap: 3px; margin-top: 5px; }
        .rating .fas.fa-star { font-size: 14px; color: #ffc107; }
        .rating .far.fa-star { font-size: 14px; color: #555; }
        .customer-description { color: #CCCCCC; font-size: 14px; line-height: 1.6; margin: 15px 0; font-style: italic; }
        .customer-description:before { content: '"'; font-size: 20px; color: #E50914; margin-right: 5px; }
        .customer-description:after  { content: '"'; font-size: 20px; color: #E50914; margin-left: 5px; vertical-align: bottom; }
        .card-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 15px; padding-top: 15px; border-top: 1px solid #2a2a2a; }
        .card-action-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .card-action-btn.edit   { background: rgba(52,152,219,0.15); color: #3498db; border: 1px solid rgba(52,152,219,0.3); }
        .card-action-btn.edit:hover   { background: rgba(52,152,219,0.3); transform: translateY(-2px); }
        .card-action-btn.delete { background: rgba(231,76,60,0.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,0.3);  }
        .card-action-btn.delete:hover { background: rgba(231,76,60,0.3); transform: translateY(-2px); }

        .empty-state { text-align: center; padding: 80px 20px; background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%); border-radius: 20px; border: 1px solid #2a2a2a; }
        .empty-state i  { font-size: 64px; color: #333; margin-bottom: 20px; }
        .empty-state h3 { color: #FFFFFF; margin-bottom: 10px; }
        .empty-state p  { color: #888; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .customers-container { padding: 15px; }
            .customers-grid { grid-template-columns: 1fr; gap: 15px; }
            
            .modal-content {
                width: 95%;
                margin: 10px auto;
                border-radius: 20px;
            }
            
            .modal-header {
                padding: 16px 20px;
            }
            
            .modal-header h2 {
                font-size: 1.1rem;
            }
            
            .modal-body {
                padding: 20px;
                max-height: calc(100vh - 100px);
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .form-group > label {
                font-size: 0.8rem;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 10px 14px;
                font-size: 0.85rem;
            }
            
            .star-rating-widget .star {
                font-size: 32px;
                gap: 6px;
            }
            
            .form-actions {
                flex-direction: column-reverse;
                gap: 10px;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
                width: 100%;
            }
            
            .customer-card {
                padding: 20px;
            }
            
            .customer-header {
                flex-wrap: wrap;
                text-align: center;
                justify-content: center;
            }
            
            .customer-avatar {
                width: 60px;
                height: 60px;
            }
            
            .customer-info {
                text-align: center;
            }
            
            .customer-car {
                justify-content: center;
            }
            
            .rating {
                justify-content: center;
            }
            
            .card-actions {
                justify-content: center;
            }
        }

        /* Small phones */
        @media (max-width: 480px) {
            .star-rating-widget .star {
                font-size: 28px;
            }
            
            .modal-body {
                padding: 16px;
            }
            
            .customers-container {
                padding: 10px;
            }
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .star-rating-widget .star {
                padding: 8px;
            }
            
            .btn-primary, .btn-secondary,
            .action-btn,
            .card-action-btn {
                -webkit-tap-highlight-color: transparent;
            }
            
            .close {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>

    <div class="customers-container">
        <div class="page-header">
            <h1><i class="fas fa-smile"></i> Satisfied Customers</h1>
            <p>Manage customer testimonials and feedback from happy car owners.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="quick-actions">
            <button class="action-btn" onclick="openAddModal()"><i class="fas fa-plus-circle"></i> Add Testimonial</button>
            <a href="admin_dashboard.php" class="action-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="customers-grid">
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $customer): ?>
                    <div class="customer-card">
                        <div class="customer-header">
                            <?php if ($customer['image'] && file_exists('../' . $customer['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($customer['image']); ?>" alt="<?php echo htmlspecialchars($customer['customer_name']); ?>" class="customer-avatar">
                            <?php else: ?>
                                <div class="customer-avatar" style="background:#E50914;display:flex;align-items:center;justify-content:center;font-size:32px;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <div class="customer-info">
                                <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                <div class="customer-car"><i class="fas fa-car"></i><?php echo htmlspecialchars($customer['car_name'] ?? 'No car specified'); ?></div>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $customer['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="customer-description"><?php echo htmlspecialchars($customer['description']); ?></div>
                        <div class="card-actions">
                            <a href="?edit=<?php echo $customer['customer_id']; ?>" class="card-action-btn edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?delete=<?php echo $customer['customer_id']; ?>" class="card-action-btn delete" onclick="return confirm('Are you sure you want to delete this testimonial?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fas fa-smile-wink"></i>
                    <h3>No Testimonials Yet</h3>
                    <p>Start collecting feedback from your satisfied customers.</p>
                    <button class="action-btn" onclick="openAddModal()" style="margin-top:20px;"><i class="fas fa-plus-circle"></i> Add First Testimonial</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Improved Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-smile"></i><span id="modalTitle">Add Testimonial</span></h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="customerForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action"      id="formAction"  value="add">
                    <input type="hidden" name="customer_id" id="customerId">
                    <input type="hidden" name="rating"      id="ratingValue" value="">

                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Customer Name *</label>
                        <input type="text" name="customer_name" id="customerName" required placeholder="Enter customer full name">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Car Purchased (Optional)</label>
                        <select name="car_id" id="carId">
                            <option value="">Select a car (optional)</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?php echo $car['car_id']; ?>"><?php echo htmlspecialchars($car['car_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Customer Photo</label>
                        <input type="file" name="image" id="customerImage" accept="image/*">
                        <div id="currentImageContainer" style="display:none;" class="current-image">
                            <label>Current Image:</label>
                            <div><img id="currentImage" src="" alt="Current customer image"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Rating *</label>
                        <div class="star-rating-widget" id="starWidget">
                            <i class="fas fa-star star" data-value="1"></i>
                            <i class="fas fa-star star" data-value="2"></i>
                            <i class="fas fa-star star" data-value="3"></i>
                            <i class="fas fa-star star" data-value="4"></i>
                            <i class="fas fa-star star" data-value="5"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Testimonial *</label>
                        <textarea name="description" id="description" required placeholder="Write customer's testimonial or feedback..." rows="4"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn-primary" onclick="return validateRating()">
                            <i class="fas fa-save"></i> Save Testimonial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Star Rating functionality
    const stars = document.querySelectorAll('#starWidget .star');
    const ratingInput = document.getElementById('ratingValue');
    let currentRating = 0;

    function paintStars(upTo) {
        stars.forEach(s => {
            s.classList.toggle('lit', parseInt(s.dataset.value) <= upTo);
        });
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => paintStars(parseInt(star.dataset.value)));
        star.addEventListener('mouseleave', () => paintStars(currentRating));
        star.addEventListener('click', () => {
            currentRating = parseInt(star.dataset.value);
            ratingInput.value = currentRating;
            paintStars(currentRating);
        });
        
        // Touch support
        star.addEventListener('touchstart', (e) => {
            e.preventDefault();
            currentRating = parseInt(star.dataset.value);
            ratingInput.value = currentRating;
            paintStars(currentRating);
        });
    });

    function setRating(val) {
        currentRating = val;
        ratingInput.value = val;
        paintStars(val);
    }

    function validateRating() {
        if (!ratingInput.value || ratingInput.value === '0') {
            alert('Please select a star rating before saving.');
            return false;
        }
        return true;
    }

    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Testimonial';
        document.getElementById('formAction').value = 'add';
        document.getElementById('customerId').value = '';
        document.getElementById('customerName').value = '';
        document.getElementById('carId').value = '';
        document.getElementById('description').value = '';
        document.getElementById('currentImageContainer').style.display = 'none';
        document.getElementById('customerImage').value = '';
        setRating(0);
        document.getElementById('customerModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    <?php if ($edit_customer): ?>
    function openEditModal() {
        document.getElementById('modalTitle').innerText = 'Edit Testimonial';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('customerId').value = '<?php echo (int)$edit_customer['customer_id']; ?>';
        document.getElementById('customerName').value = '<?php echo addslashes(htmlspecialchars($edit_customer['customer_name'])); ?>';
        document.getElementById('carId').value = '<?php echo (int)$edit_customer['car_id']; ?>';
        document.getElementById('description').value = '<?php echo addslashes(htmlspecialchars($edit_customer['description'])); ?>';
        
        setRating(<?php echo (int)$edit_customer['rating']; ?>);
        
        <?php if ($edit_customer['image'] && file_exists('../' . $edit_customer['image'])): ?>
            document.getElementById('currentImage').src = '../<?php echo $edit_customer['image']; ?>';
            document.getElementById('currentImageContainer').style.display = 'block';
        <?php else: ?>
            document.getElementById('currentImageContainer').style.display = 'none';
        <?php endif; ?>
        
        document.getElementById('customerModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    <?php endif; ?>

    window.onload = function() {
        <?php if ($edit_customer): ?>openEditModal();<?php endif; ?>
    };

    function closeModal() {
        document.getElementById('customerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('customerModal');
        if (event.target === modal) {
            closeModal();
        }
    };

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('customerModal');
            if (modal.style.display === 'block') {
                closeModal();
            }
        }
    });
    </script>
</body>
</html>