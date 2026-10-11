<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/session_helper.php';
require_login();

$userId = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Student';
$studentCode = $_SESSION['student_id'] ?? '';
$role = $_SESSION['role'] ?? 'Student';
$course = $_SESSION['course'] ?? 'Computer Science';

if (($role ?? '') === 'Admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$regCountResult = db_select("SELECT COUNT(*) as total FROM event_registrations WHERE student_id = ?", "i", [$userId]);
$regCount = !empty($regCountResult) ? (int)$regCountResult[0]['total'] : 0;

$upcomingEvents = db_select("SELECT id, name, category, event_date, venue FROM events WHERE status = 'Upcoming' ORDER BY event_date ASC LIMIT 5");

if (empty($upcomingEvents)) {
    $eventsJsonFile = __DIR__ . '/data/events.json';
    if (file_exists($eventsJsonFile)) {
        $jsonEv = json_decode(file_get_contents($eventsJsonFile), true);
        if (is_array($jsonEv)) {
            foreach ($jsonEv as $je) {
                $upcomingEvents[] = [
                    'id' => $je['id'] ?? 1,
                    'name' => $je['name'] ?? 'Event',
                    'category' => $je['category'] ?? 'Campus',
                    'event_date' => $je['date'] ?? 'Upcoming',
                    'venue' => $je['venue'] ?? 'Campus Ground'
                ];
            }
        }
    }
}

$myRegistrations = db_select("SELECT r.registration_code, r.registration_date, r.status, e.name AS event_name, e.category, e.event_date, e.venue FROM event_registrations r JOIN events e ON r.event_id = e.id WHERE r.student_id = ? ORDER BY r.id DESC LIMIT 5", "i", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - StudentHub</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
</head>
<body>

<?php render_navigation('dashboard'); ?>

<hr>

<main>

    <div class="dash-hero">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h1>
            <p>Your academic records, event participation, and portal services at a glance.</p>
            <div class="dash-hero-badges">
                <span class="dash-hero-badge">&#128100; ID: <?php echo htmlspecialchars($studentCode ?: 'STU-2026-001'); ?></span>
                <span class="dash-hero-badge">&#127891; Course: <?php echo htmlspecialchars($course ?: 'Computer Science'); ?></span>
                <span class="dash-hero-badge">&#9989; Status: Active</span>
            </div>
        </div>
        <div class="dash-hero-actions">
            <a href="profile.php" class="dash-hero-btn">&#128100; My Profile</a>
            <a href="index.php" class="dash-hero-btn outline">&#128197; Browse Events</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-info">
                <h3>Overall Attendance</h3>
                <div class="num">92%</div>
                <div class="subtext">&#9650; Satisfactory Standing</div>
            </div>
            <div class="stat-icon">&#128202;</div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Current CGPA</h3>
                <div class="num">8.75</div>
                <div class="subtext" style="color:#0d47a1;">First Class Distinction</div>
            </div>
            <div class="stat-icon">&#127891;</div>
        </div>

        <div class="stat-card orange">
            <div class="stat-info">
                <h3>Registered Events</h3>
                <div class="num"><?php echo $regCount; ?></div>
                <div class="subtext" style="color:#f59e0b;">Active Participations</div>
            </div>
            <div class="stat-icon">&#127919;</div>
        </div>

        <div class="stat-card purple">
            <div class="stat-info">
                <h3>Earned Certificates</h3>
                <div class="num">8</div>
                <div class="subtext" style="color:#8b5cf6;">Verified Digital Vault</div>
            </div>
            <div class="stat-icon">&#127942;</div>
        </div>
    </div>

    <section class="dash-section">
        <div class="dash-section-header">
            <h2>Quick Access Command Hub</h2>
            <small style="color:#64748b;">Instant shortcuts to portal features and external academic resources</small>
        </div>

        <div class="quick-grid">
            <a href="profile.php" class="quick-card">
                <img src="https://imgcdn.stablediffusionweb.com/2026/1/11/6a9ad8cb-95fc-4745-a6f7-4df7e62ea319.webp" alt="Profile">
                <h4>My Profile</h4>
                <p>View personal info, enrolled course, and contacts</p>
            </a>

            <a href="https://drive.google.com/drive/u/0/folders/1Xh1do82L8XDSJfcEMBE-_5za6_lQRd_X" target="_blank" class="quick-card">
                <img src="https://images.shiksha.com/mediadata/images/articles/1588431142phpDzh1pi.jpeg" alt="Results">
                <h4>Exam Results</h4>
                <p>Semester scorecards and transcripts archive</p>
            </a>

            <a href="dashboard.php" class="quick-card">
                <img src="https://image.slidesdocs.com/responsive-images/sheets/class-attendance-sheet-excel-template_f933879c18__max.jpg" alt="Attendance">
                <h4>Attendance</h4>
                <p>Subject-wise breakdown and attendance percentage</p>
            </a>

            <a href="index.php" class="quick-card">
                <img src="https://blog.bham.ac.uk/lpip/wp-content/uploads/sites/140/2025/05/Hackathon.png" alt="Events">
                <h4>Campus Events</h4>
                <p>Browse competitions, workshops, and hackathons</p>
            </a>

            <a href="https://drive.google.com/drive/u/0/folders/1e5vquSi3LQW1UIZ-Pspo96eYC5PqRRc7" target="_blank" class="quick-card">
                <img src="https://static.vecteezy.com/system/resources/previews/008/089/253/original/certificate-gold-appreciation-achievement-template-award-achievement-clean-creative-certificate-recognition-excellence-certificate-border-completion-template-certificate-design-template-vector.jpg" alt="Certificates">
                <h4>Certificates</h4>
                <p>Download verifiable participation awards</p>
            </a>

            <a href="dashboard.php" class="quick-card">
                <img src="https://burhanicollege.edu.in/wp-content/uploads/2026/05/FY-Degree-College-Admission-Notice-2026-27_page-0001.jpg" alt="Notifications">
                <h4>Circulars</h4>
                <p>Official announcements and faculty bulletins</p>
            </a>

            <a href="feedback.html" class="quick-card">
                <img src="https://marketplace.canva.com/EAGEvL3dle0/4/0/1131w/canva-pink-and-beige-professional-feedback-form-a4-document-1lmWcAiccjk.jpg" alt="Feedback">
                <h4>Feedback</h4>
                <p>Share feedback on curriculum and campus facilities</p>
            </a>

            <a href="contact.html" class="quick-card">
                <img src="https://static.vecteezy.com/system/resources/previews/000/589/856/original/vector-setting-icon.jpg" alt="Helpdesk">
                <h4>Helpdesk</h4>
                <p>Reach out to administrators and counselors</p>
            </a>
        </div>
    </section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 340px), 1fr));gap:25px;margin-bottom:30px;">
        <section class="dash-section" style="margin-bottom:0;">
            <div class="dash-section-header">
                <h2>Upcoming Campus Events</h2>
                <a href="index.php" style="font-size:13px;color:#0d47a1;text-decoration:none;font-weight:600;">View All &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="data-tbl">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($upcomingEvents)): ?>
                            <?php foreach ($upcomingEvents as $ev): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ev['name']); ?></strong></td>
                                <td><span class="badge badge-blue"><?php echo htmlspecialchars($ev['category'] ?? 'Campus'); ?></span></td>
                                <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                <td><a href="register_event.php?event_id=<?php echo (int)($ev['id'] ?? 1); ?>" class="btn-sm">Register</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:#64748b;">No upcoming events at this moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dash-section" style="margin-bottom:0;">
            <div class="dash-section-header">
                <h2>Campus Announcements</h2>
                <span class="badge badge-green">Live Feed</span>
            </div>

            <ul class="announcement-list">
                <li class="announcement-item">
                    <span class="announcement-badge">20 Aug 2026</span>
                    <div class="announcement-text">
                        <strong>Semester End Examinations:</strong> Final examination timetables and hall tickets will be issued next Monday.
                    </div>
                </li>
                <li class="announcement-item">
                    <span class="announcement-badge">15 Aug 2026</span>
                    <div class="announcement-text">
                        <strong>Hackathon 2026:</strong> Registrations are live in the events portal. Teams can register up to 4 members.
                    </div>
                </li>
                <li class="announcement-item">
                    <span class="announcement-badge">22 Aug 2026</span>
                    <div class="announcement-text">
                        <strong>AI & Deep Learning Workshop:</strong> Hands-on session at Computer Lab 1. Limited seats available.
                    </div>
                </li>
                <li class="announcement-item">
                    <span class="announcement-badge">28 Aug 2026</span>
                    <div class="announcement-text">
                        <strong>Campus Placement Drive:</strong> TCS and Infosys recruiting for batch 2026. Register via placement portal.
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <?php if (!empty($myRegistrations)): ?>
    <section class="dash-section">
        <div class="dash-section-header">
            <h2>My Event Registrations</h2>
            <span class="badge badge-purple"><?php echo count($myRegistrations); ?> Registered</span>
        </div>

        <div class="table-responsive">
            <table class="data-tbl">
                <thead>
                    <tr>
                        <th>Pass Code</th>
                        <th>Event Name</th>
                        <th>Category</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myRegistrations as $mr): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($mr['registration_code']); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($mr['event_name']); ?></strong></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($mr['category']); ?></span></td>
                        <td><?php echo htmlspecialchars($mr['event_date']); ?></td>
                        <td><?php echo htmlspecialchars($mr['venue']); ?></td>
                        <td><?php echo htmlspecialchars($mr['registration_date']); ?></td>
                        <td><span class="badge badge-green"><?php echo htmlspecialchars($mr['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

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
