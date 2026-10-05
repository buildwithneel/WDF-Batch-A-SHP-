<?php
$pageTitle = "Events - StudentHub";
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
                    <a href="contact.html">Contact</a>
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

<div id="event-container"></div>

<p id="no-events">No events found.</p>

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
