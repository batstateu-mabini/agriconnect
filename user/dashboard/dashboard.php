<?php
session_start();
require_once '../db.php';

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();
if (!$user) {
    header("Location: ../index.php");
    exit;
}
$user_first_name = $user['first_name'] ?? 'User';
$user_id = $user['id'];

// Handle request submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['animalType'])) {
    $animal_type = $_POST['animalType'];
    if ($animal_type === "Other") {
        $animal_type = trim($_POST['specifyAnimal']);
    }
    $service_type = $_POST['serviceType'];
    $service_notes = $_POST['serviceNotes'] ?? '';
    $barangay = $user['barangay'] ?? '';
    $sitio = $user['sitio'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO service_requests (user_id, animal_type, service_type, barangay, sitio, service_notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $animal_type, $service_type, $barangay, $sitio, $service_notes]);

    header("Location: dashboard.php?success=1");
    exit;
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$appointments_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_count = $stmt->fetchColumn();

// Fetch all requests for display, including schedule
$stmt = $pdo->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY requested_at DESC");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();
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
        <h4 class="text-white text-center mb-4">🌾 Farmer Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#">📊 Livestock</a></li>
            <li class="nav-item"><a class="nav-link" href="crops.php">📝 Crops Request</a></li>
            <li class="nav-item"><a class="nav-link" href="fisher.php">🎣 Fisher Request</a></li>
            <li class="nav-item"><a class="nav-link" href="services.php">📝 Service Logs</a></li>
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

<?php if (isset($_GET['success'])): ?>
<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Your request has been submitted!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 }); // Auto close after 3s
        toast.show();
    }
});
</script>



        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">Pending Requests</span><span>⏳</span>
                        </div>
                        <div class="fs-3 fw-bold"><?php echo $pending_count; ?></div>
                        <div class="text-muted">Awaiting approval</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success">Upcoming Appointments</span><span>📅</span>
                        </div>
                        <div class="fs-3 fw-bold"><?php echo $appointments_count; ?></div>
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

        <!-- Request Button -->
        <div class="text-center mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestModal">Request</button>
        </div>

        <!-- Requests Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                Your Service Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Animal Type</th>
                                <th>Service Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Schedule</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo isset($req['barangay']) ? htmlspecialchars($req['barangay']) : (isset($user['barangay']) ? htmlspecialchars($user['barangay']) : ''); ?></td>
                                        <td><?php echo isset($req['sitio']) ? htmlspecialchars($req['sitio']) : (isset($user['sitio']) ? htmlspecialchars($user['sitio']) : ''); ?></td>
                                        <td>
                                            <?php
                                                $full_notes = htmlspecialchars($req['service_notes']);
                                                $words = explode(' ', $req['service_notes']);
                                                $short = implode(' ', array_slice($words, 0, 2));
                                                $isLong = count($words) > 2;
                                                $preview = $isLong ? $short . ' ...' : $full_notes;
                                            ?>
                                            <span class="notes-preview" style="cursor:pointer; color:#228B22; text-decoration:underline;" onclick="showNotesModal('<?php echo addslashes($full_notes); ?>')">
                                                <?php echo $preview; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($req['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($req['status'] == 'approved'): ?>
                                                <span class="badge bg-info text-dark">Approved</span>
                                            <?php elseif ($req['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($req['schedule_date'] && $req['schedule_time']) {
                                                echo date('F j, Y', strtotime($req['schedule_date'])) . ' ' . date('h:i A', strtotime($req['schedule_time']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date("F j, Y h:i A", strtotime($req['requested_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Request Modal -->
        <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="POST">
                  <div class="mb-3">
                    <label for="animalType" class="form-label">Animal Type</label>
                    <select class="form-select" id="animalType" name="animalType" required onchange="toggleSpecifyAnimal()">
                      <option selected disabled>Select type of animal</option>
                      <option value="Cow">Cow</option>
                      <option value="Pig">Pig</option>
                      <option value="Goat">Goat</option>
                      <option value="Carabao">Carabao</option>
                      <option value="Chicken">Chicken</option>
                      <option value="Other">Other (Specify)</option>
                    </select>
                  </div>
                  <div class="mb-3" id="specifyAnimalWrapper" style="display: none;">
                    <label for="specifyAnimal" class="form-label">Specify Type</label>
                    <input type="text" class="form-control" id="specifyAnimal" name="specifyAnimal" placeholder="Enter animal name">
                  </div>
                  <div class="mb-3">
                    <label for="serviceType" class="form-label">Service Type</label>
                    <select class="form-select" id="serviceType" name="serviceType" required>
                      <option selected disabled>Select service</option>
                      <option>Vaccination</option>
                      <option>Food Assistance</option>
                      <option>Checkup</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="serviceNotes" class="form-label">Notes</label>
                    <textarea class="form-control" id="serviceNotes" name="serviceNotes" rows="3"></textarea>
                  </div>
                  <button type="submit" class="btn btn-success">Submit Request</button>
                </form>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script>
    function toggleSpecifyAnimal() {
      const animalType = document.getElementById('animalType').value;
      const specifyWrapper = document.getElementById('specifyAnimalWrapper');
      if (animalType === 'Other') {
        specifyWrapper.style.display = 'block';
        document.getElementById('specifyAnimal').required = true;
      } else {
        specifyWrapper.style.display = 'none';
        document.getElementById('specifyAnimal').required = false;
      }
    }
  </script>
    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel">Service Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notesModalBody">
                </div>
            </div>
        </div>
    </div>
    <script>
        function showNotesModal(notes) {
            var modalBody = document.getElementById('notesModalBody');
            modalBody.textContent = notes;
            var notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
            notesModal.show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
