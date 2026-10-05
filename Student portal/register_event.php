<?php


require_once __DIR__ . '/db_connect.php';

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$events = db_select("SELECT * FROM events ORDER BY event_date ASC");
$students = db_select("SELECT id, student_id, fullname, email, course FROM students ORDER BY fullname ASC");

$message = "";
$messageType = "";
$registrationDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedStudentId = (int)($_POST['student_id'] ?? 0);
    $selectedEventId   = (int)($_POST['event_id'] ?? 0);
    $remarks           = trim($_POST['remarks'] ?? 'Standard Registration');

    if ($selectedStudentId <= 0 || $selectedEventId <= 0) {
        $message = "Please select both a student and an event.";
        $messageType = "error";
    } else {
        
        $existing = db_select(
            "SELECT id FROM event_registrations WHERE student_id = ? AND event_id = ?",
            "ii",
            [$selectedStudentId, $selectedEventId]
        );

        if (!empty($existing)) {
            $message = "This student is already registered for this event!";
            $messageType = "error";
        } else {
            
            $regCode = "REG-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));

            $insert = db_execute(
                "INSERT INTO event_registrations (registration_code, student_id, event_id, status, remarks) VALUES (?, ?, ?, 'Confirmed', ?)",
                "siis",
                [$regCode, $selectedStudentId, $selectedEventId, $remarks]
            );

            if ($insert['success']) {
                $message = "Event Registration Confirmed!";
                $messageType = "success";

                
                $ticket = db_select(
                    "SELECT r.registration_code, r.registration_date, r.status, r.remarks,
                            s.fullname AS student_name, s.student_id, s.email, s.course,
                            e.name AS event_name, e.category, e.event_date, e.venue
                     FROM event_registrations r
                     JOIN students s ON r.student_id = s.id
                     JOIN events e ON r.event_id = e.id
                     WHERE r.registration_code = ?",
                    "s",
                    [$regCode]
                );

                if (!empty($ticket)) {
                    $registrationDetails = $ticket[0];
                }
            } else {
                $message = "Registration failed: " . ($insert['error'] ?? 'Unknown database error');
                $messageType = "error";
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
    <title>Event Registration - StudentHub</title>
    <link rel="stylesheet" href="events.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .form-card {
            max-width: 650px;
            margin: 35px auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #0d47a1;
            margin-bottom: 8px;
        }
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
        }
        .ticket-box {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            color: #ffffff;
            padding: 25px 30px;
            border-radius: 12px;
            margin: 25px 0;
            box-shadow: 0 8px 25px rgba(13, 71, 161, 0.25);
        }
        .ticket-box h3 {
            color: #ffd54f !important;
            margin-top: 0;
            font-size: 20px;
        }
        .ticket-table {
            width: 100%;
            color: #ffffff;
            font-size: 14px;
            margin-top: 15px;
        }
        .ticket-table td {
            padding: 6px 0;
            border: none;
        }
        .ticket-table td.prop {
            color: #bbdefb;
            width: 40%;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a;
        }
        .btn-submit {
            background: #1565c0;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 15px;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: #0d47a1;
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
                    <a href="index.php">Events</a> |
                    <a href="faq.html">FAQ</a> |
                    <a href="contact.html">Contact</a> |
                    <a href="view_records.php">Records</a>
                </td>
                <td align="right">
                    <a href="login.html">Login</a> |
                    <a href="register.html">Register</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main>
    <div class="form-card">
        <h1 align="center" style="font-size: 28px; margin-bottom: 10px;">Event Registration (MySQL)</h1>
        <p align="center" style="color: #666; margin-bottom: 25px;">
            Link registered students to campus events using MySQL foreign keys.
        </p>

        <?php if (!empty($message) && $messageType === "error"): ?>
            <div class="alert-error">
                ⚠ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($registrationDetails): ?>
            <div class="ticket-box">
                <h3>🎟 Event Pass Confirmed!</h3>
                <p>Registration Code: <strong><?php echo htmlspecialchars($registrationDetails['registration_code']); ?></strong></p>
                
                <table class="ticket-table">
                    <tr>
                        <td class="prop">Student Name:</td>
                        <td><strong><?php echo htmlspecialchars($registrationDetails['student_name']); ?></strong> (<?php echo htmlspecialchars($registrationDetails['student_id']); ?>)</td>
                    </tr>
                    <tr>
                        <td class="prop">Event Name:</td>
                        <td><strong><?php echo htmlspecialchars($registrationDetails['event_name']); ?></strong> (<?php echo htmlspecialchars($registrationDetails['category']); ?>)</td>
                    </tr>
                    <tr>
                        <td class="prop">Date & Venue:</td>
                        <td><?php echo htmlspecialchars($registrationDetails['event_date']); ?> | <?php echo htmlspecialchars($registrationDetails['venue']); ?></td>
                    </tr>
                    <tr>
                        <td class="prop">Status:</td>
                        <td><span style="background:#4caf50;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;"><?php echo htmlspecialchars($registrationDetails['status']); ?></span></td>
                    </tr>
                </table>
            </div>

            <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                <a href="view_records.php" class="page-button active" style="text-decoration:none;">View All Registrations (MySQL)</a>
                <a href="index.php" class="page-button" style="text-decoration:none;">Back to Events</a>
            </div>

        <?php else: ?>
            <form action="register_event.php" method="post">
                
                <div class="form-group">
                    <label for="student_id">Select Registered Student *</label>
                    <select id="student_id" name="student_id" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['fullname']); ?> (<?php echo htmlspecialchars($s['student_id']); ?> - <?php echo htmlspecialchars($s['course']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($students)): ?>
                        <small style="color:#d32f2f;">No students found in MySQL. Please register a student first via <a href="register.html">Registration Page</a>.</small>
                    <?php endif; ?>
                </div>

                
                <div class="form-group">
                    <label for="event_id">Select Event *</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Choose Event --</option>
                        <?php foreach ($events as $ev): ?>
                            <option value="<?php echo $ev['id']; ?>" <?php echo ($ev['id'] == $eventId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ev['name']); ?> (<?php echo htmlspecialchars($ev['category']); ?> - <?php echo htmlspecialchars($ev['event_date']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                
                <div class="form-group">
                    <label for="remarks">Remarks / Participation Role</label>
                    <input type="text" id="remarks" name="remarks" placeholder="e.g. Individual participant, team member">
                </div>

                <button type="submit" class="btn-submit">Confirm Registration (Save to MySQL)</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<hr>

<footer>
    <table width="100%">
        <tr>
            <td><p>2026 StudentHub. All Rights Reserved.</p></td>
            <td align="right">
                <a href="home.html">Home</a> |
                <a href="about.html">About</a> |
                <a href="contact.html">Contact</a>
            </td>
        </tr>
    </table>
</footer>

<script src="studenthub.js"></script>
</body>
</html>
