<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$code = $_POST['code'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($phone) || empty($code) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Пароль минимум 6 символов']);
    exit;
}

try {
    // Проверяем код
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ? AND reset_code = ? AND reset_expires > NOW()");
    $stmt->execute([$phone, $code]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Неверный код или время истекло']);
        exit;
    }
    
    // Обновляем пароль
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_code = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$password_hash, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>