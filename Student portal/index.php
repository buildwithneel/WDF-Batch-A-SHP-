<?php
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/db_connect.php';
$pageTitle = "Events - StudentHub";

$events = db_select("SELECT * FROM events ORDER BY event_date ASC");
if (empty($events)) {
    $jsonFile = __DIR__ . '/data/events.json';
    if (file_exists($jsonFile)) {
        $events = json_decode(file_get_contents($jsonFile), true) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="events.css">
    <link rel="stylesheet" href="common.css">
    <title><?php echo $pageTitle; ?></title>
</head>
<body>

<?php render_navigation('events'); ?>

<hr>

<main>

<section align="center">
    <h1>College Events</h1>
    <img src="https://media.assettype.com/freepressjournal/2025-10-18/g9aswlzy/34.png?width=1200" alt="Events Banner" width="700" height="250">
</section>

<hr>

<section>

<h2>Search and Filter Events</h2>

<form id="search-form">
    <label for="search">Search:</label>
    <input type="text" id="search" placeholder="Search events...">

    <label for="category">Category:</label>
    <select id="category">
        <option value="All">All Events</option>
        <option value="Technical">Technical</option>
        <option value="Cultural">Cultural</option>
        <option value="Sports">Sports</option>
        <option value="Workshop">Workshop</option>
        <option value="Seminar">Seminar</option>
    </select>

    <label for="sort">Sort:</label>
    <select id="sort">
        <option value="default">Default</option>
        <option value="name-asc">Name A-Z</option>
        <option value="name-desc">Name Z-A</option>
        <option value="date-asc">Date Earliest</option>
        <option value="date-desc">Date Latest</option>
    </select>

    <button type="button" id="reset">Reset</button>
</form>

</section>

<hr>

<section>

<h2 align="center">Upcoming Events</h2>

<div id="event-container">
<?php if (!empty($events)): ?>
    <?php foreach (array_slice($events, 0, 3) as $ev): ?>
    <div class="event-card">
        <div class="event-card-img-wrap">
            <img src="<?php echo htmlspecialchars($ev['image_url'] ?? $ev['image'] ?? 'pic1.png'); ?>" alt="<?php echo htmlspecialchars($ev['name']); ?>">
            <span class="event-card-category"><?php echo htmlspecialchars($ev['category']); ?></span>
        </div>
        <div class="event-card-body">
            <h3 class="event-card-title"><?php echo htmlspecialchars($ev['name']); ?></h3>
            <div class="event-card-meta">
                <span>📅 <b>Date:</b> <?php echo htmlspecialchars($ev['event_date'] ?? $ev['date'] ?? 'TBA'); ?></span>
                <span>📍 <b>Venue:</b> <?php echo htmlspecialchars($ev['venue'] ?? 'Campus'); ?></span>
            </div>
            <p class="event-card-desc"><?php echo htmlspecialchars($ev['description']); ?></p>
            <div class="event-card-action">
                <a href="register_event.php?event_id=<?php echo (int)$ev['id']; ?>" class="event-register-btn">Register for Event</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<p id="no-events" style="<?php echo empty($events) ? 'display:block;' : 'display:none;'; ?>">No events found.</p>

<div id="pagination"></div>

</section>

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
<a href="about.html">About</a> |
<a href="contact.html">Contact</a>
</td>
</tr>
</table>

</footer>
<script src="studenthub.js"></script>
<script src="events.js"></script>
</body>
</html>
