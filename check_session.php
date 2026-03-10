<?php
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
        'phone' => $_SESSION['user_phone'] ?? ''
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>