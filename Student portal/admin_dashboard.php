<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/session_helper.php';
require_login('Admin');

$conn = get_db_connection();

$studentCount = 0;
$eventCount = 0;
$regCount = 0;
$contactCount = 0;
$activeStudents = 0;

if ($conn) {
    $res = $conn->query("SELECT COUNT(*) as c FROM students WHERE role = 'Student'");
    if ($res) { $studentCount = (int)$res->fetch_assoc()['c']; $res->free(); }

    $res = $conn->query("SELECT COUNT(*) as c FROM events");
    if ($res) { $eventCount = (int)$res->fetch_assoc()['c']; $res->free(); }

    $res = $conn->query("SELECT COUNT(*) as c FROM event_registrations");
    if ($res) { $regCount = (int)$res->fetch_assoc()['c']; $res->free(); }

    $res = $conn->query("SELECT COUNT(*) as c FROM contacts");
    if ($res) { $contactCount = (int)$res->fetch_assoc()['c']; $res->free(); }

    $res = $conn->query("SELECT COUNT(*) as c FROM students WHERE status = 'Active'");
    if ($res) { $activeStudents = (int)$res->fetch_assoc()['c']; $res->free(); }
}

$latestRegistrations = [];
if ($conn) {
    $sql = "SELECT r.registration_code, r.registration_date, r.status,
                   s.fullname AS student_name, s.student_id AS student_code,
                   e.name AS event_name, e.category, e.event_date
            FROM event_registrations r
            JOIN students s ON r.student_id = s.id
            JOIN events e ON r.event_id = e.id
            ORDER BY r.id DESC LIMIT 6";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $latestRegistrations[] = $r;
        }
        $res->free();
    }
}

$latestStudents = [];
if ($conn) {
    $res = $conn->query("SELECT id, student_id, fullname, email, course, year, status, created_at FROM students ORDER BY id DESC LIMIT 5");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $latestStudents[] = $r;
        }
        $res->free();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudentHub</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0 35px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            border-left: 5px solid #0d47a1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card.purple { border-left-color: #8b5cf6; }
        .stat-card.teal { border-left-color: #06b6d4; }
        .stat-info h3 {
            margin: 0 0 6px 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        .stat-info .num {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .stat-icon {
            font-size: 36px;
            opacity: 0.85;
        }
        .dash-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }
        .dash-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }
        .dash-section-header h2 {
            margin: 0;
            font-size: 20px;
            color: #0d47a1;
        }
        .data-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .data-tbl th, .data-tbl td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .data-tbl th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
        .data-tbl tr:hover {
            background: #f8fbff;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #b45309; }
        .btn-act {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-add {
            background: #0d47a1;
            color: #fff;
            padding: 9px 18px;
            font-size: 14px;
        }
        .btn-add:hover { background: #1565c0; }
        .btn-edit { background: #e0f2fe; color: #0369a1; margin-right: 6px; }
        .btn-edit:hover { background: #bae6fd; }
        .btn-del { background: #fee2e2; color: #b91c1c; }
        .btn-del:hover { background: #fecaca; }
        .ajax-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .ajax-bar input, .ajax-bar select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }
        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal-bg.open { display: flex; }
        .modal-card {
            background: #ffffff;
            width: min(92%, 580px);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-card h3 {
            margin: 0 0 15px 0;
            color: #0d47a1;
            font-size: 20px;
        }
        .modal-field {
            margin-bottom: 14px;
        }
        .modal-field label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 5px;
            color: #334155;
        }
        .modal-field input, .modal-field select, .modal-field textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        .toast-box {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 11000;
            background: #0f172a;
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 14px;
            display: none;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php render_navigation('admin_dashboard'); ?>

<hr>

<main style="max-width:1250px;margin:25px auto;padding:0 20px;">

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
        <div>
            <h1 style="margin:0 0 5px 0;font-size:28px;color:#0d47a1;">Administrator Command Center</h1>
            <p style="margin:0;color:#64748b;font-size:14px;">Real-time portal metrics, student accounts, and asynchronous API management.</p>
        </div>
        <div>
            <a href="manage_students.php" class="btn-act btn-add" style="text-decoration:none;display:inline-block;margin-right:8px;">Manage Students</a>
            <a href="view_records.php" class="btn-act" style="text-decoration:none;background:#475569;color:#fff;padding:9px 16px;font-size:14px;display:inline-block;">Raw Records</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Students</h3>
                <div class="num"><?php echo $studentCount; ?></div>
            </div>
            <div class="stat-icon">&#127891;</div>
        </div>

        <div class="stat-card orange">
            <div class="stat-info">
                <h3>Campus Events</h3>
                <div class="num" id="stat-event-count"><?php echo $eventCount; ?></div>
            </div>
            <div class="stat-icon">&#128197;</div>
        </div>

        <div class="stat-card green">
            <div class="stat-info">
                <h3>Registrations</h3>
                <div class="num"><?php echo $regCount; ?></div>
            </div>
            <div class="stat-icon">&#127919;</div>
        </div>

        <div class="stat-card purple">
            <div class="stat-info">
                <h3>Active Accounts</h3>
                <div class="num"><?php echo $activeStudents; ?></div>
            </div>
            <div class="stat-icon">&#9889;</div>
        </div>

        <div class="stat-card teal">
            <div class="stat-info">
                <h3>Inquiries</h3>
                <div class="num"><?php echo $contactCount; ?></div>
            </div>
            <div class="stat-icon">&#128172;</div>
        </div>
    </div>

    <section class="dash-section">
        <div class="dash-section-header">
            <div>
                <h2>Events Asynchronous CRUD (Fetch API Engine)</h2>
                <small style="color:#64748b;">Live List, Add, Update, and Delete operations using asynchronous JSON REST endpoints with no page reloads.</small>
            </div>
            <button class="btn-act btn-add" id="openAddModalBtn">+ Create New Event</button>
        </div>

        <div class="ajax-bar">
            <input type="text" id="apiSearch" placeholder="Search events in real time..." style="flex-grow:1;min-width:220px;">
            <select id="apiCategory">
                <option value="All">All Categories</option>
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Sports">Sports</option>
                <option value="Cultural">Cultural</option>
                <option value="Seminar">Seminar</option>
            </select>
            <button class="btn-act" id="refreshEventsBtn" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:8px 14px;">&#8635; Refresh</button>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-tbl" id="eventsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Participants</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="eventsTbody">
                    <tr>
                        <td colspan="8" style="text-align:center;padding:25px;color:#64748b;">Loading live events from JSON API...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(480px, 1fr));gap:25px;margin-bottom:30px;">
        <section class="dash-section" style="margin-bottom:0;">
            <div class="dash-section-header">
                <h2>Latest Event Registrations</h2>
                <a href="register_event.php" style="font-size:13px;color:#0d47a1;text-decoration:none;font-weight:600;">Register Student &rarr;</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-tbl">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Student</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($latestRegistrations)): ?>
                            <?php foreach ($latestRegistrations as $lr): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($lr['registration_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($lr['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($lr['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($lr['event_date']); ?></td>
                                <td><span class="badge badge-green"><?php echo htmlspecialchars($lr['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;color:#64748b;">No event registrations yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dash-section" style="margin-bottom:0;">
            <div class="dash-section-header">
                <h2>Recent Student Registrations</h2>
                <a href="manage_students.php" style="font-size:13px;color:#0d47a1;text-decoration:none;font-weight:600;">View All Students &rarr;</a>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-tbl">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($latestStudents)): ?>
                            <?php foreach ($latestStudents as $ls): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($ls['student_id']); ?></code></td>
                                <td><?php echo htmlspecialchars($ls['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($ls['course']); ?></td>
                                <td><?php echo htmlspecialchars($ls['year']); ?></td>
                                <td>
                                    <span class="badge <?php echo $ls['status'] === 'Active' ? 'badge-green' : 'badge-yellow'; ?>">
                                        <?php echo htmlspecialchars($ls['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;color:#64748b;">No student records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</main>

<div class="modal-bg" id="eventModal">
    <div class="modal-card">
        <h3 id="modalTitle">Add New Event</h3>
        <form id="eventForm">
            <input type="hidden" id="eventId" name="id">

            <div class="modal-field">
                <label for="eventName">Event Name *</label>
                <input type="text" id="eventName" required placeholder="e.g. National Hackathon 2026">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="modal-field">
                    <label for="eventCategory">Category *</label>
                    <select id="eventCategory" required>
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Seminar">Seminar</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label for="eventDate">Event Date *</label>
                    <input type="date" id="eventDate" required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="modal-field">
                    <label for="eventVenue">Venue *</label>
                    <input type="text" id="eventVenue" required placeholder="e.g. Seminar Hall A">
                </div>
                <div class="modal-field">
                    <label for="eventMax">Max Participants</label>
                    <input type="number" id="eventMax" value="100" min="10" max="1000">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="modal-field">
                    <label for="eventStatus">Status</label>
                    <select id="eventStatus">
                        <option value="Upcoming">Upcoming</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label for="eventImage">Image URL</label>
                    <input type="text" id="eventImage" placeholder="https://... or pic1.png">
                </div>
            </div>

            <div class="modal-field">
                <label for="eventDesc">Description</label>
                <textarea id="eventDesc" rows="3" placeholder="Provide event overview and schedule details..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="closeModalBtn">Cancel</button>
                <button type="submit" class="btn-act btn-add" id="saveEventBtn">Save Event</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-box" id="toastBox">Notification</div>

<footer>
    <table width="100%">
        <tr>
            <td><p>&copy; 2026 StudentHub. All Rights Reserved.</p></td>
            <td align="right">
                <a href="admin_dashboard.php">Admin Dashboard</a> |
                <a href="manage_students.php">Students</a> |
                <a href="manage_events.php">Events</a> |
                <a href="logout.php">Logout</a>
            </td>
        </tr>
    </table>
</footer>

<script>
let eventsCache = [];

function showToast(msg, isErr = false) {
    const t = document.getElementById("toastBox");
    t.textContent = msg;
    t.style.background = isErr ? "#b91c1c" : "#0f172a";
    t.style.display = "block";
    setTimeout(() => { t.style.display = "none"; }, 3500);
}

async function loadEvents() {
    try {
        const res = await fetch("api_events.php");
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
            eventsCache = json.data;
            renderEventsTable(eventsCache);
            document.getElementById("stat-event-count").textContent = eventsCache.length;
        } else {
            showToast(json.message || "Could not load events", true);
        }
    } catch (err) {
        showToast("Network error communicating with api_events.php", true);
    }
}

function renderEventsTable(list) {
    const tbody = document.getElementById("eventsTbody");
    tbody.innerHTML = "";

    if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:25px;color:#64748b;">No events match your criteria.</td></tr>`;
        return;
    }

    list.forEach(ev => {
        const tr = document.createElement("tr");
        tr.id = `event-row-${ev.id}`;
        tr.innerHTML = `
            <td>#${ev.id}</td>
            <td><strong>${escapeHtml(ev.name)}</strong></td>
            <td><span class="badge badge-blue">${escapeHtml(ev.category)}</span></td>
            <td>${escapeHtml(ev.event_date || ev.date || '')}</td>
            <td>${escapeHtml(ev.venue || '')}</td>
            <td><span class="badge ${ev.status === 'Upcoming' ? 'badge-green' : 'badge-yellow'}">${escapeHtml(ev.status || 'Upcoming')}</span></td>
            <td>${ev.max_participants || 100}</td>
            <td style="text-align:right;white-space:nowrap;">
                <button class="btn-act btn-edit" onclick="openEditModal(${ev.id})">Edit</button>
                <button class="btn-act btn-del" onclick="deleteEvent(${ev.id}, '${escapeHtml(ev.name)}')">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/[&<>"']/g, m => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[m]);
}

function filterEvents() {
    const q = document.getElementById("apiSearch").value.trim().toLowerCase();
    const cat = document.getElementById("apiCategory").value;

    const filtered = eventsCache.filter(ev => {
        const matchesQ = !q ||
            ev.name.toLowerCase().includes(q) ||
            (ev.venue && ev.venue.toLowerCase().includes(q)) ||
            (ev.description && ev.description.toLowerCase().includes(q));

        const matchesCat = (cat === "All") || (ev.category.toLowerCase() === cat.toLowerCase());
        return matchesQ && matchesCat;
    });

    renderEventsTable(filtered);
}

document.getElementById("apiSearch").addEventListener("input", filterEvents);
document.getElementById("apiCategory").addEventListener("change", filterEvents);
document.getElementById("refreshEventsBtn").addEventListener("click", () => {
    loadEvents();
    showToast("Event list refreshed.");
});

const modal = document.getElementById("eventModal");
const form = document.getElementById("eventForm");

document.getElementById("openAddModalBtn").addEventListener("click", () => {
    form.reset();
    document.getElementById("eventId").value = "";
    document.getElementById("modalTitle").textContent = "Create New Event";
    document.getElementById("eventMax").value = "100";
    document.getElementById("eventStatus").value = "Upcoming";
    modal.classList.add("open");
});

document.getElementById("closeModalBtn").addEventListener("click", () => {
    modal.classList.remove("open");
});

modal.addEventListener("click", e => {
    if (e.target === modal) modal.classList.remove("open");
});

async function openEditModal(id) {
    try {
        const res = await fetch(`api_events.php?id=${id}`);
        const json = await res.json();
        if (json.success && json.data) {
            const ev = json.data;
            document.getElementById("eventId").value = ev.id;
            document.getElementById("eventName").value = ev.name || "";
            document.getElementById("eventCategory").value = ev.category || "Technical";
            document.getElementById("eventDate").value = ev.event_date || ev.date || "";
            document.getElementById("eventVenue").value = ev.venue || "";
            document.getElementById("eventMax").value = ev.max_participants || 100;
            document.getElementById("eventStatus").value = ev.status || "Upcoming";
            document.getElementById("eventImage").value = ev.image_url || ev.image || "";
            document.getElementById("eventDesc").value = ev.description || "";
            document.getElementById("modalTitle").textContent = `Edit Event #${ev.id}`;
            modal.classList.add("open");
        } else {
            showToast(json.message || "Failed to load event for editing", true);
        }
    } catch (err) {
        showToast("Error retrieving event data", true);
    }
}

form.addEventListener("submit", async e => {
    e.preventDefault();
    const id = document.getElementById("eventId").value;
    const isEdit = Boolean(id);

    const payload = {
        name: document.getElementById("eventName").value.trim(),
        category: document.getElementById("eventCategory").value,
        event_date: document.getElementById("eventDate").value,
        venue: document.getElementById("eventVenue").value.trim(),
        max_participants: parseInt(document.getElementById("eventMax").value, 10),
        status: document.getElementById("eventStatus").value,
        image_url: document.getElementById("eventImage").value.trim() || "pic1.png",
        description: document.getElementById("eventDesc").value.trim()
    };

    if (isEdit) {
        payload.id = parseInt(id, 10);
    }

    try {
        const res = await fetch("api_events.php", {
            method: isEdit ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (json.success) {
            modal.classList.remove("open");
            showToast(json.message || (isEdit ? "Event updated successfully!" : "Event created successfully!"));
            await loadEvents();
        } else {
            showToast(json.message || "Operation failed", true);
        }
    } catch (err) {
        showToast("Network error submitting event", true);
    }
});

async function deleteEvent(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This cannot be undone.`)) {
        return;
    }

    try {
        const res = await fetch("api_events.php", {
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        });

        const json = await res.json();
        if (json.success) {
            const row = document.getElementById(`event-row-${id}`);
            if (row) {
                row.style.transition = "opacity 0.4s";
                row.style.opacity = "0";
                setTimeout(() => { row.remove(); }, 400);
            }
            showToast(json.message || "Event deleted successfully!");
            eventsCache = eventsCache.filter(e => e.id !== id);
            document.getElementById("stat-event-count").textContent = eventsCache.length;
        } else {
            showToast(json.message || "Failed to delete event", true);
        }
    } catch (err) {
        showToast("Error executing delete request", true);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    loadEvents();
});
</script>
<script src="studenthub.js"></script>
</body>
</html>
