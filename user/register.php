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
$firstName = $lastName = $option = $relativeName = $relationship = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function clean_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    $firstName = clean_input($_POST['firstName'] ?? '');
    $lastName = clean_input($_POST['lastName'] ?? '');
    $option = clean_input($_POST['option'] ?? '');
    $relativeName = clean_input($_POST['relativeName'] ?? '');
    $relationship = clean_input($_POST['relationship'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($option === 'relative') {
        if (empty($relativeName) || empty($relationship)) {
            $error = "Relative name and relationship are required.";
        }
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
            (first_name, last_name, role, relative_name, relationship, email, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $firstName,
            $lastName,
            $option,
            $option === 'relative' ? $relativeName : null,
            $option === 'relative' ? $relationship : null,
            $email,
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
    <title>AgriVet - Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1032&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
        }
        .form-card {
            background-color: rgba(255, 255, 255, 0.97);
            box-shadow: 0 4px 15px rgba(0, 100, 0, 0.3);
        }
    </style>
</head>
<body>
<div class="container d-flex align-items-center justify-content-center h-100">
    <form id="mainForm" method="post" class="form-card p-4 rounded-4" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <img src="https://www.zamboanga.com/z/images/2/28/Mabini_Batangas_seal_logo.png" alt="Mabini Logo" style="height: 70px;">
            <h2 class="mt-2 text-success">Register to AgriVet</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success text-center">Successfully Registered!</div>
        <?php endif; ?>

        <!-- Personal Info Section -->
        <section id="personalInfo">
            <div class="mb-3">
                <input type="text" name="firstName" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars($firstName) ?>">
            </div>
            <div class="mb-3">
                <input type="text" name="lastName" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars($lastName) ?>">
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
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const option = document.getElementById('option');
    const relativeFields = document.getElementById('relativeFields');
    const relativeName = document.getElementById('relativeName');
    const relationship = document.getElementById('relationship');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const personalInfo = document.getElementById('personalInfo');
    const accountInfo = document.getElementById('accountInfo');

    function updateRelativeFields() {
        const show = option.value === 'relative';
        relativeFields.style.display = show ? 'block' : 'none';
        relativeName.required = show;
        relationship.required = show;
    }

    option.addEventListener('change', updateRelativeFields);
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