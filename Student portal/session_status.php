<?php
require_once __DIR__ . '/session_helper.php';

init_session();
check_remember_me();

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'role' => $_SESSION['role'] ?? null,
    'fullname' => $_SESSION['fullname'] ?? null,
    'student_id' => $_SESSION['student_id'] ?? null
]);
