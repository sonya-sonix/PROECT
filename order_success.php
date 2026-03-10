<?php
session_start();
require_once 'db.php';

$order_id = $_GET['order_id'] ?? 0;

if ($order_id && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background: #faf7f2; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .success-container { max-width: 600px; margin: 20px; padding: 50px; background: white; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; }
        .success-icon { font-size: 5rem; color: #28a745; margin-bottom: 20px; }
        .success-title { font-size: 2.5rem; margin-bottom: 20px; color: #333; }
        .success-message { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .order-info { background: #f9f9f9; padding: 20px; border-radius: 15px; margin-bottom: 30px; }
        .order-number { font-size: 1.5rem; font-weight: 600; color: #d8737f; }
        .actions { display: flex; gap: 15px; justify-content: center; }
        .action-btn { padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 500; transition: 0.3s; }
        .btn-primary { background: #d8737f; color: white; }
        .btn-primary:hover { background: #c76571; transform: translateY(-2px); }
        .btn-outline { background: white; color: #d8737f; border: 1px solid #d8737f; }
        .btn-outline:hover { background: #d8737f; color: white; }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        
        <h1 class="success-title">Заказ оформлен!</h1>
        
        <div class="success-message">
            Спасибо за ваш заказ. Мы свяжемся с вами для подтверждения.
        </div>
        
        <?php if (isset($order)): ?>
        <div class="order-info">
            <div style="margin-bottom: 10px;">Номер вашего заказа:</div>
            <div class="order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="profile.php#active" class="action-btn btn-primary">
                <i class="fa-solid fa-box"></i> Мои заказы
            </a>
            <a href="index.php" class="action-btn btn-outline">
                <i class="fa-solid fa-home"></i> На главную
            </a>
        </div>
    </div>
</body>
</html>