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
            <a class="nav-link active" href="#">📊 Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="services.php">📝 Service Logs</a>
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
          <!-- Dashboard Tab -->
          <div class="tab-pane fade show active" id="dashboard">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="card shadow-sm">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold text-success">Upcoming Appointments</span>
                      <span>📅</span>
                    </div>
                    <div class="fs-3 fw-bold">0</div>
                    <div class="text-muted">Scheduled this week</div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card shadow-sm">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold text-success">Completed Services</span>
                      <span>✅</span>
                    </div>
                    <div class="fs-3 fw-bold">0</div>
                    <div class="text-muted">Total completed</div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card shadow-sm">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold text-success">Pending Requests</span>
                      <span>⏳</span>
                    </div>
                    <div class="fs-3 fw-bold">0</div>
                    <div class="text-muted">Awaiting approval</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="text-center mt-4">
              <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestModal">Request</button>
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
                    <form id="requestForm">
                        <div class="mb-3">
                        <label for="animalType" class="form-label">Animal Type</label>
                        <select class="form-select" id="animalType" required onchange="toggleSpecifyAnimal()">
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

                      <div class="mb-3">
                        <label for="serviceType" class="form-label">Service Type</label>
                        <select class="form-select" id="serviceType" required>
                          <option selected disabled>Select service</option>
                          <option>Vaccination</option>
                          <option>Crop Consultation</option>
                          <option>Irrigation Setup</option>
                          <option>Pest Control</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="serviceNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="serviceNotes" rows="3"></textarea>
                      </div>
                      <button type="submit" class="btn btn-success">Submit Request</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <!-- Dashboard Tab END -->
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
