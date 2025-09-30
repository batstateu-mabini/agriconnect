<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Agriconnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Optional: Bootstrap CSS if needed -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="mb-3">Hello World</h1>
        <p class="lead">You are logged in as: <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></p>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>
