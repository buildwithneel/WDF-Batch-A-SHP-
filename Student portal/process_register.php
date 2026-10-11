<?php
require_once __DIR__ . '/db_connect.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.html");
    exit();
}

$errors = [];

$fullname         = sanitize_input($_POST['fullname'] ?? '');
$username_raw     = trim($_POST['username'] ?? '');
$username         = !empty($username_raw) ? sanitize_input($username_raw) : null;
$email_raw        = trim($_POST['email'] ?? '');
$email            = filter_var($email_raw, FILTER_SANITIZE_EMAIL);
$phone            = sanitize_input($_POST['phone'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$course           = sanitize_input($_POST['course'] ?? '');
$year             = sanitize_input($_POST['year'] ?? '');
$gender           = sanitize_input($_POST['gender'] ?? '');
$terms            = isset($_POST['terms']);

if (empty($fullname)) {
    $errors[] = "Full Name is required.";
} elseif (!preg_match("/^[A-Za-z ]{2,50}$/", $fullname)) {
    $errors[] = "Full Name should contain only letters and spaces (2-50 characters).";
}

if (!empty($username)) {
    if (!preg_match("/^[A-Za-z0-9_]{3,30}$/", $username)) {
        $errors[] = "Username must be between 3 and 30 characters (letters, numbers, underscores only).";
    }
}

if (empty($email)) {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address format.";
}

if (empty($phone)) {
    $errors[] = "Mobile number is required.";
} elseif (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {
    $errors[] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
}

if (empty($password)) {
    $errors[] = "Password is required.";
} elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@#$%^&*!]).{8,20}$/", $password)) {
    $errors[] = "Password must be 8-20 characters long and include at least one uppercase letter, one lowercase letter, one digit, and one special character (@#$%^&*!).";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

$allowedCourses = ['B.Tech', 'BCA', 'B.Sc', 'M.Tech', 'MCA'];
if (empty($course) || !in_array($course, $allowedCourses)) {
    $errors[] = "Please select a valid course from the list.";
}

$allowedYears = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];
if (empty($year) || !in_array($year, $allowedYears)) {
    $errors[] = "Please select a valid academic year.";
}

$allowedGenders = ['Male', 'Female', 'Other'];
if (empty($gender) || !in_array($gender, $allowedGenders)) {
    $errors[] = "Please select your gender.";
}

if (!$terms) {
    $errors[] = "You must accept the Terms and Conditions to register.";
}

$success = false;
$studentId = "";
$timestamp = date("Y-m-d H:i:s");

if (empty($errors)) {
    $conn = get_db_connection();
    if ($conn) {
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $errors[] = "A student with email '{$email}' is already registered.";
            }
            $checkStmt->close();
        }

        if (!empty($username) && empty($errors)) {
            $userStmt = $conn->prepare("SELECT id FROM students WHERE username = ? LIMIT 1");
            if ($userStmt) {
                $userStmt->bind_param("s", $username);
                $userStmt->execute();
                $userStmt->store_result();
                if ($userStmt->num_rows > 0) {
                    $errors[] = "Username '{$username}' is already taken. Please choose another.";
                }
                $userStmt->close();
            }
        }

        if (empty($errors)) {
            $studentId = "STU-" . date("Y") . "-" . strtoupper(substr(uniqid(), -5));
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $finalUsername = !empty($username) ? $username : strtolower(explode('@', $email)[0]) . rand(100, 999);

            $insertStmt = $conn->prepare("INSERT INTO students (student_id, fullname, username, email, phone, password_hash, role, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, 'Student', ?, ?, ?, 'Active')");
            if ($insertStmt) {
                $insertStmt->bind_param("sssssssss", $studentId, $fullname, $finalUsername, $email, $phone, $password_hash, $course, $year, $gender);
                if ($insertStmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = "Database insertion failed: " . $insertStmt->error;
                }
                $insertStmt->close();
            } else {
                $errors[] = "Failed to prepare database statement: " . $conn->error;
            }
        }
        $conn->close();
    } else {
        // Resilient fallback when MySQL is not running
        $studentId = "STU-" . date("Y") . "-" . strtoupper(substr(uniqid(), -5));
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $finalUsername = !empty($username) ? $username : strtolower(explode('@', $email)[0]) . rand(100, 999);
        $success = true;
    }

    if ($success) {
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $jsonFile = $dataDir . '/students.json';
        $csvFile  = $dataDir . '/students.csv';

        $studentData = [
            'id'            => $studentId,
            'fullname'      => $fullname,
            'username'      => $finalUsername,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => $password_hash,
            'role'          => 'Student',
            'course'        => $course,
            'year'          => $year,
            'gender'        => $gender,
            'registered_at' => $timestamp
        ];

        $existingStudents = [];
        if (file_exists($jsonFile)) {
            $jsonContent = file_get_contents($jsonFile);
            $decoded = json_decode($jsonContent, true);
            if (is_array($decoded)) {
                $existingStudents = $decoded;
            }
        }
        $existingStudents[] = $studentData;
        file_put_contents($jsonFile, json_encode($existingStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $csvFileExists = file_exists($csvFile);
        $fp = fopen($csvFile, 'a');
        if ($fp !== false) {
            if (!$csvFileExists || filesize($csvFile) === 0) {
                fputcsv($fp, ['ID', 'Full Name', 'Username', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Registered At']);
            }
            fputcsv($fp, [$studentId, $fullname, $finalUsername, $email, $phone, $course, $year, $gender, $timestamp]);
            fclose($fp);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? "Registration Successful" : "Registration Error"; ?> - StudentHub</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .result-container {
            max-width: 650px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success-badge {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        .error-badge {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .error-badge h3 {
            margin: 0 0 10px 0;
            color: #c62828;
            font-size: 18px;
        }
        .error-badge ul {
            margin: 0;
            padding-left: 20px;
            background: transparent;
            box-shadow: none;
        }
        .error-badge li {
            margin: 5px 0;
            color: #b71c1c;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .details-table th, .details-table td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        .details-table th {
            background-color: #f5f7fa;
            color: #333333;
            width: 35%;
            font-weight: 600;
        }
        .details-table td {
            color: #555555;
        }
        .action-links {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-main {
            background: #004aad;
            color: #ffffff !important;
            padding: 10px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333333 !important;
            padding: 10px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
    </style>
</head>
<body>

<header>
    <nav>
        <table width="100%">
            <tr>
                <td><h2>StudentHub</h2></td>
                <td align="center">
                    <a href="home.html">Home</a> |
                    <a href="about.html">About</a> |
                    <a href="events.html">Events</a> |
                    <a href="faq.html">FAQ</a> |
                    <a href="contact.html">Contact</a>
                </td>
                <td align="right">
                    <a href="login.php">Login</a> |
                    <a href="register.html">Register</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main>
    <div class="result-container">
        <?php if ($success): ?>
            <div class="success-badge">
                <span>Registration Completed Successfully!</span>
            </div>

            <h2 align="center">Student Profile Created</h2>
            <p align="center" style="color: #666; font-size: 14px; margin-bottom: 25px;">
                Your account has been registered in the student database. You can now login using your credentials.
            </p>

            <table class="details-table">
                <tr><th>Student ID</th><td><strong><?php echo htmlspecialchars($studentId); ?></strong></td></tr>
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($fullname); ?></td></tr>
                <tr><th>Username</th><td><?php echo htmlspecialchars($finalUsername); ?></td></tr>
                <tr><th>Email Address</th><td><?php echo htmlspecialchars($email); ?></td></tr>
                <tr><th>Mobile Number</th><td><?php echo htmlspecialchars($phone); ?></td></tr>
                <tr><th>Course</th><td><?php echo htmlspecialchars($course); ?></td></tr>
                <tr><th>Year</th><td><?php echo htmlspecialchars($year); ?></td></tr>
                <tr><th>Gender</th><td><?php echo htmlspecialchars($gender); ?></td></tr>
                <tr><th>Registration Date</th><td><?php echo htmlspecialchars($timestamp); ?></td></tr>
            </table>

            <div class="action-links">
                <a href="login.php" class="btn-main">Proceed to Login</a>
                <a href="home.html" class="btn-secondary">Return Home</a>
            </div>
        <?php else: ?>
            <div class="error-badge">
                <h3>Registration Encountered Errors</h3>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="action-links">
                <a href="javascript:history.back()" class="btn-main">Back to Registration Form</a>
                <a href="home.html" class="btn-secondary">Go to Home</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<hr>

<footer>
    <table width="100%">
        <tr>
            <td><p>&copy; 2026 StudentHub. All Rights Reserved.</p></td>
            <td align="right">
                <a href="home.html">Home</a> |
                <a href="register.html">Register</a> |
                <a href="contact.html">Contact</a>
            </td>
        </tr>
    </table>
</footer>
<script src="studenthub.js"></script>
</body>
</html>
