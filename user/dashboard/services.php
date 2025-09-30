<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();
if (!$user) {
    header("Location: ../index.php");
    exit;
}

$user_first_name = $user['first_name'] ?? 'User';
$user_id = $user['id'];

// === Search & Filter ===
$query = "SELECT * FROM service_requests WHERE user_id = :user_id";
$params = ['user_id' => $user_id];

if (!empty($_GET['search'])) {
    $query .= " AND service_type LIKE :search";
    $params['search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $query .= " AND status = :status";
    $params['status'] = $_GET['status'];
}

$query .= " ORDER BY requested_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Service Logs - Agriculture Service System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
/* ==== GENERAL ==== */
body {
    background-color: #f0f4f1;
    margin: 0;
    padding: 0;
}

/* ==== SIDEBAR ==== */
.sidebar {
    background-color: rgba(44, 94, 30, 0.95);
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    padding: 2rem 1rem;
}

.sidebar .nav-link {
    color: #fff;
    font-weight: 500;
    margin-bottom: 8px;
    border-radius: 4px;
    padding: 8px 12px;
    display: block;
    transition: background 0.3s;
}

.sidebar .nav-link.active,
.sidebar .nav-link:hover {
    background: rgba(74, 140, 60, 0.8);
    color: #fff;
}

/* ==== USER AVATAR ==== */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #2c5e1e;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* ==== MAIN CONTENT ==== */
.main-content {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 20px;
    background: rgba(255, 255, 255, 0.95);
}

/* ==== RESPONSIVE ==== */
@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
}
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <nav class="sidebar">
        <h4 class="text-white text-center mb-4">🌾 Farmer Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="crops.php">📝 Crops Request</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">📝 Service Logs</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
            <h1 class="h3">Welcome, <?= htmlspecialchars($user_first_name) ?></h1>
            <div class="user-info d-flex align-items-center gap-2">
                <span><?= htmlspecialchars($_SESSION['email']) ?></span>
                <div class="user-avatar"><?= strtoupper($user_first_name[0]) ?></div>
            </div>
        </div>

        <!-- Search & Filter -->
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search service type..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= (($_GET['status'] ?? '') == 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= (($_GET['status'] ?? '') == 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="completed" <?= (($_GET['status'] ?? '') == 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success">Filter</button>
                <a href="services.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Service Logs Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">Your Service History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Animal Type</th>
                                <th>Service Type</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($services) > 0): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?= date("M d, Y", strtotime($service['requested_at'])) ?></td>
                                    <td><?= htmlspecialchars($service['animal_type']) ?></td>
                                    <td><?= htmlspecialchars($service['service_type']) ?></td>
                                    <td>
                                        <?php if ($service['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($service['status'] == 'approved'): ?>
                                            <span class="badge bg-info text-dark">Approved</span>
                                        <?php elseif ($service['status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($service['service_notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No service history found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
