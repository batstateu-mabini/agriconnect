<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php'; // ✅ Include the database connection

// Fetch user details from the database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();
if (!$user) {
    // If user not found, redirect to login
    header("Location: ../index.php");
    exit;
}
// fetch the first_name from the database
$stmt = $pdo->prepare("SELECT first_name FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user_first_name = $stmt->fetchColumn();
if (!$user_first_name) {
    // If first name not found, set a default value
    $user_first_name = 'User';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Farmer Dashboard - Agriculture Service System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f4f1;
            margin: 0;
            padding: 0;
        }

        .container-fluid {
            overflow-x: hidden;
        }

        .sidebar {
            background-color: rgba(44, 94, 30, 0.95);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            z-index: 1000;
            padding: 2rem 1rem;
        }

        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
        }

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

        .sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(74, 140, 60, 0.8);
            color: #fff;
        }

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

        .status-pending {
            background: #fff8e1;
            color: #ffa000;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: bold;
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
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">📝 Service Logs</a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">🚪 Logout</a>
                    </li>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="main-content">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h3">Welcome, <?php echo $user_first_name ?></h1>
                    <div class="user-info d-flex align-items-center gap-2">
                        <span><?php echo htmlspecialchars($_SESSION['email']) ?></span>
                        <div class="user-avatar">F</div>
                    </div>
                </div>

                <div class="tab-content" id="dashboardTabsContent">
                    <!-- Services Tab -->
                    <div class="tab-pane fade" id="services">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="h5 mb-3 text-success">Service History</h2>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Date</th>
                                                <th>Service</th>
                                                <th>Status</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2024-05-30</td>
                                                <td>Irrigation Setup</td>
                                                <td><span class="status-completed">Completed</span></td>
                                                <td>Setup completed successfully.</td>
                                            </tr>
                                            <tr>
                                                <td>2024-05-25</td>
                                                <td>Pest Control</td>
                                                <td><span class="status-completed">Completed</span></td>
                                                <td>All pests removed.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Services Tab END -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>