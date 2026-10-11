<?php
require_once __DIR__ . '/db_connect.php';

function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function check_session_timeout($timeoutSeconds = 1800) {
    init_session();
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutSeconds)) {
            session_unset();
            session_destroy();
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
            header("Location: login.php?error=Session timed out. Please login again.");
            exit();
        }
        $_SESSION['last_activity'] = time();
    }
}

function check_remember_me() {
    init_session();
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        if (!empty($token)) {
            $user = db_select("SELECT * FROM students WHERE remember_token = ? AND status = 'Active' LIMIT 1", "s", [$token]);
            if (!empty($user)) {
                $u = $user[0];
                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['student_id'] = $u['student_id'];
                $_SESSION['fullname'] = $u['fullname'];
                $_SESSION['username'] = $u['username'] ?? '';
                $_SESSION['email'] = $u['email'];
                $_SESSION['role'] = $u['role'] ?? 'Student';
                $_SESSION['last_activity'] = time();
                return true;
            } else {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
    }
    return isset($_SESSION['user_id']);
}

function require_login($requiredRole = null) {
    init_session();
    check_remember_me();
    check_session_timeout();

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=Please login to access the portal.");
        exit();
    }

    if ($requiredRole !== null && ($_SESSION['role'] ?? '') !== $requiredRole) {
        if (($_SESSION['role'] ?? '') === 'Admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}

function render_navigation($activePage = '') {
    init_session();
    check_remember_me();
    $role = $_SESSION['role'] ?? null;
    $name = $_SESSION['fullname'] ?? '';

    echo '<header>';
    echo '<nav>';
    echo '<table width="100%">';
    echo '<tr>';
    echo '<td><h2>StudentHub</h2></td>';
    echo '<td align="center">';

    if ($role === 'Admin') {
        echo '<a href="admin_dashboard.php"' . ($activePage === 'admin_dashboard' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Admin Dashboard</a> | ';
        echo '<a href="manage_students.php"' . ($activePage === 'manage_students' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Manage Students</a> | ';
        echo '<a href="manage_events.php"' . ($activePage === 'manage_events' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Manage Events</a> | ';
        echo '<a href="index.php"' . ($activePage === 'events' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Public Events</a> | ';
        echo '<a href="view_records.php"' . ($activePage === 'view_records' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>All Records</a>';
    } elseif ($role === 'Student') {
        echo '<a href="dashboard.php"' . ($activePage === 'dashboard' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Dashboard</a> | ';
        echo '<a href="profile.php"' . ($activePage === 'profile' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Profile</a> | ';
        echo '<a href="index.php"' . ($activePage === 'events' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Events</a> | ';
        echo '<a href="register_event.php"' . ($activePage === 'register_event' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Register Event</a> | ';
        echo '<a href="feedback.html"' . ($activePage === 'feedback' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Feedback</a> | ';
        echo '<a href="contact.html"' . ($activePage === 'contact' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Contact</a>';
    } else {
        echo '<a href="home.html"' . ($activePage === 'home' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Home</a> | ';
        echo '<a href="about.html"' . ($activePage === 'about' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>About</a> | ';
        echo '<a href="index.php"' . ($activePage === 'events' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Events</a> | ';
        echo '<a href="faq.html"' . ($activePage === 'faq' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>FAQ</a> | ';
        echo '<a href="contact.html"' . ($activePage === 'contact' ? ' style="font-weight:700;color:#ffd54f;"' : '') . '>Contact</a>';
    }

    echo '</td>';
    echo '<td align="right">';

    if ($role) {
        echo '<span style="font-size:13px;margin-right:12px;color:#fff;">' . htmlspecialchars($name) . ' (' . htmlspecialchars($role) . ')</span>';
        echo '<a href="logout.php" style="color:#ffcdd2;font-weight:600;">Logout</a>';
    } else {
        echo '<a href="login.php">Login</a> | ';
        echo '<a href="register.html">Register</a>';
    }

    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</nav>';
    echo '</header>';
}
