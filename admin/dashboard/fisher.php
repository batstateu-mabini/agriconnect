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

// Handle Approve and Schedule actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_id'])) {
        $approve_id = (int)$_POST['approve_id'];
        $stmt = $pdo->prepare("UPDATE fisher_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$approve_id]);
    }
    if (isset($_POST['schedule_id'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $schedule_date = $_POST['schedule_date'] ?? null;
        $schedule_time = $_POST['schedule_time'] ?? null;
        // Set status to 'approved' but with schedule set
        $stmt = $pdo->prepare("UPDATE fisher_requests SET schedule_date = ?, schedule_time = ? WHERE id = ?");
        $stmt->execute([$schedule_date, $schedule_time, $schedule_id]);

        // Get user phone and send SMS
        $stmt = $pdo->prepare("SELECT fr.*, u.phone, u.first_name FROM fisher_requests fr LEFT JOIN users u ON fr.user_id = u.id WHERE fr.id = ?");
        $stmt->execute([$schedule_id]);
        $req = $stmt->fetch();
        if ($req && $req['phone']) {
            $recipient = $req['phone'];
            $formatted_date = date('F j, Y', strtotime($schedule_date));
            $message = "Magandang araw, ang iyong fisher request ay nakatakdang i-pickup sa {$formatted_date}, sa ganitong oras {$schedule_time}";
            $sender_id = 'PhilSMS';
            $token = '959|sTvinSqCTo4H41HoogCFyggNenkamLKcjvrQwRlP';
            if (preg_match('/^09\d{9}$/', $recipient)) {
                $recipient = '+63' . substr($recipient, 1);
            }
            if (preg_match('/^639\d{9}$/', $recipient)) {
                $recipient = '+' . $recipient;
            }
            $send_data = [
                'sender_id' => $sender_id,
                'recipient' => $recipient,
                'message' => $message
            ];
            $parameters = json_encode($send_data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://app.philsms.com/api/v3/sms/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $headers = [
                'Content-Type: application/json',
                "Authorization: Bearer $token"
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $get_sms_status = curl_exec($ch);
            curl_close($ch);
        }
    }
    if (isset($_POST['complete_id'])) {
        $complete_id = (int)$_POST['complete_id'];
        $stmt = $pdo->prepare("UPDATE fisher_requests SET status = 'completed' WHERE id = ?");
        $stmt->execute([$complete_id]);
    }
}

// Fetch pending fisher requests
$stmt = $pdo->query("SELECT fr.*, u.first_name, u.last_name, u.email FROM fisher_requests fr JOIN users u ON fr.user_id = u.id WHERE fr.status = 'pending' ORDER BY fr.id DESC");
$pending_requests = $stmt->fetchAll();

// Approved requests (no schedule set)
$stmt = $pdo->query("SELECT fr.*, u.first_name, u.last_name, u.email FROM fisher_requests fr JOIN users u ON fr.user_id = u.id WHERE fr.status = 'approved' AND (fr.schedule_date IS NULL OR fr.schedule_date = '') ORDER BY fr.id DESC");
$approved_requests = $stmt->fetchAll();

// Waiting for pickup (approved, schedule set)
$stmt = $pdo->query("SELECT fr.*, u.first_name, u.last_name, u.email FROM fisher_requests fr JOIN users u ON fr.user_id = u.id WHERE fr.status = 'approved' AND fr.schedule_date IS NOT NULL AND fr.schedule_date != '' ORDER BY fr.id DESC");
$waiting_requests = $stmt->fetchAll();

// Completed fisher requests
$stmt = $pdo->query("SELECT fr.*, u.first_name, u.last_name, u.email FROM fisher_requests fr JOIN users u ON fr.user_id = u.id WHERE fr.status = 'completed' ORDER BY fr.id DESC");
$completed_requests = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard - Fisher Service System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
body { background-color: #f0f4f1; margin: 0; padding: 0; }
.sidebar { background-color: rgba(44, 94, 30, 0.95); position: fixed; top: 0; left: 0; width: 250px; height: 100vh; padding: 2rem 1rem; }
.sidebar .nav-link { color: #fff; font-weight: 500; margin-bottom: 8px; border-radius: 4px; padding: 8px 12px; display: block; transition: background 0.3s; }
.sidebar .nav-link.active, .sidebar .nav-link:hover { background: rgba(74, 140, 60, 0.8); color: #fff; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #2c5e1e; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.main-content { margin-left: 250px; width: calc(100% - 250px); min-height: 100vh; padding: 20px; background: rgba(255, 255, 255, 0.95); }
@media (max-width: 768px) { .sidebar { position: relative; width: 100%; height: auto; } .main-content { margin-left: 0; width: 100%; padding: 15px; } }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <nav class="sidebar">
        <h4 class="text-white text-center mb-4">🧑‍✈️ Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="livestock.php">📊 Livestock</a></li>
            <li class="nav-item"><a class="nav-link" href="crops.php">🌾 Crop Requests</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">🐟 Fisher Requests</a></li>
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

        <!-- Pending Fisher Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">Pending Fisher Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Fishers & Quantity</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($pending_requests) > 0): ?>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr>
                                    <td><?= isset($req['requested_at']) ? date("M d, Y h:i A", strtotime($req['requested_at'])) : '' ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['email']) ?></td>
                                    <td>
                                        <?php
                                            $fishersArr = explode(',', $req['fishers']);
                                            $out = [];
                                            foreach ($fishersArr as $f) {
                                                $parts = explode(':', $f);
                                                if (count($parts) == 2) {
                                                    $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                                } else {
                                                    $out[] = htmlspecialchars($f);
                                                }
                                            }
                                            echo implode(', ', $out);
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['notes']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $req['id'] ?>">Approve</button>
                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal<?= $req['id'] ?>" tabindex="-1" aria-labelledby="approveModalLabel<?= $req['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="approveModalLabel<?= $req['id'] ?>">Confirm Approval</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to approve this fisher request?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="approve_id" value="<?= $req['id'] ?>">
                                                                <button type="submit" class="btn btn-success">Yes, Approve</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No pending fisher requests.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approved Fisher Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-dark">Approved Fisher Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Fishers & Quantity</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($approved_requests) > 0): ?>
                            <?php foreach ($approved_requests as $req): ?>
                                <tr>
                                    <td><?= isset($req['requested_at']) ? date("M d, Y h:i A", strtotime($req['requested_at'])) : '' ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['email']) ?></td>
                                    <td>
                                        <?php
                                            $fishersArr = explode(',', $req['fishers']);
                                            $out = [];
                                            foreach ($fishersArr as $f) {
                                                $parts = explode(':', $f);
                                                if (count($parts) == 2) {
                                                    $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                                } else {
                                                    $out[] = htmlspecialchars($f);
                                                }
                                            }
                                            echo implode(', ', $out);
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['notes']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal<?= $req['id'] ?>">Schedule</button>
                                        <!-- Schedule Modal -->
                                        <div class="modal fade" id="scheduleModal<?= $req['id'] ?>" tabindex="-1" aria-labelledby="scheduleModalLabel<?= $req['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="scheduleModalLabel<?= $req['id'] ?>">Set Schedule & Send SMS</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="schedule_id" value="<?= $req['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="schedule_date_<?= $req['id'] ?>" class="form-label">Date</label>
                                                                <input type="date" class="form-control" name="schedule_date" id="schedule_date_<?= $req['id'] ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="schedule_time_<?= $req['id'] ?>" class="form-label">Time</label>
                                                                <input type="time" class="form-control" name="schedule_time" id="schedule_time_<?= $req['id'] ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">SMS Preview</label>
                                                                <div id="smsPreview_<?= $req['id'] ?>" class="border rounded p-2 bg-light"></div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Set Schedule & Send SMS</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var dateInput = document.getElementById('schedule_date_<?= $req['id'] ?>');
                                                var timeInput = document.getElementById('schedule_time_<?= $req['id'] ?>');
                                                var smsPreview = document.getElementById('smsPreview_<?= $req['id'] ?>');
                                                function formatDate(dateStr) {
                                                    if (!dateStr) return '';
                                                    var dateObj = new Date(dateStr);
                                                    var options = { year: 'numeric', month: 'long', day: 'numeric' };
                                                    return dateObj.toLocaleDateString('en-US', options);
                                                }
                                                function formatTime(timeStr) {
                                                    if (!timeStr) return '';
                                                    var [hour, minute] = timeStr.split(':');
                                                    hour = parseInt(hour);
                                                    var ampm = hour >= 12 ? 'PM' : 'AM';
                                                    hour = hour % 12;
                                                    if (hour === 0) hour = 12;
                                                    return `${hour}:${minute} ${ampm}`;
                                                }
                                                function updateSmsPreview() {
                                                    var date = dateInput.value;
                                                    var time = timeInput.value;
                                                    if (date && time) {
                                                        var formattedDate = formatDate(date);
                                                        var formattedTime = formatTime(time);
                                                        smsPreview.textContent = `Magandang araw, ang iyong fisher request ay nakatakda sa ${formattedDate}, sa ganitong oras ${formattedTime}`;
                                                    } else {
                                                        smsPreview.textContent = '';
                                                    }
                                                }
                                                dateInput && dateInput.addEventListener('input', updateSmsPreview);
                                                timeInput && timeInput.addEventListener('input', updateSmsPreview);
                                            });
                                        </script>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No approved fisher requests.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Waiting for Pickup Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">Schedule Waiting for Pickup</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Fishers & Quantity</th>
                                <th>Notes</th>
                                <th>Schedule Date</th>
                                <th>Schedule Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($waiting_requests) > 0): ?>
                            <?php foreach ($waiting_requests as $req): ?>
                                <tr>
                                    <td><?= isset($req['requested_at']) ? date("M d, Y h:i A", strtotime($req['requested_at'])) : '' ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['email']) ?></td>
                                    <td>
                                        <?php
                                            $fishersArr = explode(',', $req['fishers']);
                                            $out = [];
                                            foreach ($fishersArr as $f) {
                                                $parts = explode(':', $f);
                                                if (count($parts) == 2) {
                                                    $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                                } else {
                                                    $out[] = htmlspecialchars($f);
                                                }
                                            }
                                            echo implode(', ', $out);
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['notes']) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($req['schedule_date']))) ?></td>
                                    <td><?= htmlspecialchars(date('h:i A', strtotime($req['schedule_time']))) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="complete_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Complete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted">No waiting fisher requests.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reminders Table for Today's Scheduled Pickups -->
        <?php
        $today = date('Y-m-d');
        $reminder_requests = $pdo->query("SELECT fr.*, u.first_name, u.last_name, u.email, fr.schedule_time, fr.fishers, fr.notes FROM fisher_requests fr JOIN users u ON fr.user_id = u.id WHERE fr.status = 'approved' AND fr.schedule_date = '$today' ORDER BY fr.schedule_time ASC")->fetchAll();
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white fw-bold">Reminders for Today (<?php echo date('F j, Y'); ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Fishers & Quantity</th>
                                <th>Notes</th>
                                <th>Schedule Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reminder_requests) > 0): ?>
                                <?php foreach ($reminder_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['email']); ?></td>
                                        <td>
                                            <?php
                                                $fishersArr = explode(',', $req['fishers']);
                                                $out = [];
                                                foreach ($fishersArr as $f) {
                                                    $parts = explode(':', $f);
                                                    if (count($parts) == 2) {
                                                        $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                                    } else {
                                                        $out[] = htmlspecialchars($f);
                                                    }
                                                }
                                                echo implode(', ', $out);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($req['notes']); ?></td>
                                        <td><?php echo htmlspecialchars($req['schedule_time']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="complete_id" value="<?php echo $req['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Complete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No reminders for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Completed Fisher Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">Completed Fisher Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Fishers & Quantity</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($completed_requests) > 0): ?>
                            <?php foreach ($completed_requests as $req): ?>
                                <tr>
                                    <td><?= isset($req['requested_at']) ? date("M d, Y h:i A", strtotime($req['requested_at'])) : '' ?></td>
                                    <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                    <td><?= htmlspecialchars($req['email']) ?></td>
                                    <td>
                                        <?php
                                            $fishersArr = explode(',', $req['fishers']);
                                            $out = [];
                                            foreach ($fishersArr as $f) {
                                                $parts = explode(':', $f);
                                                if (count($parts) == 2) {
                                                    $out[] = htmlspecialchars($parts[0]) . ' <span class="badge bg-secondary">' . htmlspecialchars($parts[1]) . '</span>';
                                                } else {
                                                    $out[] = htmlspecialchars($f);
                                                }
                                            }
                                            echo implode(', ', $out);
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No completed fisher requests.</td></tr>
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
