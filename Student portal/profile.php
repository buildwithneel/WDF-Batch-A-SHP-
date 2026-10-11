<?php
require_once __DIR__ . '/session_helper.php';
require_login();

$userId = $_SESSION['user_id'];
$message = "";
$messageType = "";

$conn = get_db_connection();
$user = null;

if ($conn) {
    $stmt = $conn->prepare("SELECT id, student_id, fullname, username, email, phone, course, year, gender, role, created_at FROM students WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fallback to session / json if db query returned nothing
if (!$user) {
    $user = [
        'id' => $userId,
        'student_id' => $_SESSION['student_id'] ?? 'STU-2026-00001',
        'fullname' => $_SESSION['fullname'] ?? 'Student User',
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? 'student@studenthub.com',
        'phone' => '9876543210',
        'course' => $_SESSION['course'] ?? 'B.Tech',
        'year' => 'Second Year',
        'gender' => 'Male',
        'role' => $_SESSION['role'] ?? 'Student'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $gender   = trim($_POST['gender'] ?? 'Other');

    if (empty($fullname)) {
        $message = "Full Name cannot be empty.";
        $messageType = "error";
    } elseif (!empty($phone) && !preg_match("/^[0-9+\- ]{10,15}$/", $phone)) {
        $message = "Please enter a valid 10-15 digit phone number.";
        $messageType = "error";
    } else {
        if ($conn) {
            $upd = $conn->prepare("UPDATE students SET fullname = ?, phone = ?, course = ?, gender = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("ssssi", $fullname, $phone, $course, $gender, $userId);
                if ($upd->execute()) {
                    $message = "Profile updated successfully!";
                    $messageType = "success";
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['course']   = $course;
                    $user['fullname'] = $fullname;
                    $user['phone']    = $phone;
                    $user['course']   = $course;
                    $user['gender']   = $gender;
                } else {
                    $message = "Failed to update profile: " . $upd->error;
                    $messageType = "error";
                }
                $upd->close();
            }
        } else {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['course']   = $course;
            $user['fullname'] = $fullname;
            $user['phone']    = $phone;
            $user['course']   = $course;
            $user['gender']   = $gender;
            $message = "Profile updated (local session mode).";
            $messageType = "success";
        }
    }
}

if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - StudentHub</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="common.css">
</head>
<body>

<?php render_navigation('profile'); ?>

<hr>

<main>

<h1 align="center">Student Profile</h1>

<?php if (!empty($message)): ?>
<div style="max-width:800px;margin:15px auto;padding:12px 18px;border-radius:8px;font-size:14px;<?php echo $messageType === 'success' ? 'background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;' : 'background:#ffebee;color:#c62828;border:1px solid #ef9a9a;'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<table width="100%" cellpadding="20">

<tr>

<td width="30%" align="center">

<img src="pic1.png" alt="Student Profile" width="180" height="180" style="border-radius:50%;object-fit:cover;box-shadow:0 6px 15px rgba(0,0,0,0.15);">

<br><br>

<p><b>Role:</b> <?php echo htmlspecialchars($user['role'] ?? 'Student'); ?></p>
<p><b>Status:</b> <span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">Active</span></p>

</td>

<td width="70%">

<form action="profile.php" method="post">
<input type="hidden" name="update_profile" value="1">

<table cellpadding="8">

<tr>
<td><label>Student ID / Enrollment</label></td>
<td><input type="text" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" size="35" readonly style="background:#f1f5f9;cursor:not-allowed;"></td>
</tr>

<tr>
<td><label>Full Name</label></td>
<td><input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" size="35" required></td>
</tr>

<tr>
<td><label>Username</label></td>
<td><input type="text" value="<?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?>" size="35" readonly style="background:#f1f5f9;cursor:not-allowed;"></td>
</tr>

<tr>
<td><label>Email Address</label></td>
<td><input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" size="35" readonly style="background:#f1f5f9;cursor:not-allowed;"></td>
</tr>

<tr>
<td><label>Phone Number</label></td>
<td><input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" size="35"></td>
</tr>

<tr>
<td><label>Course / Program</label></td>
<td>
<select name="course">
<?php
$courses = ['B.Tech', 'BCA', 'B.Sc', 'M.Tech', 'MCA', 'Administration'];
foreach ($courses as $c) {
    $sel = (($user['course'] ?? '') === $c) ? 'selected' : '';
    echo "<option value=\"$c\" $sel>$c</option>";
}
?>
</select>
</td>
</tr>

<tr>
<td><label>Academic Year</label></td>
<td><input type="text" value="<?php echo htmlspecialchars($user['year'] ?? 'Second Year'); ?>" size="35" readonly style="background:#f1f5f9;cursor:not-allowed;"></td>
</tr>

<tr>
<td><label>Gender</label></td>
<td>
<label><input type="radio" name="gender" value="Male" <?php if (($user['gender'] ?? '') === 'Male') echo 'checked'; ?>> Male</label> &nbsp;
<label><input type="radio" name="gender" value="Female" <?php if (($user['gender'] ?? '') === 'Female') echo 'checked'; ?>> Female</label> &nbsp;
<label><input type="radio" name="gender" value="Other" <?php if (($user['gender'] ?? '') === 'Other') echo 'checked'; ?>> Other</label>
</td>
</tr>

<tr>
<td colspan="2" align="center" style="padding-top:15px;">
<input type="submit" value="Save Profile Changes">
<input type="reset" value="Reset">
</td>
</tr>

</table>

</form>

</td>

</tr>

</table>

<hr>

<h2>Academic Information</h2>

<table border="1" width="100%" cellpadding="10">

<tr align="center">
<th>Enrollment No.</th>
<th>Program</th>
<th>Semester</th>
<th>CGPA</th>
<th>Attendance</th>
</tr>

<tr align="center">
<td><?php echo htmlspecialchars($user['student_id'] ?? '25DIT002'); ?></td>
<td><?php echo htmlspecialchars($user['course'] ?? 'Information Technology'); ?></td>
<td>Semester 5</td>
<td>8.75</td>
<td>92%</td>
</tr>

</table>

<hr>

<h2>Enrolled Skills & Certifications</h2>

<ul>
<li>C / C++ Programming</li>
<li>Java & Python Development</li>
<li>Full Stack Web Development (HTML5, CSS3, JavaScript, PHP, MySQL)</li>
<li>Database Architecture & Optimization</li>
</ul>

</main>

<hr>

<footer>

<table width="100%">

<tr>

<td>
<p>&copy; 2026 StudentHub. All Rights Reserved.</p>
</td>

<td align="right">
<a href="dashboard.php">Dashboard</a> |
<a href="contact.html">Contact</a> |
<a href="logout.php">Logout</a>
</td>

</tr>

</table>

</footer>

<script src="studenthub.js"></script>
</body>
</html>
