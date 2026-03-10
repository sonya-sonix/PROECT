<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$login = $_POST['login'] ?? ''; // может быть email ИЛИ телефон
$password = $_POST['password'] ?? '';
$remember = $_POST['remember'] ?? false;

if (empty($login) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

try {
    // Ищем пользователя по email ИЛИ по телефону
    $sql = "SELECT * FROM users WHERE email = :login OR phone_number = :login";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Сессия
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone_number'];
        
        // Запомнить меня - создаем токен
        if ($remember === 'true' || $remember === true) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Сохраняем токен в БД
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Отправляем токен в куки
            echo json_encode([
                'success' => true,
                'role' => $user['role'],
                'name' => $user['full_name'],
                'remember_token' => $token,
                'message' => 'Вход выполнен успешно!'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'role' => $user['role'],
            'name' => $user['full_name'],
            'message' => 'Вход выполнен успешно!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Неверный email/телефон или пароль']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>