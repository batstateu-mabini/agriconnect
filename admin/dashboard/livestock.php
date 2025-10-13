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
// Handle status update and scheduling
$sms_response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['new_status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['new_status'];
    $schedule_date = $_POST['schedule_date'] ?? null;
    $schedule_time = $_POST['schedule_time'] ?? null;
    if (in_array($new_status, ['pending', 'approved', 'completed'])) {
        if ($new_status === 'approved' && $schedule_date && $schedule_time) {
            // Update with schedule
            $stmt = $pdo->prepare("UPDATE service_requests SET status = ?, schedule_date = ?, schedule_time = ? WHERE id = ?");
            $stmt->execute([$new_status, $schedule_date, $schedule_time, $request_id]);

            // Get user phone and service type
            $stmt = $pdo->prepare("SELECT sr.*, u.phone FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.id = ?");
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();
            if ($req && $req['phone']) {
                $recipient = $req['phone'];
                $formatted_date = date('F j, Y', strtotime($schedule_date));
                $message = "Magandang araw, ang {$req['service_type']} ng iyong alagang hayop ay nakatakda sa {$formatted_date}, sa ganitong oras {$schedule_time}";
                // SMS API
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
                if ($get_sms_status === false) {
                    $sms_response = 'cURL error: ' . curl_error($ch);
                } else {
                    $sms_response = $get_sms_status;
                }
                curl_close($ch);
            }
        } else {
            // No schedule, just status update
            $stmt = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $request_id]);
        }
        header("Location: livestock.php?updated=1");
        exit;
    }
}

$pending_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
$completed_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'completed'")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'approved'")->fetchColumn();

// Fetch all service requests for admin view
// Pending requests (only pending)
$pending_requests = $pdo->query("SELECT sr.*, u.first_name, u.email FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.status = 'pending' ORDER BY sr.requested_at DESC")->fetchAll();
// Approved requests (approved, no schedule)
$approved_requests = $pdo->query("SELECT sr.*, u.first_name, u.email, u.phone FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.status = 'approved' AND (sr.schedule_date IS NULL OR sr.schedule_date = '') ORDER BY sr.requested_at DESC")->fetchAll();
// Scheduled requests (approved, with schedule)
$scheduled_requests = $pdo->query("SELECT sr.*, u.first_name, u.email, u.phone FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.status = 'approved' AND sr.schedule_date IS NOT NULL AND sr.schedule_date != '' ORDER BY sr.schedule_date, sr.schedule_time ASC")->fetchAll();
// Completed requests
$completed_requests = $pdo->query("SELECT sr.*, u.first_name, u.email FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.status = 'completed' ORDER BY sr.requested_at DESC")->fetchAll();
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
            <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">📊 Livestock</a></li>
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

        <!-- Pending Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark fw-bold">Pending Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Animal Type</th>
                                <th>Service Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Notes</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pending_requests) > 0): ?>
                                <?php foreach ($pending_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($req['sitio']); ?></td>
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
                                        <td><?php echo date("M d, Y h:i A", strtotime($req['requested_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="showApproveModal(<?php echo $req['id']; ?>)">Approve</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No pending requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approved Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-dark fw-bold">Approved Requests (No Schedule)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Animal Type</th>
                                <th>Service Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Notes</th>
                                <th>Requested At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($approved_requests) > 0): ?>
                                <?php foreach ($approved_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($req['sitio']); ?></td>
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
                                        <td><?php echo date("M d, Y h:i A", strtotime($req['requested_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="showScheduleModal(<?php echo $req['id']; ?>, '<?php echo addslashes($req['first_name'] ?? ''); ?>', '<?php echo addslashes($req['phone'] ?? ''); ?>', '<?php echo addslashes($req['service_type']); ?>')">Schedule</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No approved requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Scheduled Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">Scheduled Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Phone</th>
                                <th>Service Type</th>
                                <th>Animal Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Schedule Date</th>
                                <th>Schedule Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($scheduled_requests) > 0): ?>
                                <?php foreach ($scheduled_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($req['sitio']); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($req['schedule_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($req['schedule_time']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="showCompletedModal(<?php echo $req['id']; ?>)">Completed</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No scheduled requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reminders Table for Today's Appointments (same as scheduled, filtered for today) -->
        <?php
        $today = date('Y-m-d');
        $reminder_requests = $pdo->query("SELECT sr.*, u.first_name, u.phone, sr.schedule_time, sr.animal_type, sr.service_type, sr.barangay, sr.sitio FROM service_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.status = 'approved' AND sr.schedule_date = '$today' ORDER BY sr.schedule_time ASC")->fetchAll();
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white fw-bold">Reminders for Today (<?php echo date('F j, Y'); ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Phone</th>
                                <th>Service Type</th>
                                <th>Animal Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Schedule Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reminder_requests) > 0): ?>
                                <?php foreach ($reminder_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($req['sitio']); ?></td>
                                        <td><?php echo htmlspecialchars($req['schedule_time']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="showCompletedModal(<?php echo $req['id']; ?>)">Completed</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No reminders for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Schedule Modal -->
        <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="scheduleForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="scheduleModalLabel">Set Schedule & Send SMS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="request_id" id="modal_request_id">
                            <input type="hidden" name="new_status" value="approved">
                            <div class="mb-3">
                                <label for="modal_schedule_date" class="form-label">Date</label>
                                <input type="date" class="form-control" name="schedule_date" id="modal_schedule_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="modal_schedule_time" class="form-label">Time</label>
                                <input type="time" class="form-control" name="schedule_time" id="modal_schedule_time" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMS Preview</label>
                                <div id="smsPreview" class="border rounded p-2 bg-light"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Update & Send SMS</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            let scheduleModal = null;
            let approveModal = null;
            let completedModal = null;

            function showApproveModal(requestId) {
                approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
                document.getElementById('approve_request_id').value = requestId;
                approveModal.show();
            }

            function showCompletedModal(requestId) {
                completedModal = new bootstrap.Modal(document.getElementById('completedModal'));
                document.getElementById('completed_request_id').value = requestId;
                completedModal.show();
            }

            function showScheduleModal(requestId, userName, userPhone, serviceType) {
                document.getElementById('modal_request_id').value = requestId;
                document.getElementById('modal_schedule_date').value = '';
                document.getElementById('modal_schedule_time').value = '';
                document.getElementById('smsPreview').textContent = '';
                scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                scheduleModal.show();
                // Update SMS preview on input
                document.getElementById('modal_schedule_date').oninput = updateSmsPreview;
                document.getElementById('modal_schedule_time').oninput = updateSmsPreview;
                function updateSmsPreview() {
                    const date = document.getElementById('modal_schedule_date').value;
                    const time = document.getElementById('modal_schedule_time').value;
                    if (date && time) {
                        document.getElementById('smsPreview').textContent = `Magandang araw, ang ${serviceType} ng iyong alagang hayop ay nakatakda sa ${date}, sa ganitong oras ${time}`;
                    } else {
                        document.getElementById('smsPreview').textContent = '';
                    }
                }
            }
        </script>

        <!-- Completed Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white fw-bold">Completed Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Animal Type</th>
                                <th>Service Type</th>
                                <th>Barangay</th>
                                <th>Sitio</th>
                                <th>Notes</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($completed_requests) > 0): ?>
                                <?php foreach ($completed_requests as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['first_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($req['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                        <td><?php echo htmlspecialchars($req['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($req['sitio']); ?></td>
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
                                        <td><?php echo date("M d, Y h:i A", strtotime($req['requested_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No completed requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approveModalLabel">Warning</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="request_id" id="approve_request_id">
                            <input type="hidden" name="new_status" value="approved">
                            <p>Are you sure you want to approve this request?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Yes, Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Completed Modal -->
        <div class="modal fade" id="completedModal" tabindex="-1" aria-labelledby="completedModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="completedModalLabel">Warning</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="request_id" id="completed_request_id">
                            <input type="hidden" name="new_status" value="completed">
                            <p>Are you sure you want to mark this request as completed?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Yes, Complete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notes Modal -->
        <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notesModalLabel">Service Notes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="notesModalBody"></div>
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

      </main>
    </div>
  </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
