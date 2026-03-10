<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($full_name) || empty($phone) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Пароль минимум 6 символов']);
    exit;
}

try {
    // Проверка существования
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
    $check->execute([$email, $phone]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Пользователь уже существует']);
        exit;
    }
    
    // Хэшируем пароль
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Вставляем
    $sql = "INSERT INTO users (email, password_hash, full_name, phone_number, role, created_at) 
            VALUES (?, ?, ?, ?, 'customer', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $password_hash, $full_name, $phone]);
    
    $user_id = $pdo->lastInsertId();
    
    // Создаем сессию
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_role'] = 'customer';
    $_SESSION['user_email'] = $email;
    
    echo json_encode([
        'success' => true,
        'message' => 'Регистрация успешна!',
        'name' => $full_name,
        'role' => 'customer'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>