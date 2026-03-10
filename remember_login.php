<?php
session_start();
require_once 'db.php';

$token = $_COOKIE['remember_token'] ?? '';

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone_number'];
        echo json_encode(['logged_in' => true, 'role' => $user['role']]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
} else {
    echo json_encode(['logged_in' => false]);
}
?>