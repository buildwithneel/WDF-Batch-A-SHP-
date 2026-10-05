<?php
require_once __DIR__ . '/session_helper.php';
require_login();

$userId = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Student';
$studentCode = $_SESSION['student_id'] ?? '';
$role = $_SESSION['role'] ?? 'Student';
$course = $_SESSION['course'] ?? '';

$regCountResult = db_select("SELECT COUNT(*) as total FROM event_registrations WHERE student_id = ?", "i", [$userId]);
$regCount = !empty($regCountResult) ? (int)$regCountResult[0]['total'] : 0;

$upcomingEvents = db_select("SELECT name, event_date, venue FROM events WHERE status = 'Upcoming' ORDER BY event_date ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
    <title>Dashboard - StudentHub</title>
</head>
<body>

<header>
    <nav>
        <table width="100%">
            <tr>
                <td><h2>StudentHub</h2></td>
                <td align="center">
                    <a href="dashboard.php">Dashboard</a> |
                    <a href="profile.html">Profile</a> |
                    <a href="events.html">Events</a> |
                    <a href="register_event.php">Register Event</a> |
                    <a href="feedback.html">Feedback</a> |
                    <a href="contact.html">Contact</a>
                    <?php if ($role === 'Admin'): ?>
                        | <a href="manage_students.php" style="color:#d97706;font-weight:600;">Manage Students</a>
                        | <a href="manage_events.php" style="color:#0284c7;font-weight:600;">Manage Events</a>
                    <?php endif; ?>
                </td>
                <td align="right">
                    <span style="font-size:13px;margin-right:10px;color:#555;"><?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($role); ?>)</span>
                    <a href="logout.php" style="color:#c62828;font-weight:600;">Logout</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main>

<h1 align="center"><?php echo ($role === 'Admin') ? 'Administrator Dashboard' : 'Student Dashboard'; ?></h1>

<p align="center">
Welcome, <b><?php echo htmlspecialchars($fullname); ?></b> | ID: <b><?php echo htmlspecialchars($studentCode); ?></b> <?php if (!empty($course)) echo "| Course: <b>" . htmlspecialchars($course) . "</b>"; ?>
</p>

<hr>

<table border="1" width="100%" cellpadding="10">

<tr align="center">
    <th>Attendance</th>
    <th>CGPA</th>
    <th>Registered Events</th>
    <th>Certificates</th>
</tr>

<tr align="center">
    <td>92%</td>
    <td>8.75</td>
    <td><?php echo $regCount; ?></td>
    <td>8</td>
</tr>

</table>

<hr>

<h2>Quick Access</h2>

<table width="100%" cellpadding="15">

<tr align="center">

<td>
<img src="https://imgcdn.stablediffusionweb.com/2026/1/11/6a9ad8cb-95fc-4745-a6f7-4df7e62ea319.webp" alt="Profile" width="80" height="80"><br><br>
<a href="profile.html">Profile</a>
</td>

<td>
<img src="https://images.shiksha.com/mediadata/images/articles/1588431142phpDzh1pi.jpeg" alt="Results" width="80" height="80"><br><br>
<a href="https://drive.google.com/drive/u/0/folders/1Xh1do82L8XDSJfcEMBE-_5za6_lQRd_X" target="_blank">Results</a>
</td>

<td>
<img src="https://image.slidesdocs.com/responsive-images/sheets/class-attendance-sheet-excel-template_f933879c18__max.jpg" alt="Attendance" width="80" height="80"><br><br>
<a href="dashboard.php">Attendance</a>
</td>

<td>
<img src="https://blog.bham.ac.uk/lpip/wp-content/uploads/sites/140/2025/05/Hackathon.png" alt="Events" width="80" height="80"><br><br>
<a href="events.html">Events</a>
</td>

</tr>

<tr align="center">

<td>
<img src="https://static.vecteezy.com/system/resources/previews/008/089/253/original/certificate-gold-appreciation-achievement-template-award-achievement-clean-creative-certificate-recognition-excellence-certificate-border-completion-template-certificate-design-template-vector.jpg" alt="Certificates" width="80" height="80"><br><br>
<a href="https://drive.google.com/drive/u/0/folders/1e5vquSi3LQW1UIZ-Pspo96eYC5PqRRc7" target="_blank">Certificates</a>
</td>

<td>
<img src="https://burhanicollege.edu.in/wp-content/uploads/2026/05/FY-Degree-College-Admission-Notice-2026-27_page-0001.jpg" alt="Notifications" width="80" height="80"><br><br>
<a href="dashboard.php">Notifications</a>
</td>

<td>
<img src="https://marketplace.canva.com/EAGEvL3dle0/4/0/1131w/canva-pink-and-beige-professional-feedback-form-a4-document-1lmWcAiccjk.jpg" alt="Feedback" width="80" height="80"><br><br>
<a href="feedback.html">Feedback</a>
</td>

<td>
<img src="https://static.vecteezy.com/system/resources/previews/000/589/856/original/vector-setting-icon.jpg" alt="Settings" width="80" height="80"><br><br>
<a href="dashboard.php">Settings</a>
</td>

</tr>

</table>

<hr>

<h2>Recent Announcements</h2>

<ul>
    <li>Semester Examination starts from <b>20 August 2026</b>.</li>
    <li>Hackathon registrations are now open in Events portal.</li>
    <li>Workshop on Artificial Intelligence on <b>22 August 2026</b>.</li>
    <li>Placement Drive by TCS next week.</li>
</ul>

<hr>

<h2>Upcoming Events</h2>

<table border="1" width="100%" cellpadding="10">

<tr>
    <th>Event</th>
    <th>Date</th>
    <th>Venue</th>
</tr>

<?php if (!empty($upcomingEvents)): ?>
    <?php foreach ($upcomingEvents as $ev): ?>
    <tr>
        <td><?php echo htmlspecialchars($ev['name']); ?></td>
        <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
        <td><?php echo htmlspecialchars($ev['venue']); ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td>Hackathon 2026</td>
        <td>2026-08-15</td>
        <td>Seminar Hall</td>
    </tr>
<?php endif; ?>

</table>

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
<a href="events.html">Events</a> |
<a href="contact.html">Contact</a>
</td>

</tr>

</table>

</footer>
<script src="studenthub.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
