<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub_db');
define('DB_PORT', 3306);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_errno) {
    if ($conn->connect_errno === 1049) {
        $rootConn = new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);
        if (!$rootConn->connect_errno) {
            $rootConn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $rootConn->close();
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        }
    }
}

if (!$conn->connect_errno) {
    $conn->set_charset("utf8mb4");
}

function get_db_connection() {
    $c = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($c && !$c->connect_errno) {
        $c->set_charset("utf8mb4");
        return $c;
    }
    return null;
}

$db = ($conn && !$conn->connect_errno) ? $conn : null;

function db_execute($sql, $types = "", $params = []) {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'error' => 'Database connection failed.'];
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = $conn->error;
        $conn->close();
        return ['success' => false, 'error' => $error];
    }

    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();
    if (!$result) {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $error];
    }

    $insertId = $stmt->insert_id;
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    return [
        'success' => true,
        'insert_id' => $insertId,
        'affected_rows' => $affectedRows
    ];
}

function db_select($sql, $types = "", $params = []) {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        return [];
    }

    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    $conn->close();

    return $rows;
}
?>
