<?php
require_once '../include/config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: ../admin/admin_dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username already exists';
            } else {
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $password]);
                
                $success = 'Registration successful! Redirecting to login...';
                echo '<meta http-equiv="refresh" content="2;url=admin_login.php">';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - Driven Online Auto Sales</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0D0D0D 0%, #1A1A1A 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .glow {
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(229,9,20,0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: glowMove 15s infinite alternate;
        }
        
        @keyframes glowMove {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(100px, 100px);
            }
        }
        
        .register-container {
            background: rgba(26, 26, 26, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(229, 9, 20, 0.1);
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
            backdrop-filter: blur(10px);
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
        
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand h2 {
            font-size: 28px;
            color: #FFFFFF;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .brand .tagline {
            color: #E50914;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .brand .line {
            width: 50px;
            height: 3px;
            background: #E50914;
            margin: 15px auto 0;
            border-radius: 3px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #CCCCCC;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: #0D0D0D;
            border: 1px solid #333;
            border-radius: 12px;
            font-size: 15px;
            color: #FFFFFF;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #E50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.2);
        }
        
        .form-group input::placeholder {
            color: #666;
        }
        
        .register-btn {
            width: 100%;
            padding: 14px;
            background: #E50914;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .register-btn:hover {
            background: #FF2A2A;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.4);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: rgba(229, 9, 20, 0.15);
            color: #FF6B6B;
            border-left: 3px solid #E50914;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border-left: 3px solid #2ecc71;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        
        .login-link p {
            color: #CCCCCC;
            font-size: 14px;
        }
        
        .login-link a {
            color: #E50914;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }
        
        .login-link a:hover {
            color: #FF2A2A;
        }
        
        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #E50914;
            transition: width 0.3s;
        }
        
        .login-link a:hover::after {
            width: 100%;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 480px) {
            .register-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .brand h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="glow" style="top: 10%; left: 10%;"></div>
    <div class="glow" style="bottom: 10%; right: 10%; animation-delay: -5s;"></div>
    
    <div class="register-container">
        <div class="brand">
            <h2>DRIVEN</h2>
            <div class="tagline">Create Account</div>
            <div class="line"></div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="off" placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Create a password">
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Confirm your password">
            </div>
            
            <button type="submit" class="register-btn">Register</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="admin_login.php">Login here</a></p>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Driven Online Auto Sales</p>
        </div>
    </div>
    
    <script>
        // Input focus animation
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateX(5px)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateX(0)';
            });
        });
        
        // Password strength indicator
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const strength = this.value.length;
                if (strength > 0 && strength < 4) {
                    this.style.borderColor = '#e74c3c';
                } else if (strength >= 4 && strength < 8) {
                    this.style.borderColor = '#f39c12';
                } else if (strength >= 8) {
                    this.style.borderColor = '#2ecc71';
                } else {
                    this.style.borderColor = '#333';
                }
            });
        }
    </script>
</body>
</html>