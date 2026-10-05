<?php


require_once __DIR__ . '/db_connect.php';

$dataDir = __DIR__ . '/data';
$studentsJsonFile = $dataDir . '/students.json';
$contactsJsonFile = $dataDir . '/contacts.json';
$studentsCsvFile  = $dataDir . '/students.csv';
$contactsCsvFile  = $dataDir . '/contacts.csv';


$mysqlConnected = ($db !== null);
$mysqlError = ($db === null) ? "Unable to connect to MySQL. Ensure MySQL is started in XAMPP." : "";


$dbStudents = [];
if ($mysqlConnected) {
    $dbStudents = db_select("SELECT id, student_id, fullname, email, phone, course, year, gender, created_at FROM students ORDER BY id DESC");
}


$dbEvents = [];
if ($mysqlConnected) {
    $dbEvents = db_select("SELECT * FROM events ORDER BY event_date ASC");
}


$dbRegistrations = [];
if ($mysqlConnected) {
    $dbRegistrations = db_select("
        SELECT r.id, r.registration_code, r.registration_date, r.status, r.remarks,
               s.fullname AS student_name, s.student_id AS student_code, s.course,
               e.name AS event_name, e.category, e.event_date, e.venue
        FROM event_registrations r
        JOIN students s ON r.student_id = s.id
        JOIN events e ON r.event_id = e.id
        ORDER BY r.id DESC
    ");
}


$jsonStudents = file_exists($studentsJsonFile) ? json_decode(file_get_contents($studentsJsonFile), true) : [];
$jsonContacts = file_exists($contactsJsonFile) ? json_decode(file_get_contents($contactsJsonFile), true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Records (MySQL, JSON, CSV) - StudentHub</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .container {
            width: 94%;
            max-width: 1250px;
            margin: 30px auto;
        }
        .status-bar {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 600;
        }
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-green { background: #22c55e; box-shadow: 0 0 10px #22c55e; }
        .dot-red { background: #ef4444; box-shadow: 0 0 10px #ef4444; }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 35px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f4f8;
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h2 {
            margin: 0;
            color: #0d47a1;
            font-size: 22px;
        }
        .badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .data-table th {
            background: #1565c0;
            color: #ffffff;
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
        }
        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .data-table tr:hover {
            background: #f1f5f9;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-style: italic;
        }
        .btn-action {
            background: #0d47a1;
            color: #ffffff !important;
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-action:hover {
            background: #1565c0;
        }
        .tag-status {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .tag-confirmed { background: #dcfce7; color: #15803d; }
        .tag-pending { background: #fef9c3; color: #854d0e; }
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
                    <a href="view_records.php"><strong>Records</strong></a>
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

<main class="container">
    
    <div class="status-bar">
        <div class="status-indicator">
            <span class="dot <?php echo $mysqlConnected ? 'dot-green' : 'dot-red'; ?>"></span>
            <span>MySQL Status: 
                <?php if ($mysqlConnected): ?>
                    <strong style="color:#16a34a;">Connected to `studenthub_db`</strong> (Host: localhost:3306)
                <?php else: ?>
                    <strong style="color:#dc2626;">Disconnected</strong> (Start MySQL in XAMPP)
                <?php endif; ?>
            </span>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="setup_database.php" class="btn-action" style="background:#0284c7;">⚡ Run Setup / Seed SQL</a>
            <a href="register_event.php" class="btn-action" style="background:#16a34a;">🎟 Register for Event</a>
            <a href="register.html" class="btn-action">👤 New Student</a>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Event Registrations Table (`event_registrations`) <span class="badge"><?php echo count($dbRegistrations); ?> Registrations</span></h2>
                <small style="color: #64748b;">Relational Junction Table linking <code>students</code> & <code>events</code> with Foreign Keys</small>
            </div>
            <div>
                <a href="register_event.php" class="btn-action">+ New Registration</a>
            </div>
        </div>

        <?php if (!empty($dbRegistrations)): ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reg Code</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Event Name</th>
                            <th>Category</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbRegistrations as $reg): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($reg['registration_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reg['student_name']); ?></td>
                                <td><code><?php echo htmlspecialchars($reg['student_code']); ?></code></td>
                                <td><span class="event-category"><?php echo htmlspecialchars($reg['course']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($reg['event_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reg['category']); ?></td>
                                <td><?php echo htmlspecialchars($reg['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($reg['venue']); ?></td>
                                <td>
                                    <span class="tag-status <?php echo ($reg['status'] === 'Confirmed') ? 'tag-confirmed' : 'tag-pending'; ?>">
                                        <?php echo htmlspecialchars($reg['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($reg['registration_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No event registrations recorded in MySQL yet. <a href="register_event.php">Click here to register a student for an event!</a></p>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Students Table (`students`) <span class="badge"><?php echo count($dbStudents); ?> Registered</span></h2>
                <small style="color: #64748b;">MySQL Source: <code>students</code> | Exports: <code>data/students.json</code>, <code>data/students.csv</code></small>
            </div>
            <div style="display:flex;gap:8px;">
                <?php if (file_exists($studentsCsvFile)): ?>
                    <a href="data/students.csv" class="btn-action" style="background:#059669;" download>📥 CSV</a>
                <?php endif; ?>
                <?php if (file_exists($studentsJsonFile)): ?>
                    <a href="data/students.json" class="btn-action" style="background:#d97706;" download>📄 JSON</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($dbStudents)): ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Code</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbStudents as $s): ?>
                            <tr>
                                <td><?php echo $s['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($s['student_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['phone']); ?></td>
                                <td><span class="event-category"><?php echo htmlspecialchars($s['course']); ?></span></td>
                                <td><?php echo htmlspecialchars($s['year']); ?></td>
                                <td><?php echo htmlspecialchars($s['gender']); ?></td>
                                <td><?php echo htmlspecialchars($s['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No students found in MySQL. <a href="register.html">Register a new student</a> or click "Run Setup / Seed SQL" above.</p>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Events Table (`events`) <span class="badge"><?php echo count($dbEvents); ?> Campus Events</span></h2>
                <small style="color: #64748b;">MySQL Source: <code>events</code></small>
            </div>
            <div>
                <a href="index.php" class="btn-action">Browse Events Portal</a>
            </div>
        </div>

        <?php if (!empty($dbEvents)): ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Description</th>
                            <th>Max Capacity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbEvents as $ev): ?>
                            <tr>
                                <td><?php echo $ev['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($ev['name']); ?></strong></td>
                                <td><span class="event-category"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                <td><?php echo htmlspecialchars(substr($ev['description'], 0, 80)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($ev['max_participants']); ?></td>
                                <td>
                                    <a href="register_event.php?event_id=<?php echo $ev['id']; ?>" class="btn-action" style="padding:4px 10px;font-size:12px;">Book Pass</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No events found. Run <a href="setup_database.php">setup_database.php</a> to populate seed events.</p>
            </div>
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
                <a href="contact.html">Contact</a> |
                <a href="register.html">Register</a>
            </td>
        </tr>
    </table>
</footer>

<script src="studenthub.js"></script>
</body>
</html>
