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
            header("Location: manage_students.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}
?>
