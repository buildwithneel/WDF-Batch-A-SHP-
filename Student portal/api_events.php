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

function sync_events_json() {
    $conn = get_db_connection();
    if (!$conn) return;
    $res = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $list = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'date' => $row['event_date'],
                'venue' => $row['venue'],
                'description' => $row['description'],
                'image' => $row['image_url'] ?: 'pic1.png',
                'link' => 'register_event.php?event_id=' . $row['id']
            ];
        }
        $res->free();
    }
    $conn->close();
    $target = __DIR__ . '/data/events.json';
    if (!empty($list) && is_dir(__DIR__ . '/data')) {
        file_put_contents($target, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
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
    send_json(500, null, 'Database connection unavailable.');
}

if ($method === 'GET') {
    if (isset($input['id']) && is_numeric($input['id'])) {
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
        $id = (int)$input['id'];
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($row) {
            send_json(200, $row, 'Event retrieved.');
        } else {
            send_json(404, null, 'Event not found.');
        }
    }

    $category = trim($input['category'] ?? '');
    $search = trim($input['search'] ?? '');

    $sql = "SELECT * FROM events WHERE 1=1";
    $types = "";
    $params = [];

    if ($category !== '' && $category !== 'All') {
        $sql .= " AND category = ?";
        $types .= "s";
        $params[] = $category;
    }
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR description LIKE ? OR venue LIKE ?)";
        $types .= "sss";
        $term = "%{$search}%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= " ORDER BY event_date ASC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($r = $result->fetch_assoc()) {
        $events[] = $r;
    }
    $stmt->close();
    $conn->close();

    send_json(200, $events, 'Events list loaded.');
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    $conn->close();
    send_json(403, null, 'Administrator access required.');
}

if ($method === 'POST' && (!isset($input['id']) || empty($input['id']))) {
    $name = trim($input['name'] ?? '');
    $category = trim($input['category'] ?? 'Technical');
    $date = trim($input['event_date'] ?? $input['date'] ?? '');
    $venue = trim($input['venue'] ?? '');
    $desc = trim($input['description'] ?? '');
    $image = trim($input['image_url'] ?? $input['image'] ?? 'pic1.png');
    $max = (int)($input['max_participants'] ?? 100);
    $status = trim($input['status'] ?? 'Upcoming');

    if (empty($name) || empty($date) || empty($venue)) {
        $conn->close();
        send_json(400, null, 'Name, date, and venue are required.');
    }

    $stmt = $conn->prepare("INSERT INTO events (name, category, event_date, venue, description, image_url, max_participants, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $name, $category, $date, $venue, $desc, $image, $max, $status);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        sync_events_json();
        send_json(201, ['id' => $newId, 'name' => $name], 'Event created successfully.');
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        send_json(500, null, 'Failed to insert event: ' . $err);
    }
}

if ($method === 'PUT' || ($method === 'POST' && isset($input['id']) && !empty($input['id']))) {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        $conn->close();
        send_json(400, null, 'Valid event ID required for update.');
    }

    $name = trim($input['name'] ?? '');
    $category = trim($input['category'] ?? 'Technical');
    $date = trim($input['event_date'] ?? $input['date'] ?? '');
    $venue = trim($input['venue'] ?? '');
    $desc = trim($input['description'] ?? '');
    $image = trim($input['image_url'] ?? $input['image'] ?? 'pic1.png');
    $max = (int)($input['max_participants'] ?? 100);
    $status = trim($input['status'] ?? 'Upcoming');

    if (empty($name) || empty($date) || empty($venue)) {
        $conn->close();
        send_json(400, null, 'Name, date, and venue cannot be empty.');
    }

    $stmt = $conn->prepare("UPDATE events SET name = ?, category = ?, event_date = ?, venue = ?, description = ?, image_url = ?, max_participants = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssssisi", $name, $category, $date, $venue, $desc, $image, $max, $status, $id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        sync_events_json();
        send_json(200, ['id' => $id, 'name' => $name], 'Event updated successfully.');
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
        send_json(400, null, 'Valid event ID required for deletion.');
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();
        sync_events_json();
        if ($affected > 0) {
            send_json(200, ['id' => $id], 'Event deleted successfully.');
        } else {
            send_json(404, null, 'Event not found or already deleted.');
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
