<?php
require_once __DIR__ . '/session_helper.php';
require_login('Admin');

$message = "";
$messageType = "";

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $course   = trim($_POST['course'] ?? '');
        $year     = trim($_POST['year'] ?? '');
        $gender   = trim($_POST['gender'] ?? 'Other');
        $role     = trim($_POST['role'] ?? 'Student');

        if (empty($fullname) || empty($email) || empty($password)) {
            $message = "Full Name, Email, and Password are required.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {
            $chk = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
            $chk->bind_param("s", $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $message = "A student with this email address already exists.";
                $messageType = "error";
                $chk->close();
            } else {
                $chk->close();
                $studentCode = "STU-" . date("Y") . "-" . strtoupper(substr(uniqid(), -5));
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $finalUsername = !empty($username) ? $username : strtolower(explode('@', $email)[0]) . rand(100, 999);

                $ins = $conn->prepare("INSERT INTO students (student_id, fullname, username, email, phone, password_hash, role, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                $ins->bind_param("ssssssssss", $studentCode, $fullname, $finalUsername, $email, $phone, $hash, $role, $course, $year, $gender);
                if ($ins->execute()) {
                    $message = "Student {$fullname} ({$studentCode}) added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to add student: " . $ins->error;
                    $messageType = "error";
                }
                $ins->close();
            }
        }
    }

    if ($action === 'edit') {
        $id       = (int)($_POST['student_id'] ?? 0);
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $course   = trim($_POST['course'] ?? '');
        $year     = trim($_POST['year'] ?? '');
        $gender   = trim($_POST['gender'] ?? 'Other');
        $status   = trim($_POST['status'] ?? 'Active');
        $role     = trim($_POST['role'] ?? 'Student');
        $newPass  = trim($_POST['new_password'] ?? '');

        if ($id <= 0 || empty($fullname) || empty($email)) {
            $message = "Student ID, Full Name, and Email are required.";
            $messageType = "error";
        } else {
            $chk = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
            $chk->bind_param("si", $email, $id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $message = "This email address is already assigned to another student.";
                $messageType = "error";
                $chk->close();
            } else {
                $chk->close();
                if (!empty($newPass)) {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE students SET fullname = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ?, role = ?, password_hash = ? WHERE id = ?");
                    $upd->bind_param("sssssssssi", $fullname, $email, $phone, $course, $year, $gender, $status, $role, $hash, $id);
                } else {
                    $upd = $conn->prepare("UPDATE students SET fullname = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ?, role = ? WHERE id = ?");
                    $upd->bind_param("ssssssssi", $fullname, $email, $phone, $course, $year, $gender, $status, $role, $id);
                }

                if ($upd->execute()) {
                    $message = "Student record updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to update student: " . $upd->error;
                    $messageType = "error";
                }
                $upd->close();
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['student_id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            $message = "You cannot delete your own logged-in admin account.";
            $messageType = "error";
        } elseif ($id > 0) {
            $del = $conn->prepare("DELETE FROM students WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                $message = "Student record deleted successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to delete student: " . $del->error;
                $messageType = "error";
            }
            $del->close();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT id, student_id, fullname, username, email, phone, course, year, gender, status, role, created_at FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR student_id LIKE ? OR phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($filterCourse) && $filterCourse !== 'All') {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
    $types .= "s";
}

if (!empty($filterStatus) && $filterStatus !== 'All') {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$students = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - StudentHub</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .admin-container {
            width: 95%;
            max-width: 1300px;
            margin: 25px auto;
        }
        .admin-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filter-panel {
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-panel input, .filter-panel select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-add {
            background: #004aad;
            color: #ffffff !important;
            padding: 9px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .btn-action-edit {
            background: #0284c7;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            border: none;
            cursor: pointer;
        }
        .btn-action-del {
            background: #dc2626;
            color: #ffffff;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            border: none;
            cursor: pointer;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .data-table th, .data-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
        }
        .data-table th {
            background: #004aad;
            color: #ffffff;
            font-weight: 600;
        }
        .data-table tr:hover {
            background: #f8fafc;
        }
        .status-pill {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-Active { background: #dcfce7; color: #15803d; }
        .status-Suspended { background: #fee2e2; color: #b91c1c; }
        .status-Pending { background: #fef3c7; color: #b45309; }
        .modal-form {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-card h3 {
            margin-top: 0;
            color: #004aad;
        }
        .form-row {
            margin-bottom: 12px;
        }
        .form-row label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 13px;
        }
        .form-row input, .form-row select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>

<header>
    <nav>
        <table width="100%">
            <tr>
                <td><h2>StudentHub Admin</h2></td>
                <td align="center">
                    <a href="dashboard.php">Dashboard</a> |
                    <a href="manage_students.php" style="font-weight:bold;color:#004aad;">Students</a> |
                    <a href="manage_events.php">Events</a> |
                    <a href="view_records.php">All Records</a> |
                    <a href="home.html">Portal Home</a>
                </td>
                <td align="right">
                    <span style="font-size:13px;margin-right:10px;color:#555;">Admin: <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                    <a href="logout.php" style="color:#c62828;font-weight:600;">Logout</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main class="admin-container">

    <div class="admin-header-bar">
        <h1>Student Management Module</h1>
        <button class="btn-add" onclick="openAddModal()">+ Add New Student</button>
    </div>

    <?php if (!empty($message)): ?>
    <div style="padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;background:<?php echo $messageType === 'success' ? '#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;' : '#ffebee;color:#c62828;border:1px solid #ef9a9a;'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <form method="get" action="manage_students.php" class="filter-panel">
        <label><strong>Search:</strong></label>
        <input type="text" name="search" placeholder="Name, ID, Email, Phone..." value="<?php echo htmlspecialchars($search); ?>">

        <label><strong>Course:</strong></label>
        <select name="course">
            <option value="All">All Courses</option>
            <?php foreach (['B.Tech', 'BCA', 'B.Sc', 'M.Tech', 'MCA'] as $c): ?>
            <option value="<?php echo $c; ?>" <?php if ($filterCourse === $c) echo 'selected'; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
        </select>

        <label><strong>Status:</strong></label>
        <select name="status">
            <option value="All">All Statuses</option>
            <option value="Active" <?php if ($filterStatus === 'Active') echo 'selected'; ?>>Active</option>
            <option value="Suspended" <?php if ($filterStatus === 'Suspended') echo 'selected'; ?>>Suspended</option>
            <option value="Pending" <?php if ($filterStatus === 'Pending') echo 'selected'; ?>>Pending</option>
        </select>

        <button type="submit" class="btn-add" style="padding:8px 16px;">Filter</button>
        <a href="manage_students.php" style="color:#666;font-size:13px;text-decoration:none;">Reset</a>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Code</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Course</th>
                <th>Year</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?php echo $s['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($s['student_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                    <td><?php echo htmlspecialchars($s['phone']); ?></td>
                    <td><?php echo htmlspecialchars($s['course']); ?></td>
                    <td><?php echo htmlspecialchars($s['year']); ?></td>
                    <td><?php echo htmlspecialchars($s['role']); ?></td>
                    <td><span class="status-pill status-<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                    <td style="white-space:nowrap;">
                        <button class="btn-action-edit" onclick='openEditModal(<?php echo json_encode($s); ?>)'>Edit</button>
                        <form method="post" action="manage_students.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student record?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="btn-action-del">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" align="center" style="padding:30px;color:#777;">No students found matching your criteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</main>

<div id="addModal" class="modal-form">
    <div class="modal-card">
        <h3>Add New Student</h3>
        <form method="post" action="manage_students.php">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Full Name *</label>
                <input type="text" name="fullname" required>
            </div>
            <div class="form-row">
                <label>Username</label>
                <input type="text" name="username" placeholder="Optional">
            </div>
            <div class="form-row">
                <label>Email Address *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-row">
                <label>Phone Number *</label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-row">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-row">
                <label>Course *</label>
                <select name="course" required>
                    <option value="B.Tech">B.Tech</option>
                    <option value="BCA">BCA</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="M.Tech">M.Tech</option>
                    <option value="MCA">MCA</option>
                </select>
            </div>
            <div class="form-row">
                <label>Year *</label>
                <select name="year" required>
                    <option value="First Year">First Year</option>
                    <option value="Second Year">Second Year</option>
                    <option value="Third Year">Third Year</option>
                    <option value="Fourth Year">Fourth Year</option>
                </select>
            </div>
            <div class="form-row">
                <label>Gender</label>
                <select name="gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-row">
                <label>Role</label>
                <select name="role">
                    <option value="Student">Student</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <div style="margin-top:20px;text-align:right;">
                <button type="button" onclick="closeModal('addModal')" style="padding:8px 16px;margin-right:10px;border:1px solid #ccc;background:#f5f5f5;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-add">Save Student</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal-form">
    <div class="modal-card">
        <h3>Edit Student Record</h3>
        <form method="post" action="manage_students.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_id">
            <div class="form-row">
                <label>Full Name *</label>
                <input type="text" name="fullname" id="edit_fullname" required>
            </div>
            <div class="form-row">
                <label>Email Address *</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-row">
                <label>Phone Number</label>
                <input type="text" name="phone" id="edit_phone">
            </div>
            <div class="form-row">
                <label>Course</label>
                <select name="course" id="edit_course">
                    <option value="B.Tech">B.Tech</option>
                    <option value="BCA">BCA</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="M.Tech">M.Tech</option>
                    <option value="MCA">MCA</option>
                </select>
            </div>
            <div class="form-row">
                <label>Year</label>
                <select name="year" id="edit_year">
                    <option value="First Year">First Year</option>
                    <option value="Second Year">Second Year</option>
                    <option value="Third Year">Third Year</option>
                    <option value="Fourth Year">Fourth Year</option>
                </select>
            </div>
            <div class="form-row">
                <label>Gender</label>
                <select name="gender" id="edit_gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-row">
                <label>Account Status</label>
                <select name="status" id="edit_status">
                    <option value="Active">Active</option>
                    <option value="Suspended">Suspended</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>
            <div class="form-row">
                <label>Role</label>
                <select name="role" id="edit_role">
                    <option value="Student">Student</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <div class="form-row">
                <label>Reset Password (leave empty to keep current password)</label>
                <input type="password" name="new_password" placeholder="New password">
            </div>
            <div style="margin-top:20px;text-align:right;">
                <button type="button" onclick="closeModal('editModal')" style="padding:8px 16px;margin-right:10px;border:1px solid #ccc;background:#f5f5f5;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-add">Update Student</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}
function openEditModal(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_fullname').value = student.fullname;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_course').value = student.course;
    document.getElementById('edit_year').value = student.year;
    document.getElementById('edit_gender').value = student.gender;
    document.getElementById('edit_status').value = student.status;
    document.getElementById('edit_role').value = student.role;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-form')) {
        event.target.style.display = 'none';
    }
};
</script>
</body>
</html>
