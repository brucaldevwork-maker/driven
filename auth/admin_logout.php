<?php
require_once '../include/config.php';

// Store username for goodbye message
$username = $_SESSION['username'] ?? 'Admin';

// Clear session
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Driven Auto Sales</title>
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
            overflow: hidden;
        }
        
        .logout-container {
            text-align: center;
            animation: fadeInOut 2.5s ease forwards;
        }
        
        @keyframes fadeInOut {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            20% {
                opacity: 1;
                transform: scale(1);
            }
            80% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.9);
                visibility: hidden;
            }
        }
        
        .icon {
            width: 80px;
            height: 80px;
            background: #E50914;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1s ease infinite;
            box-shadow: 0 0 20px rgba(229, 9, 20, 0.5);
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .icon svg {
            width: 45px;
            height: 45px;
            stroke: white;
            stroke-width: 2;
            fill: none;
        }
        
        h2 {
            color: #FFFFFF;
            font-size: 28px;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        
        .goodbye {
            color: #E50914;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .message {
            color: #CCCCCC;
            font-size: 14px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(229, 9, 20, 0.3);
            border-top-color: #E50914;
            border-radius: 50%;
            margin: 30px auto 0;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        .redirect-text {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
    <meta http-equiv="refresh" content="2.5;url=admin_login.php">
</head>
<body>
    <div class="logout-container">
        <div class="icon">
            <svg viewBox="0 0 24 24" stroke="currentColor">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-linecap="round"/>
                <polyline points="16 17 21 12 16 7" stroke-linecap="round"/>
                <line x1="21" y1="12" x2="9" y2="12" stroke-linecap="round"/>
            </svg>
        </div>
        <h2>Goodbye, <?php echo htmlspecialchars($username); ?>!</h2>
        <div class="goodbye">See you soon</div>
        <div class="message">You have been successfully logged out.</div>
        <div class="spinner"></div>
        <div class="redirect-text">Redirecting to login page...</div>
    </div>
</body>
</html>