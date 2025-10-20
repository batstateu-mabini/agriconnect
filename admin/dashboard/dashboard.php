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

// Count services by type
$service_counts = [
    'Vaccination' => 0,
    'Food Assistance' => 0,
    'Checkup' => 0
];


// === Dashboard Data Insights ===

// Most requested service type
$top_service_query = $pdo->query("SELECT service_type, COUNT(*) as total FROM service_requests GROUP BY service_type ORDER BY total DESC LIMIT 1");
$top_service = $top_service_query->fetch();

// Most requested animal type
$top_animal_query = $pdo->query("SELECT animal_type, COUNT(*) as total FROM service_requests GROUP BY animal_type ORDER BY total DESC LIMIT 1");
$top_animal = $top_animal_query->fetch();

// Most requested crop
$top_crop_query = $pdo->query("SELECT crops, COUNT(*) as total FROM crop_requests GROUP BY crops ORDER BY total DESC LIMIT 1");
$top_crop = $top_crop_query->fetch();

// Most requested fisher service
$top_fisher_query = $pdo->query("SELECT fishers, COUNT(*) as total FROM fisher_requests GROUP BY fishers ORDER BY total DESC LIMIT 1");
$top_fisher = $top_fisher_query->fetch();

// Total users
$total_users_query = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_users_query->fetchColumn();

// Dashboard Summary Text
$dashboard_summary = "
📊 <b>Dashboard Summary:</b><br>
• Most requested service: <b>" . ($top_service['service_type'] ?? 'N/A') . "</b><br>
• Common animal type: <b>" . ($top_animal['animal_type'] ?? 'N/A') . "</b><br>
• Top crop request: <b>" . ($top_crop['crops'] ?? 'N/A') . "</b><br>
• Popular fisher request: <b>" . ($top_fisher['fishers'] ?? 'N/A') . "</b><br>
• Registered users: <b>$total_users</b><br>
";

// === Stock Reminder ===
$current_day = date('N'); // 1 = Monday, 7 = Sunday
if ($current_day == 1 || $current_day == 4) {
    $stock_reminder_message = "🗓️ <b>Stock Check Reminder:</b><br>
    Today is your scheduled <b>stock check day</b>! Please make sure supplies are ready for upcoming service requests.";
} else {
    $stock_reminder_message = "🔔 <b>Stock Reminder:</b><br>
    Stock check happens twice a week (<b>Monday and Thursday</b>). Prepare inventory ahead of time.";
}


$query = $pdo->query("SELECT service_type, COUNT(*) as count FROM service_requests GROUP BY service_type");
while ($row = $query->fetch()) {
    $type = $row['service_type'];
    if (isset($service_counts[$type])) {
        $service_counts[$type] = (int)$row['count'];
    }
}

// Count animals by type
$animal_counts = [
    'Cow' => 0,
    'Pig' => 0,
    'Goat' => 0,
    'Carabao' => 0,
    'Chicken' => 0
];

$query_animals = $pdo->query("SELECT animal_type, COUNT(*) as count FROM service_requests GROUP BY animal_type");
while ($row = $query_animals->fetch()) {
    $type = $row['animal_type'];
    if (isset($animal_counts[$type])) {
        $animal_counts[$type] = (int)$row['count'];
    }
}

// Count crops by type
$crop_counts = [
    'Okra' => 0,
    'Kalamansi' => 0,
    'Corn' => 0,
    'Ampalaya' => 0,
    'Eggplant' => 0,
    'Tomato' => 0,
    'Squash' => 0,
    'Peanut' => 0
];

$query_crops = $pdo->query("SELECT crops, COUNT(*) as count FROM crop_requests GROUP BY crops");
while ($row = $query_crops->fetch()) {
    $type = $row['crops'];
    if (isset($crop_counts[$type])) {
        $crop_counts[$type] = (int)$row['count'];
    }
}

// Count fishers by type
$fisher_counts = [
    'Fishing Gear' => 0,
    'Hook and line' => 0,
    'Bouya' => 0,
    'Fishers Feed' => 0
];

$query_fishers = $pdo->query("SELECT fishers, COUNT(*) as count FROM fisher_requests GROUP BY fishers");
while ($row = $query_fishers->fetch()) {
    $type = $row['fishers'];
    if (isset($fisher_counts[$type])) {
        $fisher_counts[$type] = (int)$row['count'];
    }
}


// Fetch all registered users
$query_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $query_users->fetchAll(PDO::FETCH_ASSOC);

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

  .card-title {
    font-weight: 600;
  }
  .card-text b {
    color: #198754;
  }
  .alert-warning {
    background-color: #fff8dc;
    border: 1px solid #ffe58a;
    color: #664d03;
    font-weight: 500;
  }

  table {
    font-size: 0.9rem;
  }
  thead th {
    white-space: nowrap;
  }
  tbody td {
    vertical-align: middle;
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
            <li class="nav-item"><a class="nav-link" href="crops.php">🌾 Crop Requests</a></li>
            <li class="nav-item"><a class="nav-link" href="fisher.php">🐟 Fisher Requests</a></li>
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

<!-- Insights and Reminder Row -->
<div class="row g-3 mb-3">
  <!-- Column 1: Dashboard Summary -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">🧠 Dashboard Insights</h5>
        <p class="card-text" style="line-height: 1.6;">
          <?= $dashboard_summary ?>
        </p>
        <p class="text-muted mb-0">
          💡 <i>These insights are automatically generated based on the latest data in your system — helping the admin understand trends and plan ahead.</i>
        </p>
      </div>
    </div>
  </div>

  <!-- Column 2: Stock Reminder -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">📦 Stock Reminder</h5>
        <div class="alert alert-warning" role="alert" style="line-height: 1.6;">
          <?= $stock_reminder_message ?>
        </div>
        <p class="text-muted mb-0">
          🧾 <i>This reminder helps ensure that all materials, vaccines, feeds, and supplies are available for smooth weekly operations.</i>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Two Column Section -->
<div class="row g-3 mb-4">
  <!-- Column 1: Service Type Chart -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">📈 Service Requests by Type</h5>
        <canvas id="serviceChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Column 2: Animal Type Chart -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">🐄 Requests by Animal Type</h5>
        <canvas id="animalChart" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Second Row: Crop Requests and Fisher Requests -->
<div class="row g-3 mb-4">
  <!-- Column 1: Crop Requests Chart -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">🌾 Crop Requests by Type</h5>
        <canvas id="cropChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Column 2: Fisher Requests Chart -->
  <div class="col-md-6 col-12">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title text-success mb-3">🎣 Fisher Requests by Type</h5>
        <canvas id="fisherChart" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Registered Users Table -->
<div class="card shadow-sm mt-4">
  <div class="card-body">
    <h5 class="card-title text-success mb-3">👥 Registered Users</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-success text-center">
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Barangay</th>
            <th>Sitio</th>
            <th>Role</th>
            <th>Relative Name</th>
            <th>Relationship</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($users)): ?>
            <?php foreach ($users as $user): ?>
              <tr>
                <td class="text-center"><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                <td><?= htmlspecialchars($user['barangay']) ?></td>
                <td><?= htmlspecialchars($user['sitio']) ?></td>
                <td class="text-capitalize text-center"><?= htmlspecialchars($user['role']) ?></td>
                <td><?= htmlspecialchars($user['relative_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($user['relationship'] ?? '-') ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= date('M d, Y h:i A', strtotime($user['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center text-muted py-4">No registered users found.</td>
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Service Type Bar Chart
const ctxService = document.getElementById('serviceChart').getContext('2d');
new Chart(ctxService, {
    type: 'bar',
    data: {
        labels: ['Vaccination', 'Food Assistance', 'Checkup'],
        datasets: [{
            label: 'Number of Requests',
            data: [
                <?php echo $service_counts['Vaccination']; ?>,
                <?php echo $service_counts['Food Assistance']; ?>,
                <?php echo $service_counts['Checkup']; ?>
            ],
            backgroundColor: [
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Animal Type Bar Chart
const ctxAnimal = document.getElementById('animalChart').getContext('2d');
new Chart(ctxAnimal, {
    type: 'bar',
    data: {
        labels: ['Cow', 'Pig', 'Goat', 'Carabao', 'Chicken'],
        datasets: [{
            label: 'Number of Requests',
            data: [
                <?php echo $animal_counts['Cow']; ?>,
                <?php echo $animal_counts['Pig']; ?>,
                <?php echo $animal_counts['Goat']; ?>,
                <?php echo $animal_counts['Carabao']; ?>,
                <?php echo $animal_counts['Chicken']; ?>
            ],
            backgroundColor: [
                'rgba(46, 204, 113, 0.6)',
                'rgba(241, 196, 15, 0.6)',
                'rgba(52, 152, 219, 0.6)',
                'rgba(155, 89, 182, 0.6)',
                'rgba(231, 76, 60, 0.6)'
            ],
            borderColor: [
                'rgba(46, 204, 113, 1)',
                'rgba(241, 196, 15, 1)',
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)',
                'rgba(231, 76, 60, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Crop Requests Bar Chart
const ctxCrop = document.getElementById('cropChart').getContext('2d');
new Chart(ctxCrop, {
    type: 'bar',
    data: {
        labels: ['Okra', 'Kalamansi', 'Corn', 'Ampalaya', 'Eggplant', 'Tomato', 'Squash', 'Peanut'],
        datasets: [{
            label: 'Number of Crop Requests',
            data: [
                <?php echo $crop_counts['Okra']; ?>,
                <?php echo $crop_counts['Kalamansi']; ?>,
                <?php echo $crop_counts['Corn']; ?>,
                <?php echo $crop_counts['Ampalaya']; ?>,
                <?php echo $crop_counts['Eggplant']; ?>,
                <?php echo $crop_counts['Tomato']; ?>,
                <?php echo $crop_counts['Squash']; ?>,
                <?php echo $crop_counts['Peanut']; ?>
            ],
            backgroundColor: [
                'rgba(46, 204, 113, 0.6)',
                'rgba(39, 174, 96, 0.6)',
                'rgba(52, 152, 219, 0.6)',
                'rgba(155, 89, 182, 0.6)',
                'rgba(241, 196, 15, 0.6)',
                'rgba(230, 126, 34, 0.6)',
                'rgba(231, 76, 60, 0.6)',
                'rgba(52, 73, 94, 0.6)'
            ],
            borderColor: [
                'rgba(46, 204, 113, 1)',
                'rgba(39, 174, 96, 1)',
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)',
                'rgba(241, 196, 15, 1)',
                'rgba(230, 126, 34, 1)',
                'rgba(231, 76, 60, 1)',
                'rgba(52, 73, 94, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Fisher Requests Bar Chart
const ctxFisher = document.getElementById('fisherChart').getContext('2d');
new Chart(ctxFisher, {
    type: 'bar',
    data: {
        labels: ['Fishing Gear', 'Hook and line', 'Bouya', 'Fishers Feed'],
        datasets: [{
            label: 'Number of Fisher Requests',
            data: [
                <?php echo $fisher_counts['Fishing Gear']; ?>,
                <?php echo $fisher_counts['Hook and line']; ?>,
                <?php echo $fisher_counts['Bouya']; ?>,
                <?php echo $fisher_counts['Fishers Feed']; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

</script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
