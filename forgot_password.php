<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$phone = $_POST['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Введите номер телефона']);
    exit;
}

try {
    // Ищем пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }
    
    // Генерируем код восстановления
    $reset_code = sprintf("%06d", mt_rand(1, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Сохраняем код в БД
    $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$reset_code, $expires, $user['id']]);
    
    // ТУТ ДОЛЖНА БЫТЬ ОТПРАВКА SMS
    // Но пока просто показываем код в ответе (для теста)
    echo json_encode([
        'success' => true, 
        'message' => 'Код восстановления отправлен',
        'debug_code' => $reset_code // Убери это на продакшене!
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>