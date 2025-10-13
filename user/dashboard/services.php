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


// Get counts for service requests by status for this user
$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM service_requests WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$status_counts = [
    'pending' => 0,
    'approved' => 0,
    'completed' => 0
];
foreach ($stmt->fetchAll() as $row) {
    $status_counts[$row['status']] = (int)$row['cnt'];
}

$stmt = $pdo->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY requested_at DESC");
$stmt->execute([$user_id]);
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
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Livestock</a></li>
            <li class="nav-item"><a class="nav-link" href="crops.php">📝 Crops Request</a></li>
            <li class="nav-item"><a class="nav-link" href="fisher.php">🎣 Fisher Request</a></li>
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

        <!-- Service Request Status Counts -->
        <div class="row mb-4">
            <div class="col-md-4 mb-2">
                <div class="card text-center">
                    <div class="card-body">
                        <span class="badge bg-warning text-dark mb-1">Pending</span>
                        <h4><?= $status_counts['pending'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card text-center">
                    <div class="card-body">
                        <span class="badge bg-info text-dark mb-1">Approved</span>
                        <h4><?= $status_counts['approved'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card text-center">
                    <div class="card-body">
                        <span class="badge bg-success mb-1">Completed</span>
                        <h4><?= $status_counts['completed'] ?></h4>
                    </div>
                </div>
            </div>
        </div>



        <!-- Service Logs Table -->
        <!-- Combined Logs Table -->
        <!-- Service Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">Service Requests</div>
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
                        <?php
                        if (count($services) > 0) {
                            foreach ($services as $service) {
                                echo '<tr>';
                                echo '<td>' . date("M d, Y", strtotime($service['requested_at'])) . '</td>';
                                echo '<td>' . htmlspecialchars($service['animal_type']) . '</td>';
                                echo '<td>' . htmlspecialchars($service['service_type']) . '</td>';
                                echo '<td>';
                                if ($service['status'] == 'pending') {
                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                } elseif ($service['status'] == 'approved') {
                                    echo '<span class="badge bg-info text-dark">Approved</span>';
                                } elseif ($service['status'] == 'completed') {
                                    echo '<span class="badge bg-success">Completed</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                echo '</td>';
                                echo '<td>' . htmlspecialchars($service['service_notes']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center text-muted">No service requests found.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Crop Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">Crop Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Crops & Quantity</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM crop_requests WHERE user_id = ? ORDER BY id DESC");
                        $stmt->execute([$user_id]);
                        $crop_requests = $stmt->fetchAll();
                        if (count($crop_requests) > 0) {
                            foreach ($crop_requests as $req) {
                                echo '<tr>';
                                echo '<td>' . (isset($req['requested_at']) ? date("M d, Y", strtotime($req['requested_at'])) : '') . '</td>';
                                $cropsArr = explode(',', $req['crops']);
                                $out = [];
                                foreach ($cropsArr as $c) {
                                    $parts = explode(':', $c);
                                    if (count($parts) == 2) {
                                        $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                    } else {
                                        $out[] = htmlspecialchars($c);
                                    }
                                }
                                echo '<td>' . implode(', ', $out) . '</td>';
                                echo '<td>';
                                if ($req['status'] == 'pending') {
                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                } elseif ($req['status'] == 'approved') {
                                    echo '<span class="badge bg-info text-dark">Approved</span>';
                                } elseif ($req['status'] == 'completed') {
                                    echo '<span class="badge bg-success">Completed</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                echo '</td>';
                                echo '<td>' . htmlspecialchars($req['notes']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center text-muted">No crop requests found.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fisher Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">Fisher Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Fishers & Quantity</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM fisher_requests WHERE user_id = ? ORDER BY id DESC");
                        $stmt->execute([$user_id]);
                        $fisher_requests = $stmt->fetchAll();
                        if (count($fisher_requests) > 0) {
                            foreach ($fisher_requests as $req) {
                                echo '<tr>';
                                echo '<td>' . (isset($req['requested_at']) ? date("M d, Y", strtotime($req['requested_at'])) : '') . '</td>';
                                $fishersArr = explode(',', $req['fishers']);
                                $out = [];
                                foreach ($fishersArr as $c) {
                                    $parts = explode(':', $c);
                                    if (count($parts) == 2) {
                                        $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                    } else {
                                        $out[] = htmlspecialchars($c);
                                    }
                                }
                                echo '<td>' . implode(', ', $out) . '</td>';
                                echo '<td>';
                                if ($req['status'] == 'pending') {
                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                } elseif ($req['status'] == 'approved') {
                                    echo '<span class="badge bg-info text-dark">Approved</span>';
                                } elseif ($req['status'] == 'completed') {
                                    echo '<span class="badge bg-success">Completed</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                echo '</td>';
                                echo '<td>' . htmlspecialchars($req['notes']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center text-muted">No fisher requests found.</td></tr>';
                        }
                        ?>
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
