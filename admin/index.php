<?php
session_start();
if (isset($_SESSION['email'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}

require_once 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['email'] = $user['email'];
        header("Location: dashboard/dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AgriVet - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #FDFBEE;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
                        0 10px 10px rgba(0,0,0,0.22);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 20px; /* Margin for desktop/tablet */
        }
        .login-form, .welcome-panel {
            padding: 50px;
        }
        .login-form h1 {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .welcome-panel {
            position: relative;
            background: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1032&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        /* Overlay for readability */
        .welcome-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 100, 0, 0.5);
        }
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        .welcome-content h1 {
            font-weight: bold;
        }
        .btn-login {
            background-color: #228B22;
            color: white;
            border: none;
            padding: 10px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            background-color: #1e7b1e;
        }
        .btn-signup {
            border: 2px solid white;
            color: white;
            padding: 10px 20px;
            font-weight: bold;
            background: transparent;
        }
        .btn-signup:hover {
            background-color: white;
            color: #228B22;
        }
        /* Mobile view adjustments */
        @media (max-width: 768px) {
            .login-container {
                margin: 0; /* Remove margin on mobile */
                border-radius: 0; /* Flush with screen edges */
            }
            .row {
                flex-direction: column; /* Stack vertically */
            }
            .welcome-panel {
                order: 2; /* Move image to bottom */
                padding: 30px;
                min-height: 200px; /* Prevents tiny image panel */
            }
            .login-form {
                order: 1;
                padding: 30px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="row g-0">
        <!-- Left Side: Login Form -->
        <div class="col-md-6 login-form">
            <div class="text-center mb-3">
                <img src="https://www.zamboanga.com/z/images/2/28/Mabini_Batangas_seal_logo.png" alt="Mabini Logo" style="height: 70px;">
                <h1 class="text-success mt-2">Mabini Agriconnect</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center p-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <div class="text-end mb-3">
                    <a href="#" class="text-secondary">Forgot your password?</a>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-login">SIGN IN</button>
                </div>
            </form>
        </div>

        <!-- Right Side: Agriculture Image Panel -->
        <div class="col-md-6 welcome-panel">
            <div class="welcome-content">
                <h1>Welcome, Admin!</h1>
                <p>Connect with your local agricultural network today</p>
                <a href="register.php" class="btn btn-signup mt-3">SIGN UP</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
