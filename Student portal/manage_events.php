<?php
require_once __DIR__ . '/session_helper.php';
require_login('Admin');

$message = "";
$messageType = "";

$conn = get_db_connection();

$uploadDir = __DIR__ . '/uploads/events/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function handle_poster_upload($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }

    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'Poster image size exceeds maximum allowed limit of 5MB.'];
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/pjpeg'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Invalid file extension. Only JPG, JPEG, PNG, and WEBP images are allowed.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image MIME type: ' . $mime];
    }

    $newFilename = 'event_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = __DIR__ . '/uploads/events/' . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/events/' . $newFilename];
    } else {
        return ['success' => false, 'error' => 'Failed to save uploaded poster to server directory.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? 'Technical');
        $event_date  = trim($_POST['event_date'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_p       = (int)($_POST['max_participants'] ?? 100);
        $status      = trim($_POST['status'] ?? 'Upcoming');

        if (empty($name) || empty($event_date) || empty($venue) || empty($description)) {
            $message = "Event Name, Date, Venue, and Description are required.";
            $messageType = "error";
        } else {
            $uploadResult = handle_poster_upload($_FILES['poster'] ?? null);
            if (!$uploadResult['success']) {
                $message = $uploadResult['error'];
                $messageType = "error";
            } else {
                $imageUrl = $uploadResult['path'] ?? 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30';
                $stmt = $conn->prepare("INSERT INTO events (name, category, event_date, venue, description, image_url, max_participants, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssis", $name, $category, $event_date, $venue, $description, $imageUrl, $max_p, $status);
                if ($stmt->execute()) {
                    $message = "Event '{$name}' created successfully!";
                    $messageType = "success";
                } else {
                    $message = "Database error adding event: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'edit') {
        $id          = (int)($_POST['event_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? 'Technical');
        $event_date  = trim($_POST['event_date'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $max_p       = (int)($_POST['max_participants'] ?? 100);
        $status      = trim($_POST['status'] ?? 'Upcoming');

        if ($id <= 0 || empty($name) || empty($event_date) || empty($venue)) {
            $message = "Valid Event ID, Name, Date, and Venue are required.";
            $messageType = "error";
        } else {
            $uploadResult = handle_poster_upload($_FILES['poster'] ?? null);
            if (!$uploadResult['success']) {
                $message = $uploadResult['error'];
                $messageType = "error";
            } else {
                if (!empty($uploadResult['path'])) {
                    $stmt = $conn->prepare("UPDATE events SET name = ?, category = ?, event_date = ?, venue = ?, description = ?, image_url = ?, max_participants = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("ssssssisi", $name, $category, $event_date, $venue, $description, $uploadResult['path'], $max_p, $status, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE events SET name = ?, category = ?, event_date = ?, venue = ?, description = ?, max_participants = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssssisi", $name, $category, $event_date, $venue, $description, $max_p, $status, $id);
                }

                if ($stmt->execute()) {
                    $message = "Event '{$name}' updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to update event: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['event_id'] ?? 0);
        if ($id > 0) {
            $imgStmt = $conn->prepare("SELECT image_url FROM events WHERE id = ?");
            $imgStmt->bind_param("i", $id);
            $imgStmt->execute();
            $imgRes = $imgStmt->get_result();
            if ($row = $imgRes->fetch_assoc()) {
                if (!empty($row['image_url']) && strpos($row['image_url'], 'uploads/events/') === 0) {
                    $localPath = __DIR__ . '/' . $row['image_url'];
                    if (file_exists($localPath)) {
                        unlink($localPath);
                    }
                }
            }
            $imgStmt->close();

            $del = $conn->prepare("DELETE FROM events WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                $message = "Event deleted successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to delete event: " . $del->error;
                $messageType = "error";
            }
            $del->close();
        }
    }
}

$events = [];
$res = $conn->query("
    SELECT e.*, COUNT(r.id) AS registration_count
    FROM events e
    LEFT JOIN event_registrations r ON e.id = r.event_id
    GROUP BY e.id
    ORDER BY e.event_date ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - StudentHub</title>
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
            vertical-align: middle;
        }
        .data-table th {
            background: #004aad;
            color: #ffffff;
            font-weight: 600;
        }
        .data-table tr:hover {
            background: #f8fafc;
        }
        .poster-thumb {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .status-pill {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-Upcoming { background: #dbeafe; color: #1e40af; }
        .status-Ongoing { background: #dcfce7; color: #15803d; }
        .status-Completed { background: #f3f4f6; color: #4b5563; }
        .status-Cancelled { background: #fee2e2; color: #b91c1c; }
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
            max-width: 580px;
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
        .form-row input, .form-row select, .form-row textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
    </style>
</head>
<body>

<?php render_navigation('manage_events'); ?>

<hr>

<main class="admin-container">

    <div class="admin-header-bar">
        <h1>Event Management Module</h1>
        <button class="btn-add" onclick="openAddModal()">+ Add New Event</button>
    </div>

    <?php if (!empty($message)): ?>
    <div style="padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:14px;background:<?php echo $messageType === 'success' ? '#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;' : '#ffebee;color:#c62828;border:1px solid #ef9a9a;'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Poster</th>
                <th>Event Name</th>
                <th>Category</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Seats</th>
                <th>Registrations</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <?php if (!empty($ev['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($ev['image_url']); ?>" alt="Poster" class="poster-thumb">
                        <?php else: ?>
                            <span style="color:#999;font-size:11px;">No Poster</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($ev['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($ev['category']); ?></td>
                    <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                    <td><?php echo $ev['max_participants']; ?></td>
                    <td><strong><?php echo $ev['registration_count']; ?></strong></td>
                    <td><span class="status-pill status-<?php echo htmlspecialchars($ev['status']); ?>"><?php echo htmlspecialchars($ev['status']); ?></span></td>
                    <td style="white-space:nowrap;">
                        <button class="btn-action-edit" onclick='openEditModal(<?php echo json_encode($ev); ?>)'>Edit</button>
                        <form method="post" action="manage_events.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                            <button type="submit" class="btn-action-del">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" align="center" style="padding:30px;color:#777;">No events found in database.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</main>

<div id="addModal" class="modal-form">
    <div class="modal-card">
        <h3>Add New Event</h3>
        <form method="post" action="manage_events.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>Event Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-row">
                <label>Category *</label>
                <select name="category" required>
                    <option value="Technical">Technical</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Sports">Sports</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Seminar">Seminar</option>
                </select>
            </div>
            <div class="form-row">
                <label>Event Date *</label>
                <input type="date" name="event_date" required>
            </div>
            <div class="form-row">
                <label>Venue *</label>
                <input type="text" name="venue" required>
            </div>
            <div class="form-row">
                <label>Description *</label>
                <textarea name="description" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <label>Max Participants Capacity</label>
                <input type="number" name="max_participants" value="100" min="10" max="1000">
            </div>
            <div class="form-row">
                <label>Event Poster Upload (Max 5MB - JPG, PNG, WEBP)</label>
                <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-row">
                <label>Status</label>
                <select name="status">
                    <option value="Upcoming">Upcoming</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div style="margin-top:20px;text-align:right;">
                <button type="button" onclick="closeModal('addModal')" style="padding:8px 16px;margin-right:10px;border:1px solid #ccc;background:#f5f5f5;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-add">Save Event</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal-form">
    <div class="modal-card">
        <h3>Edit Event</h3>
        <form method="post" action="manage_events.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="event_id" id="edit_id">
            <div class="form-row">
                <label>Event Name *</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="form-row">
                <label>Category *</label>
                <select name="category" id="edit_category" required>
                    <option value="Technical">Technical</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Sports">Sports</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Seminar">Seminar</option>
                </select>
            </div>
            <div class="form-row">
                <label>Event Date *</label>
                <input type="date" name="event_date" id="edit_date" required>
            </div>
            <div class="form-row">
                <label>Venue *</label>
                <input type="text" name="venue" id="edit_venue" required>
            </div>
            <div class="form-row">
                <label>Description *</label>
                <textarea name="description" id="edit_description" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <label>Max Participants Capacity</label>
                <input type="number" name="max_participants" id="edit_max_participants">
            </div>
            <div class="form-row">
                <label>Upload New Poster (optional - Max 5MB)</label>
                <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-row">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="Upcoming">Upcoming</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div style="margin-top:20px;text-align:right;">
                <button type="button" onclick="closeModal('editModal')" style="padding:8px 16px;margin-right:10px;border:1px solid #ccc;background:#f5f5f5;border-radius:6px;cursor:pointer;">Cancel</button>
                <button type="submit" class="btn-add">Update Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}
function openEditModal(ev) {
    document.getElementById('edit_id').value = ev.id;
    document.getElementById('edit_name').value = ev.name;
    document.getElementById('edit_category').value = ev.category;
    document.getElementById('edit_date').value = ev.event_date;
    document.getElementById('edit_venue').value = ev.venue;
    document.getElementById('edit_description').value = ev.description;
    document.getElementById('edit_max_participants').value = ev.max_participants;
    document.getElementById('edit_status').value = ev.status;
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
<script src="studenthub.js"></script>
</body>
</html>
