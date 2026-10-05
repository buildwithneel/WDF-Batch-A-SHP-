<?php
require_once __DIR__ . '/session_helper.php';

init_session();

if (isset($_SESSION['user_id'])) {
    $conn = get_db_connection();
    if ($conn) {
        $stmt = $conn->prepare("UPDATE students SET remember_token = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php?success=You have been securely logged out.");
exit();
?>
