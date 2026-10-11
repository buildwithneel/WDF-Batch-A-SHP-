<?php
require_once __DIR__ . '/session_helper.php';

init_session();
check_remember_me();

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'Admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both your email/username and password.";
    } else {
        $conn = get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT id, student_id, fullname, username, email, password_hash, role, status, course FROM students WHERE email = ? OR username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] === 'Suspended') {
                        $error = "Your account has been suspended. Please contact the administrator.";
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['fullname'] = $user['fullname'];
                        $_SESSION['username'] = $user['username'] ?? '';
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'] ?? 'Student';
                        $_SESSION['course'] = $user['course'] ?? '';
                        $_SESSION['last_activity'] = time();

                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $updateStmt = $conn->prepare("UPDATE students SET remember_token = ? WHERE id = ?");
                            if ($updateStmt) {
                                $updateStmt->bind_param("si", $token, $user['id']);
                                $updateStmt->execute();
                                $updateStmt->close();
                                setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                            }
                        }

                        $conn->close();

                        if ($user['role'] === 'Admin') {
                            header("Location: admin_dashboard.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit();
                    }
                } else {
                    $error = "Invalid email/username or password.";
                }
            } else {
                $error = "Database query error: " . $conn->error;
            }
            $conn->close();
        } else {
            $jsonFile = __DIR__ . '/data/students.json';
            $foundUser = null;
            if (file_exists($jsonFile)) {
                $students = json_decode(file_get_contents($jsonFile), true);
                if (is_array($students)) {
                    foreach ($students as $stu) {
                        if (
                            (isset($stu['email']) && strcasecmp($stu['email'], $identifier) === 0) ||
                            (isset($stu['username']) && strcasecmp($stu['username'], $identifier) === 0)
                        ) {
                            $foundUser = $stu;
                            break;
                        }
                    }
                }
            }

            if ($foundUser && password_verify($password, $foundUser['password_hash'] ?? '')) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $foundUser['id'] ?? 1;
                $_SESSION['student_id'] = $foundUser['id'] ?? 'STU-001';
                $_SESSION['fullname'] = $foundUser['fullname'] ?? 'Student';
                $_SESSION['username'] = $foundUser['username'] ?? '';
                $_SESSION['email'] = $foundUser['email'] ?? '';
                $_SESSION['role'] = $foundUser['role'] ?? 'Student';
                $_SESSION['course'] = $foundUser['course'] ?? '';
                $_SESSION['last_activity'] = time();

                if (($foundUser['role'] ?? '') === 'Admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } elseif ($foundUser) {
                $error = "Invalid email/username or password.";
            } else {
                $error = "Invalid credentials. Unable to connect to MySQL and user not found in local records.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="common.css">
    <title>Login - StudentHub</title>
</head>
<body>

<?php render_navigation('login'); ?>

<hr>

<main>

<h1 align="center">Student Login</h1>

<table width="100%" cellpadding="20">

<tr>

<td width="50%" align="center">

<img src="pic1.png" alt="Login" width="350" height="300">

<h2>Welcome Back!</h2>

<p>
Login to access your dashboard, attendance, results, events, profile, and other StudentHub services.
</p>

</td>

<td width="50%">

<?php if (!empty($error)): ?>
<div style="background:#ffebee;color:#c62828;border:1px solid #ef9a9a;padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:14px;">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:14px;">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;margin-bottom:15px;font-size:13px;">
    <div style="font-weight:600;color:#1e40af;margin-bottom:8px;">🔑 Demo Test Accounts:</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" onclick="fillCreds('admin', 'Admin@123')" style="background:#1e40af;color:#fff;border:none;border-radius:4px;padding:5px 10px;cursor:pointer;font-size:12px;font-weight:500;">👤 Admin (admin / Admin@123)</button>
        <button type="button" onclick="fillCreds('rahul', 'Student@123')" style="background:#0284c7;color:#fff;border:none;border-radius:4px;padding:5px 10px;cursor:pointer;font-size:12px;font-weight:500;">🎓 Student (rahul / Student@123)</button>
    </div>
</div>

<form action="login.php" method="post">

<p>
<label>Email Address or Username</label><br>
<input type="text" name="email" size="40" placeholder="Enter your email or username" required>
</p>

<p>
<label>Password</label><br>
<input type="password" name="password" size="40" placeholder="Enter password" required>
</p>

<p>
<input type="checkbox" name="remember" id="remember">
<label for="remember">Remember Me</label>
</p>

<p>
<input type="submit" value="Login">
<input type="reset" value="Clear">
</p>

<p>
<a href="forgetpassword.html">Forgot Password?</a>
</p>

<p>
Don't have an account?
<a href="register.html">Register Here</a>
</p>

</form>

</td>

</tr>

</table>

<hr>

<section>

<h2 align="center">Student Benefits</h2>

<ul>
    <li>Access Attendance Records</li>
    <li>View Semester Results</li>
    <li>Register for Events</li>
    <li>Download Certificates</li>
    <li>Update Student Profile</li>
    <li>Receive Notifications</li>
</ul>

</section>

</main>

<hr>

<footer>

<table width="100%">

<tr>

<td>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
</td>

<td align="right">
<a href="home.html">Home</a> |
<a href="register.html">Register</a> |
<a href="contact.html">Contact</a>
</td>

</tr>

</table>

</footer>
<script>
function fillCreds(u, p) {
    var emailInput = document.querySelector('input[name="email"]');
    var passInput = document.querySelector('input[name="password"]');
    if (emailInput && passInput) {
        emailInput.value = u;
        passInput.value = p;
    }
}
</script>
<script src="studenthub.js"></script>
</body>
</html>
