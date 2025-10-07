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

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedfishers = $_POST['fisherType'] ?? [];
    $quantities = $_POST['fisherQuantity'] ?? [];
    if (in_array('Other', $selectedfishers) && !empty($_POST['otherfisher'])) {
        $selectedfishers = array_diff($selectedfishers, ['Other']);
        $otherfishers = array_map('trim', explode(',', $_POST['otherfisher']));
        $selectedfishers = array_merge($selectedfishers, $otherfishers);
    }
    $notes = $_POST['notes'] ?? '';
    $fisherData = [];
    foreach ($selectedfishers as $fisher) {
        $qty = isset($quantities[$fisher]) ? (int)$quantities[$fisher] : 0;
        if ($qty > 0) {
            $fisherData[] = $fisher . ':' . $qty;
        }
    }
    $fishersStr = implode(',', $fisherData);
    if ($fishersStr) {
        $stmt = $pdo->prepare("INSERT INTO fisher_requests (user_id, fishers, quantity, notes, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $fishersStr, '', $notes, 'pending']);
        $success = true;
    } else {
        $error = 'Please select fishers and specify quantity for each.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Fisher Requests - Agriculture Service System</title>
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
            <li class="nav-item"><a class="nav-link active" href="#">🎣 Fisher Request</a></li>
            <li class="nav-item"><a class="nav-link" href="services.php">📝 Service Logs</a></li>
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

        <!-- fisher Request Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">Fisher Request Form</div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">fisher request submitted successfully!</div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Select fishers & Specify Quantity</label>
                        <div class="row" id="fishersCheckboxes"></div>
                    </div>
                    <div class="mb-3" id="otherfisherWrapper" style="display:none;">
                        <input type="text" class="form-control" name="otherfisher" id="otherfisher" placeholder="Specify fisher(s), separated by comma">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- fisher Requests Table -->
        <?php
        // Fetch fisher requests for this user
        $stmt = $pdo->prepare("SELECT * FROM fisher_requests WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        $fisher_requests = $stmt->fetchAll();
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">Your fisher Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>fishers & Quantity</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fisher_requests) > 0): ?>
                                <?php foreach ($fisher_requests as $req): ?>
                                    <tr>
                                        <td>
                                            <?php
                                                // fishers column format: fisher1:qty1,fisher2:qty2
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
                                                echo implode(', ', $out);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($req['notes']); ?></td>
                                        <td>
                                            <?php if ($req['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($req['status'] == 'approved'): ?>
                                                <span class="badge bg-info text-dark">Approved</span>
                                            <?php elseif ($req['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($req['requested_at']) ? date("M d, Y h:i A", strtotime($req['requested_at'])) : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No fisher requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            // fishers array
            const fishers = [
                'Fishing Gear', 'Hook and line', 'Bouya', 'Fishers Feed', 'Other'
            ];
            const fishersCheckboxes = document.getElementById('fishersCheckboxes');
            fishers.forEach(function(fisher) {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-12 col-md-6 mb-2';
                const rowDiv = document.createElement('div');
                rowDiv.className = 'd-flex align-items-center';

                // Quantity input
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.className = 'form-control me-2';
                qtyInput.name = 'fisherQuantity[' + fisher + ']';
                qtyInput.placeholder = 'Qty';
                qtyInput.min = '1';
                qtyInput.style.width = '90px';
                qtyInput.disabled = true;

                // Checkbox and label
                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';
                const input = document.createElement('input');
                input.className = 'form-check-input';
                input.type = 'checkbox';
                input.name = 'fisherType[]';
                input.value = fisher;
                input.id = 'fisher' + fisher;
                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = input.id;
                label.textContent = fisher;
                checkDiv.appendChild(input);
                checkDiv.appendChild(label);

                // Enable/disable qty input based on checkbox
                input.addEventListener('change', function() {
                    qtyInput.disabled = !input.checked;
                    if (!input.checked) qtyInput.value = '';
                });

                rowDiv.appendChild(qtyInput);
                rowDiv.appendChild(checkDiv);
                colDiv.appendChild(rowDiv);
                fishersCheckboxes.appendChild(colDiv);
            });

            // Show/hide other fisher input
            fishersCheckboxes.addEventListener('change', function() {
                var otherWrapper = document.getElementById('otherfisherWrapper');
                var otherChecked = document.getElementById('fisherOther').checked;
                if (otherChecked) {
                    otherWrapper.style.display = 'block';
                    document.getElementById('otherfisher').required = true;
                } else {
                    otherWrapper.style.display = 'none';
                    document.getElementById('otherfisher').required = false;
                }
            });
        </script>
    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
