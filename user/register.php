<?php
require_once 'db.php';
session_start();

if (isset($_SESSION['email'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}

$error = "";
$success = false;

// Initialize values
$firstName = $lastName = $option = $relativeName = $relationship = $phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function clean_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    $firstName = clean_input($_POST['firstName'] ?? '');
    $lastName = clean_input($_POST['lastName'] ?? '');
    $barangay = clean_input($_POST['barangay'] ?? '');
    $sitio = clean_input($_POST['sitio'] ?? '');
    $option = clean_input($_POST['option'] ?? '');
    $relativeName = clean_input($_POST['relativeName'] ?? '');
    $relationship = clean_input($_POST['relationship'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($option === 'relative') {
        if (empty($relativeName) || empty($relationship)) {
            $error = "Relative name and relationship are required.";
        }
    }

    if (!$error && !preg_match('/^09\d{9}$/', $phone)) {
        $error = "Phone number must be 11 digits and start with 09 (e.g., 09109198735).";
    }

    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }

    if (!$error && $password !== $confirmPassword) {
        $error = "Passwords do not match.";
    }

    if (!$error && strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }

    if (!$error) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already registered.";
        }
    }

    if (!$error) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users 
            (first_name, last_name, barangay, sitio, role, relative_name, relationship, email, phone, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $firstName,
            $lastName,
            $barangay,
            $sitio,
            $option,
            $option === 'relative' ? $relativeName : null,
            $option === 'relative' ? $relationship : null,
            $email,
            $phone,
            $passwordHash
        ]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agriconnect - Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #FDFBEE;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25),
                        0 10px 10px rgba(0,0,0,0.22);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            margin: 20px;
        }
        .form-section, .image-panel {
            padding: 50px;
        }
        .image-panel {
            position: relative;
            background: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1032&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 100, 0, 0.5);
        }
        .image-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .btn-success {
            background-color: #228B22;
            border: none;
        }
        .btn-success:hover {
            background-color: #1e7b1e;
        }
        @media (max-width: 768px) {
            .register-container {
                margin: 0;
                border-radius: 0;
            }
            .row {
                flex-direction: column;
            }
            .image-panel {
                order: 2;
                min-height: 200px;
            }
            .form-section {
                order: 1;
                padding: 30px;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="row g-0">
        <!-- Left Side: Image Panel -->
        <div class="col-md-6 image-panel">
            <div class="image-content">
                <h1>Join Agriconnect</h1>
                <p>Connect with your local agricultural community</p>
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="col-md-6 form-section">
            <div class="text-center mb-4">
                <img src="https://www.zamboanga.com/z/images/2/28/Mabini_Batangas_seal_logo.png" alt="Mabini Logo" style="height: 70px;">
                <h2 class="mt-2 text-success">Register to Agriconnect</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success text-center">Successfully Registered!</div>
            <?php endif; ?>

            <!-- ✅ FORM STARTS HERE -->
            <form method="POST">
                <!-- Personal Info Section -->
                <section id="personalInfo">
                    <div class="mb-3">
                        <input type="text" name="firstName" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars($firstName) ?>">
                    </div>
                    <div class="mb-3">
                        <input type="text" name="lastName" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars($lastName) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="barangay" class="form-label">Barangay</label>
                        <select name="barangay" id="barangay" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="sitio" class="form-control" placeholder="Sitio" required value="<?= htmlspecialchars($_POST['sitio'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09109198735)" required pattern="09[0-9]{9}" maxlength="11" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <!-- Relative fields removed from personal info section -->

                    <div class="d-grid">
                        <button type="button" id="nextBtn" class="btn btn-success">Next</button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-success">Back to Login</a>
                    </div>
                </section>

                <!-- Account Info Section -->
                <section id="accountInfo" style="display: none;">
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <label for="option" class="form-label">Select Role</label>
                        <select name="option" id="option" class="form-select" required>
                            <option value="personal" <?= $option === 'personal' ? 'selected' : '' ?>>Personal</option>
                            <option value="relative" <?= $option === 'relative' ? 'selected' : '' ?>>w/ Relative</option>
                        </select>
                    </div>
                    <div class="mb-3" id="relativeFields" style="display: none;">
                        <input type="text" name="relativeName" id="relativeName" class="form-control mb-2" placeholder="Relative Name" value="<?= htmlspecialchars($relativeName) ?>">
                        <input type="text" name="relationship" id="relationship" class="form-control" placeholder="Relationship" value="<?= htmlspecialchars($relationship) ?>">
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="confirmPassword" class="form-control" placeholder="Confirm Password" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" id="backBtn" class="btn btn-outline-secondary">Back</button>
                        <button type="submit" class="btn btn-success">Register</button>
                    </div>
                </section>
            </form>
            <!-- ✅ FORM ENDS HERE -->
        </div>
    </div>
</div>

<script>
    // Barangay dropdown options
    const barangays = [
        "Anilao II","Anilao I","Bagalangit","Bulacan","Calamias","Estrella","Gasang","Laurel","Ligaya","Mainaga","Mainit","Majuben","Malimatoc I","Malimatoc II","Nag-Iba","Pilahan","Poblacion","Pulang Lupa","Pulong Anahao","Pulong Balibaguhan","Pulong Niogan","Saguing","Sampaguita","San Francisco","San Jose","San Juan","San Teodoro","Santa Ana","Santa Mesa","Santo Niño","Sili","Solo","Talaga Proper","Talaga East"
    ];
    const barangaySelect = document.getElementById('barangay');
    function populateBarangays() {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>' +
            barangays.map(b => `<option value="${b}"${b === "<?= htmlspecialchars($_POST['barangay'] ?? '') ?>" ? ' selected' : ''}>${b}</option>`).join('');
    }
    window.addEventListener('DOMContentLoaded', populateBarangays);

    const option = document.getElementById('option');
    const relativeFields = document.getElementById('relativeFields');
    const relativeName = document.getElementById('relativeName');
    const relationship = document.getElementById('relationship');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const personalInfo = document.getElementById('personalInfo');
    const accountInfo = document.getElementById('accountInfo');

    function updateRelativeFields() {
        const optionSelect = document.getElementById('option');
        const show = optionSelect.value === 'relative';
        relativeFields.style.display = show ? 'block' : 'none';
        relativeName.required = show;
        relationship.required = show;
    }

    document.getElementById('option').addEventListener('change', updateRelativeFields);
    window.addEventListener('load', updateRelativeFields);

    nextBtn.addEventListener('click', () => {
        const inputs = personalInfo.querySelectorAll('input, select');
        for (let input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                return;
            }
        }
        personalInfo.style.display = 'none';
        accountInfo.style.display = 'block';
    });

    backBtn.addEventListener('click', () => {
        accountInfo.style.display = 'none';
        personalInfo.style.display = 'block';
    });
</script>
</body>
</html>
