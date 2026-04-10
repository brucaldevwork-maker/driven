<?php
require_once '../include/config.php';
requireAdminLogin();

$error = '';
$success = '';
$editMode = false;
$editId = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        // Get images to delete files
        $stmt = $pdo->prepare("SELECT images FROM cars WHERE car_id = ?");
        $stmt->execute([$deleteId]);
        $car = $stmt->fetch();
        
        if ($car && $car['images']) {
            // Delete image files from server
            $images = json_decode($car['images'], true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    $filePath = "../uploads/" . $image;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM cars WHERE car_id = ?");
        $stmt->execute([$deleteId]);
        $success = "Car deleted successfully!";
        
        // Redirect to clear URL
        header("Location: manage_cars.php?success=deleted");
        exit;
        
    } catch(PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

// Get car for editing
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE car_id = ?");
        $stmt->execute([$editId]);
        $editCar = $stmt->fetch();
        if ($editCar) {
            $editMode = true;
            $editCar['images'] = json_decode($editCar['images'], true);
            if (!is_array($editCar['images'])) {
                $editCar['images'] = [];
            }
            $editCar['included_items'] = json_decode($editCar['included_items'], true);
            if (!is_array($editCar['included_items'])) {
                $editCar['included_items'] = [];
            }
        }
    } catch(PDOException $e) {
        $error = "Error loading car data: " . $e->getMessage();
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
    $category_id = (int)$_POST['category_id'];
    $car_name = sanitize(trim($_POST['car_name']));
    $price = (float)$_POST['price'];
    $down_payment = (float)$_POST['down_payment'];
    $monthly = (float)$_POST['monthly'];
    $terms = sanitize(trim($_POST['terms']));
    $mileage = (int)$_POST['mileage'];
    $included_heading = sanitize(trim($_POST['included_heading']));
    $description = sanitize(trim($_POST['description']));
    $is_low_dp = isset($_POST['is_low_dp']) ? 1 : 0;
    
    // Validation
    if (empty($car_name)) {
        $error = "Car name is required";
    } elseif ($category_id <= 0) {
        $error = "Please select a category";
    } elseif ($price <= 0) {
        $error = "Please enter a valid price";
    } elseif ($down_payment < 0) {
        $error = "Please enter a valid down payment";
    } elseif ($monthly <= 0) {
        $error = "Please enter a valid monthly payment";
    } elseif (empty($terms)) {
        $error = "Please enter terms";
    } elseif ($mileage < 0) {
        $error = "Please enter a valid mileage";
    } else {
        // Handle included items
        $included_items = isset($_POST['included_items']) ? array_filter($_POST['included_items'], function($item) {
            return !empty(trim($item));
        }) : [];
        
        $included_items = array_values($included_items);
        $included_items_json = json_encode($included_items);
        
        // Handle image uploads
        $existing_images = [];
        if ($editMode && isset($_POST['existing_images']) && !empty($_POST['existing_images'])) {
            $existing_images = explode(',', $_POST['existing_images']);
            $existing_images = array_filter($existing_images);
        }
        
        $uploaded_images = [];
        
        // Process new image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = "../uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $file_type = $_FILES['images']['type'][$key];
                    $file_size = $_FILES['images']['size'][$key];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        $error = "Only JPG, PNG, GIF, and WEBP files are allowed";
                        continue;
                    }
                    
                    if ($file_size > $max_size) {
                        $error = "File size must be less than 5MB";
                        continue;
                    }
                    
                    $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $file_name = time() . '_' . uniqid() . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $uploaded_images[] = $file_name;
                    }
                }
            }
        }
        
        // Merge existing and new images
        $all_images = array_merge($existing_images, $uploaded_images);
        $images_json = json_encode($all_images);
        
        try {
            if ($editMode && $car_id > 0) {
                // Get old images to delete if images are being replaced
                $oldCarStmt = $pdo->prepare("SELECT images FROM cars WHERE car_id = ?");
                $oldCarStmt->execute([$car_id]);
                $oldCar = $oldCarStmt->fetch();
                
                // Update existing car
                $sql = "UPDATE cars SET 
                        category_id = ?,
                        images = ?,
                        car_name = ?,
                        price = ?,
                        down_payment = ?,
                        monthly = ?,
                        terms = ?,
                        mileage = ?,
                        included_heading = ?,
                        included_items = ?,
                        description = ?,
                        is_low_dp = ?
                        WHERE car_id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $category_id, $images_json, $car_name, $price, $down_payment,
                    $monthly, $terms, $mileage, $included_heading, $included_items_json,
                    $description, $is_low_dp, $car_id
                ]);
                
                // Delete removed images from server
                if ($oldCar && $oldCar['images']) {
                    $oldImages = json_decode($oldCar['images'], true);
                    if (is_array($oldImages)) {
                        $removedImages = array_diff($oldImages, $all_images);
                        foreach ($removedImages as $removedImage) {
                            $filePath = "../uploads/" . $removedImage;
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }
                
                $success = "Car updated successfully!";
                header("Location: manage_cars.php?success=updated");
                exit;
                
            } else {
                // Insert new car
                $sql = "INSERT INTO cars (category_id, images, car_name, price, down_payment, monthly, terms, mileage, included_heading, included_items, description, is_low_dp) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $category_id, $images_json, $car_name, $price, $down_payment,
                    $monthly, $terms, $mileage, $included_heading, $included_items_json,
                    $description, $is_low_dp
                ]);
                $success = "Car added successfully!";
                header("Location: manage_cars.php?success=added");
                exit;
            }
            
        } catch(PDOException $e) {
            $error = "Save failed: " . $e->getMessage();
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'added') {
        $success = "Car added successfully!";
    } elseif ($_GET['success'] == 'updated') {
        $success = "Car updated successfully!";
    } elseif ($_GET['success'] == 'deleted') {
        $success = "Car deleted successfully!";
    }
}

// Get all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order")->fetchAll();

// Get all cars with category names
$cars = $pdo->query("
    SELECT c.*, cat.category_name 
    FROM cars c 
    LEFT JOIN categories cat ON c.category_id = cat.category_id 
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Driven Auto Sales</title>
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
        .manage-container {
            padding: 20px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #FFFFFF;
            border-left: 4px solid #E50914;
            padding-left: 15px;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #E50914 0%, #FF2A2A 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
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
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1A1A1A 0%, #0F0F0F 100%);
            max-width: 900px;
            margin: 40px auto;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            animation: slideUp 0.4s ease;
            overflow: hidden;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #E50914 0%, #B00810 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-header h2 i {
            font-size: 28px;
        }
        
        .close-modal {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Custom Scrollbar */
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
        
        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #E50914;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: #0D0D0D;
            border: 2px solid #333;
            border-radius: 12px;
            font-size: 14px;
            color: #FFFFFF;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #E50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Checkbox Style */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #0D0D0D;
            border-radius: 12px;
            border: 2px solid #333;
        }
        
        .checkbox-group input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #E50914;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
            color: #CCCCCC;
            text-transform: none;
        }
        
        /* Enhanced LOW DP BADGE */
        .low-dp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(120deg, #ff416c, #ff4b2b);
            color: white;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 40px;
            letter-spacing: 0.5px;
            box-shadow: 0 0 12px rgba(255, 65, 108, 0.6);
            backdrop-filter: blur(2px);
            text-transform: uppercase;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .low-dp-badge i {
            font-size: 11px;
            filter: drop-shadow(0 0 2px rgba(0,0,0,0.3));
        }
        
        .low-dp-badge:hover {
            transform: scale(1.02);
            box-shadow: 0 0 18px rgba(255, 65, 108, 0.8);
        }
        
        /* Image Upload */
        .image-upload-area {
            border: 2px dashed #E50914;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #0D0D0D;
        }
        
        .image-upload-area:hover {
            background: rgba(229, 9, 20, 0.05);
            border-color: #FF2A2A;
        }
        
        .image-upload-area i {
            font-size: 48px;
            color: #E50914;
            margin-bottom: 15px;
        }
        
        .image-upload-area p {
            color: #CCCCCC;
            margin-bottom: 5px;
        }
        
        .image-upload-area small {
            color: #666;
        }
        
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #333;
            transition: all 0.3s;
        }
        
        .preview-item:hover {
            border-color: #E50914;
            transform: scale(1.05);
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #E50914;
            color: white;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .remove-image:hover {
            transform: scale(1.1);
            background: #FF2A2A;
        }
        
        /* Included Items */
        .included-items-container {
            background: #0D0D0D;
            border-radius: 12px;
            padding: 15px;
            border: 2px solid #333;
        }
        
        .included-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            align-items: center;
        }
        
        .included-item input {
            flex: 1;
            background: #1A1A1A;
        }
        
        .remove-item {
            background: #E50914;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-item:hover {
            background: #FF2A2A;
            transform: scale(1.05);
        }
        
        .add-item-btn {
            background: linear-gradient(135deg, #333 0%, #1A1A1A 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .add-item-btn:hover {
            background: linear-gradient(135deg, #E50914 0%, #FF2A2A 100%);
            transform: translateY(-2px);
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #E50914 0%, #FF2A2A 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.4);
        }
        
        /* ===== PERFECT TABLE ALIGNMENT WITH DATABASE STRUCTURE ===== */
        .cars-table {
            background: #1A1A1A;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        /* Define exact column widths matching database fields */
        th:nth-child(1), td:nth-child(1) { width: 85px; }   /* Image column */
        th:nth-child(2), td:nth-child(2) { width: 25%; }    /* Car Name */
        th:nth-child(3), td:nth-child(3) { width: 15%; }    /* Category */
        th:nth-child(4), td:nth-child(4) { width: 15%; }    /* Price */
        th:nth-child(5), td:nth-child(5) { width: 12%; }    /* Low DP (is_low_dp) */
        th:nth-child(6), td:nth-child(6) { width: 18%; }    /* Actions */
        
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
        
        /* Image column - center aligned */
        td:first-child {
            text-align: center;
            vertical-align: middle;
        }
        
        /* Car Name - left aligned with proper spacing */
        td:nth-child(2) {
            text-align: left;
            font-weight: 600;
            color: #ffffff;
        }
        
        /* Category - left aligned */
        td:nth-child(3) {
            text-align: left;
        }
        
        /* Price - right aligned for currency */
        td:nth-child(4) {
            text-align: right;
            font-weight: 700;
            color: #E50914;
            font-size: 15px;
            white-space: nowrap;
        }
        
        /* Low DP - center aligned for badge */
        td:nth-child(5) {
            text-align: center;
            vertical-align: middle;
        }
        
        /* Actions - left aligned */
        td:nth-child(6) {
            text-align: left;
        }
        
        th:nth-child(4) {
            text-align: right;
        }
        
        th:nth-child(5) {
            text-align: center;
        }
        
        tr:hover {
            background: rgba(229, 9, 20, 0.06);
            transition: background 0.2s;
        }
        
        .car-image {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            background: #0D0D0D;
            display: block;
            margin: 0 auto;
            border: 1px solid #333;
            transition: transform 0.2s;
        }
        
        .car-image:hover {
            transform: scale(1.05);
            border-color: #E50914;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .edit-btn, .delete-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .edit-btn {
            background: #2c3e66;
            color: white;
        }
        
        .edit-btn:hover {
            background: #1e2a44;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }
        
        .delete-btn {
            background: #aa2e2e;
            color: white;
        }
        
        .delete-btn:hover {
            background: #8b2323;
            transform: translateY(-2px);
        }
        
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
        
        /* Category tag styling */
        .category-tag {
            background: rgba(229, 9, 20, 0.15);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            color: #ff8a8a;
            border: 1px solid rgba(229,9,20,0.3);
        }
        
        .price-cell {
            font-weight: 700;
            color: #E50914;
            font-size: 15px;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        
        /* Loading Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .manage-container {
                padding: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            th, td {
                padding: 12px 10px;
                font-size: 12px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }
            
            .edit-btn, .delete-btn {
                justify-content: center;
            }
            
            .modal-content {
                margin: 20px;
                width: auto;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            /* Responsive table adjustments */
            th:nth-child(1), td:nth-child(1) { width: 70px; }
            th:nth-child(2), td:nth-child(2) { width: 30%; }
            th:nth-child(3), td:nth-child(3) { width: 20%; }
            th:nth-child(4), td:nth-child(4) { width: 20%; }
        }
    </style>
</head>
<body>
    <?php include '../include/admin_nav.php'; ?>
    
    <div class="manage-container">
        <div class="page-header">
            <h1><i class="fas fa-car"></i> Manage Cars</h1>
            <button class="add-btn" onclick="openAddModal()">
                <i class="fas fa-plus-circle"></i> Add New Car
            </button>
        </div>
        
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
        
        <!-- Cars Table - Perfectly Aligned with Database Structure -->
        <div class="cars-table">
            <table>
                <thead>
                    <tr>
                        <th>IMAGE</th>
                        <th>CAR NAME</th>
                        <th>CATEGORY</th>
                        <th>PRICE (₱)</th>
                        <th>LOW DP</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($cars) > 0): ?>
                        <?php foreach ($cars as $car): 
                            $images = json_decode($car['images'], true);
                            $firstImage = !empty($images) && is_array($images) ? $images[0] : '';
                            $imagePath = "../uploads/" . $firstImage;
                            $hasValidImage = ($firstImage && file_exists($imagePath));
                        ?>
                            <tr>
                                <td>
                                    <?php if ($hasValidImage): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" class="car-image" alt="<?php echo htmlspecialchars($car['car_name']); ?>">
                                    <?php else: ?>
                                        <div class="car-image" style="background: #222; display: flex; align-items: center; justify-content: center; font-size: 28px;">🚗</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['car_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="category-tag">
                                        <i class="fas fa-tag" style="font-size: 10px; margin-right: 4px;"></i>
                                        <?php echo htmlspecialchars($car['category_name']); ?>
                                    </span>
                                </td>
                                <td class="price-cell">
                                    ₱ <?php echo number_format($car['price'], 0); ?>
                                </td>
                                <td>
                                    <?php if ($car['is_low_dp']): ?>
                                        <span class="low-dp-badge">
                                            <i class="fas fa-bolt"></i> LOW DP
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #555; font-size: 12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $car['car_id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $car['car_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this car? This action cannot be undone.')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fas fa-car-side"></i>
                                No cars found. Click "Add New Car" to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal (Preserved with full functionality) -->
    <div id="carModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">
                    <i class="fas fa-car"></i>
                    <span id="modalTitleText">Add New Car</span>
                </h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data" id="carForm">
                    <input type="hidden" name="car_id" id="car_id" value="">
                    <input type="hidden" name="existing_images" id="existing_images" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-car"></i> Car Name *</label>
                            <input type="text" name="car_name" id="car_name" required placeholder="e.g., Toyota Vios 1.3 E CVT">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Category *</label>
                            <select name="category_id" id="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Price (₱) *</label>
                            <input type="number" name="price" id="price" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hand-holding-usd"></i> Down Payment (₱) *</label>
                            <input type="number" name="down_payment" id="down_payment" step="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Monthly (₱) *</label>
                            <input type="number" name="monthly" id="monthly" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Terms *</label>
                            <input type="text" name="terms" id="terms" required placeholder="e.g., 48 months">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tachometer-alt"></i> Mileage (km) *</label>
                            <input type="number" name="mileage" id="mileage" required placeholder="0">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Included Heading *</label>
                            <select name="included_heading" id="included_heading" required>
                                <option value="Including:">Including:</option>
                                <option value="Included:">Included:</option>
                                <option value="Inclusion:">Inclusion:</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_low_dp" id="is_low_dp" value="1">
                            <label for="is_low_dp">
                                <i class="fas fa-percent"></i> Mark as Low Down Payment
                            </label>
                        </div>
                        <small style="color: #666; display: block; margin-top: 8px; margin-left: 12px;">
                            <i class="fas fa-info-circle"></i> Check this if the down payment is considered low (e.g., below ₱100,000 or special promo offer)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-list-ul"></i> Included Items</label>
                        <div class="included-items-container" id="includedItemsContainer">
                            <div class="included-item">
                                <input type="text" name="included_items[]" placeholder="e.g., Chattel Mortgage Fee">
                                <button type="button" class="remove-item" onclick="removeItem(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="add-item-btn" onclick="addItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="description" rows="4" placeholder="Additional notes, disclaimers, etc.&#10;Example: *additional 1st yr auto insurance (Comprehensive/AOG)*&#10;*estimated computation only*&#10;*subject to bank approval*"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-images"></i> Images</label>
                        <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload images</p>
                            <small>Supports JPG, PNG, GIF, WEBP (Max 5MB each)</small>
                            <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages(this)">
                        </div>
                        <div class="image-preview" id="imagePreview"></div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-save"></i>
                        <span id="submitBtnText">Add Car</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let editCarData = null;
        <?php if ($editMode && isset($editCar)): ?>
        editCarData = <?php echo json_encode($editCar); ?>;
        <?php endif; ?>
        
        function openAddModal() {
            document.getElementById('modalTitleText').innerText = 'Add New Car';
            document.getElementById('carForm').reset();
            document.getElementById('car_id').value = '';
            document.getElementById('existing_images').value = '';
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('includedItemsContainer').innerHTML = '<div class="included-item"><input type="text" name="included_items[]" placeholder="e.g., Chattel Mortgage Fee"><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></div>';
            document.getElementById('is_low_dp').checked = false;
            document.getElementById('submitBtnText').innerText = 'Add Car';
            document.getElementById('carModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function openEditModal() {
            if (!editCarData) return;
            document.getElementById('modalTitleText').innerText = 'Edit Car';
            document.getElementById('car_id').value = editCarData.car_id;
            document.getElementById('car_name').value = editCarData.car_name;
            document.getElementById('category_id').value = editCarData.category_id;
            document.getElementById('price').value = editCarData.price;
            document.getElementById('down_payment').value = editCarData.down_payment;
            document.getElementById('monthly').value = editCarData.monthly;
            document.getElementById('terms').value = editCarData.terms;
            document.getElementById('mileage').value = editCarData.mileage;
            document.getElementById('included_heading').value = editCarData.included_heading;
            document.getElementById('description').value = editCarData.description;
            document.getElementById('is_low_dp').checked = editCarData.is_low_dp == 1;
            document.getElementById('submitBtnText').innerText = 'Update Car';
            
            const includedContainer = document.getElementById('includedItemsContainer');
            includedContainer.innerHTML = '';
            let itemsArr = (editCarData.included_items && Array.isArray(editCarData.included_items)) ? editCarData.included_items : [];
            if(itemsArr.length > 0) {
                itemsArr.forEach(item => {
                    const newItem = document.createElement('div');
                    newItem.className = 'included-item';
                    newItem.innerHTML = `<input type="text" name="included_items[]" value="${escapeHtml(item)}" placeholder="e.g., Chattel Mortgage Fee"><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button>`;
                    includedContainer.appendChild(newItem);
                });
            } else {
                const newItem = document.createElement('div');
                newItem.className = 'included-item';
                newItem.innerHTML = '<input type="text" name="included_items[]" placeholder="e.g., Chattel Mortgage Fee"><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button>';
                includedContainer.appendChild(newItem);
            }
            
            const imagePreview = document.getElementById('imagePreview');
            imagePreview.innerHTML = '';
            const existingImages = [];
            if(editCarData.images && editCarData.images.length) {
                editCarData.images.forEach(image => {
                    if(image) {
                        existingImages.push(image);
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.setAttribute('data-image', image);
                        div.innerHTML = `<img src="../uploads/${escapeHtml(image)}" alt="Car image"><button type="button" class="remove-image" onclick="removeExistingImage(this, '${escapeHtml(image)}')"><i class="fas fa-times"></i></button>`;
                        imagePreview.appendChild(div);
                    }
                });
            }
            document.getElementById('existing_images').value = existingImages.join(',');
            document.getElementById('carModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }
        
        <?php if ($editMode): ?>
        document.addEventListener('DOMContentLoaded', function() { openEditModal(); });
        <?php endif; ?>
        
        function closeModal() { document.getElementById('carModal').style.display = 'none'; document.body.style.overflow = 'auto'; }
        window.onclick = function(event) { if(event.target == document.getElementById('carModal')) closeModal(); }
        function addItem() { const container = document.getElementById('includedItemsContainer'); const newItem = document.createElement('div'); newItem.className = 'included-item'; newItem.innerHTML = '<input type="text" name="included_items[]" placeholder="e.g., Chattel Mortgage Fee"><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button>'; container.appendChild(newItem); }
        function removeItem(button) { button.parentElement.remove(); }
        function previewImages(input) { const preview = document.getElementById('imagePreview'); const files = input.files; for(let i=0;i<files.length;i++){ const reader=new FileReader(); const file=files[i]; reader.onload=function(e){ const div=document.createElement('div'); div.className='preview-item'; div.innerHTML=`<img src="${e.target.result}" alt="Preview"><button type="button" class="remove-image" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`; preview.appendChild(div); }; reader.readAsDataURL(file); } }
        function removeExistingImage(button, imageName) { button.parentElement.remove(); const existingInput = document.getElementById('existing_images'); let existing = existingInput.value ? existingInput.value.split(',') : []; existing = existing.filter(img => img !== imageName); existingInput.value = existing.join(','); }
        document.getElementById('carForm').addEventListener('submit', function() { const existingImages = []; document.querySelectorAll('#imagePreview .preview-item img').forEach(img => { if(img.src && img.src.includes('/uploads/') && !img.src.includes('blob:')) { let parts = img.src.split('/'); let fileName = parts.pop().split('?')[0]; if(fileName) existingImages.push(fileName); } }); document.getElementById('existing_images').value = existingImages.join(','); });
    </script>
</body>
</html>