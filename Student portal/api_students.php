<?php
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/db_connect.php';

init_session();
header('Content-Type: application/json; charset=utf-8');

function send_json($status, $data = null, $message = '') {
    http_response_code($status);
    echo json_encode([
        'success' => ($status >= 200 && $status < 300),
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sync_students_data() {
    $conn = get_db_connection();
    if (!$conn) return;
    $res = $conn->query("SELECT id, student_id, fullname, username, email, phone, course, year, gender, role, status, created_at FROM students ORDER BY id DESC");
    $list = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'id' => $row['student_id'],
                'fullname' => $row['fullname'],
                'username' => $row['username'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'course' => $row['course'],
                'year' => $row['year'],
                'gender' => $row['gender'],
                'role' => $row['role'],
                'status' => $row['status'],
                'registered_at' => $row['created_at']
            ];
        }
        $res->free();
    }
    $conn->close();

    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    file_put_contents($dataDir . '/students.json', json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $fp = fopen($dataDir . '/students.csv', 'w');
    if ($fp !== false) {
        fputcsv($fp, ['ID', 'Full Name', 'Username', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Role', 'Status', 'Registered At']);
        foreach ($list as $st) {
            fputcsv($fp, [
                $st['id'], $st['fullname'], $st['username'], $st['email'], $st['phone'],
                $st['course'], $st['year'], $st['gender'], $st['role'], $st['status'], $st['registered_at']
            ]);
        }
        fclose($fp);
    }
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    send_json(403, null, 'Administrator authorization required.');
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$parsedInput = json_decode($rawInput, true) ?: [];
$input = array_merge($_GET, $_POST, $parsedInput);

if (isset($input['_method'])) {
    $method = strtoupper($input['_method']);
}

$conn = get_db_connection();
if (!$conn) {
    send_json(500, null, 'Database connection error.');
}

if ($method === 'GET') {
    if (isset($input['id']) && is_numeric($input['id'])) {
        $id = (int)$input['id'];
        $stmt = $conn->prepare("SELECT id, student_id, fullname, username, email, phone, course, year, gender, role, status, created_at FROM students WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($row) {
            send_json(200, $row, 'Student record retrieved.');
        } else {
            send_json(404, null, 'Student not found.');
        }
    }

    $search = trim($input['search'] ?? '');
    $course = trim($input['course'] ?? '');

    $sql = "SELECT id, student_id, fullname, username, email, phone, course, year, gender, role, status, created_at FROM students WHERE 1=1";
    $types = "";
    $params = [];

    if ($course !== '' && $course !== 'All') {
        $sql .= " AND course = ?";
        $types .= "s";
        $params[] = $course;
    }
    if ($search !== '') {
        $sql .= " AND (fullname LIKE ? OR email LIKE ? OR student_id LIKE ? OR username LIKE ?)";
        $types .= "ssss";
        $term = "%{$search}%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= " ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $students = [];
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
    $stmt->close();
    $conn->close();

    send_json(200, $students, 'Students retrieved successfully.');
}

if ($method === 'POST' && (!isset($input['id']) || empty($input['id']))) {
    $fullname = trim($input['fullname'] ?? '');
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? 'Student@123';
    $course = trim($input['course'] ?? 'B.Tech');
    $year = trim($input['year'] ?? 'First Year');
    $gender = trim($input['gender'] ?? 'Male');
    $role = trim($input['role'] ?? 'Student');
    $status = trim($input['status'] ?? 'Active');

    if (empty($fullname) || empty($email)) {
        $conn->close();
        send_json(400, null, 'Full Name and Email are required.');
    }

    $chk = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $chk->bind_param("s", $email);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        $conn->close();
        send_json(400, null, 'Email already registered.');
    }
    $chk->close();

    $studentId = "STU-" . date("Y") . "-" . strtoupper(substr(uniqid(), -5));
    $finalUsername = !empty($username) ? $username : strtolower(explode('@', $email)[0]) . rand(100, 999);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (student_id, fullname, username, email, phone, password_hash, role, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $studentId, $fullname, $finalUsername, $email, $phone, $hash, $role, $course, $year, $gender, $status);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        sync_students_data();
        send_json(201, ['id' => $newId, 'student_id' => $studentId, 'fullname' => $fullname], 'Student registered successfully.');
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        send_json(500, null, 'Failed to add student: ' . $err);
    }
}

if ($method === 'PUT' || ($method === 'POST' && isset($input['id']) && !empty($input['id']))) {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        $conn->close();
        send_json(400, null, 'Valid student record ID required.');
    }

    $fullname = trim($input['fullname'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $course = trim($input['course'] ?? '');
    $year = trim($input['year'] ?? '');
    $gender = trim($input['gender'] ?? 'Male');
    $status = trim($input['status'] ?? 'Active');
    $role = trim($input['role'] ?? 'Student');
    $newPass = trim($input['password'] ?? '');

    if (empty($fullname) || empty($email)) {
        $conn->close();
        send_json(400, null, 'Full Name and Email are mandatory.');
    }

    if (!empty($newPass)) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET fullname = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ?, role = ?, password_hash = ? WHERE id = ?");
        $stmt->bind_param("sssssssssi", $fullname, $email, $phone, $course, $year, $gender, $status, $role, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE students SET fullname = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $fullname, $email, $phone, $course, $year, $gender, $status, $role, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        sync_students_data();
        send_json(200, ['id' => $id, 'fullname' => $fullname], 'Student updated successfully.');
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        send_json(500, null, 'Update failed: ' . $err);
    }
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        $conn->close();
        send_json(400, null, 'Valid student record ID required.');
    }

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
        $conn->close();
        send_json(400, null, 'Cannot delete currently authenticated administrator.');
    }

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();
        sync_students_data();
        if ($affected > 0) {
            send_json(200, ['id' => $id], 'Student record deleted.');
        } else {
            send_json(404, null, 'Student not found.');
        }
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        send_json(500, null, 'Delete failed: ' . $err);
    }
}

$conn->close();
send_json(405, null, 'Method not allowed.');
