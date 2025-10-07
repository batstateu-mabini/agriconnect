<?php
session_start();
require_once '../db.php';

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

// Get admin user data
$stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();
if (!$user) {
    header("Location: ../index.php");
    exit;
}
$user_first_name = $user['first_name'] ?? 'Admin';

// Get global stats for admin dashboard
$pending_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
$completed_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'completed'")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Farmer Dashboard - Agriculture Service System</title>
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
        <h4 class="text-white text-center mb-4">🌾 Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="livestock.php">📊 Livestock</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="logout.php">🚪 Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
            <h1 class="h3">Welcome, <?php echo htmlspecialchars($user_first_name); ?></h1>
            <div class="user-info d-flex align-items-center gap-2">
                <span><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                <div class="user-avatar"><?php echo strtoupper($user_first_name[0]); ?></div>
            </div>
        </div>



        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">Pending Requests</span><span>⏳</span>
                        </div>
                        <div class="fs-3 fw-bold">
                            <?php
                            if ($pending_count == 0) {
                                echo '<span class="text-success">&#10003;</span>'; // check mark
                            } else {
                                echo $pending_count;
                            }
                            ?>
                        </div>
                        <div class="text-muted">Awaiting approval</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">Ongoing Appointments</span><span>📅</span>
                        </div>
                        <div class="fs-3 fw-bold">
                            <?php
                            if ($appointments_count == 0 && $pending_count == 0) {
                                echo '<span class="text-success">&#10003;</span>'; // check mark
                            } else {
                                echo $appointments_count;
                            }
                            ?>
                        </div>
                        <div class="text-muted">Scheduled this week</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">Completed Services</span><span>✅</span>
                        </div>
                        <div class="fs-3 fw-bold"><?php echo $completed_count; ?></div>
                        <div class="text-muted">Total completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Responsive 2-column row below the 3 cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-success">Column 1 Title</h5>
                        <p class="card-text">Content for the first column. You can put any summary, chart, or info here.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-success">Column 2 Title</h5>
                        <p class="card-text">Content for the second column. You can put any summary, chart, or info here.</p>
                    </div>
                </div>
            </div>
        </div>

      </main>
    </div>
  </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
