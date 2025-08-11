<?php
session_start();
if (isset($_SESSION['email'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}

require_once 'db.php'; // ✅ Include the database connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
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
            background: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1032&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 15px rgba(0, 100, 0, 0.3);
        }
        .logo {
            height: 70px;
        }
    </style>
</head>
<body>
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 rounded-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-3">
            <img src="https://www.zamboanga.com/z/images/2/28/Mabini_Batangas_seal_logo.png" alt="Mabini Logo" class="logo mb-2">
            <h1 class="h4 text-success">Mabini AgriVet</h1>
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
            <div class="d-grid">
                <button type="submit" class="btn btn-success">Login</button>
            </div>
            <div class="text-center mt-3">
                <a href="register.php" class="text-success">Don't have an account? Register here</a>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
