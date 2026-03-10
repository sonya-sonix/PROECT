<?php
session_start();
require_once 'db.php';
require_once 'order_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Получаем детали заказа
$order = getOrderDetails($order_id, $user_id);

if (!$order) {
    header('Location: profile.php#orders');
    exit;
}

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Статусы на русском
$status_names = [
    'pending' => 'Ожидает подтверждения',
    'confirmed' => 'Подтверждён',
    'preparing' => 'Готовится',
    'ready' => 'Готов к выдаче',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменён'
];

$status_colors = [
    'pending' => '#ffc107',
    'confirmed' => '#17a2b8',
    'preparing' => '#fd7e14',
    'ready' => '#28a745',
    'delivered' => '#28a745',
    'cancelled' => '#dc3545'
];

// Форматирование способа оплаты
$payment_methods = [
    'cash' => 'Наличными при получении',
    'card' => 'Картой онлайн'
];

// Форматирование способа получения
$delivery_types = [
    'pickup' => 'Самовывоз',
    'delivery' => 'Доставка'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ #<?= htmlspecialchars($order['order_number'] ?? '') ?> | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #faf7f2; padding: 30px; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #d8737f;
        }
        
        .order-header h1 { font-size: 2rem; color: #333; }
        .order-header h1 span { color: #d8737f; }
        
        .back-btn {
            background: white;
            color: #d8737f;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            border: 1px solid #d8737f;
            transition: 0.3s;
        }
        
        .back-btn:hover { background: #d8737f; color: white; }
        
        .status-bar {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .status-badge {
            padding: 10px 25px;
            border-radius: 30px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-date { color: #666; }
        .order-date strong { color: #333; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .info-card h2 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0e8e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h2 i { color: #d8737f; }
        .info-content p { margin: 8px 0; display: flex; align-items: baseline; }
        .info-content strong { color: #333; min-width: 120px; display: inline-block; }
        
        .items-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .items-card h2 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0e8e0;
            display: flex;
            align-items: center; gap: 10px;
        }
        
        .items-card h2 i { color: #d8737f; }
        .order-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f0e8e0; }
        .order-item:last-child { border-bottom: none; }
        .item-image { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; }
        .item-details { flex: 1; }
        .item-name { font-weight: 600; margin-bottom: 5px; }
        .item-options { font-size: 0.85rem; color: #666; margin-bottom: 3px; }
        .item-quantity { font-size: 0.85rem; color: #999; }
        .item-price { font-weight: 600; color: #d8737f; min-width: 100px; text-align: right; }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #f0e8e0;
        }
        
        .total-amount { font-size: 1.5rem; font-weight: 600; color: #d8737f; margin-left: 30px; }
        .notes-card { background: #fff9e6; border-radius: 15px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #ffc107; }
        .notes-card h3 { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #856404; }
        
        .history-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .history-card h2 {
            font-size: 1.2rem; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0e8e0;
            display: flex; align-items: center; gap: 10px;
        }
        .history-card h2 i { color: #d8737f; }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 7px; top: 10px; bottom: 10px; width: 2px; background: #f0e8e0; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before {
            content: ''; position: absolute; left: -23px; top: 5px; width: 12px; height: 12px;
            border-radius: 50%; background: #d8737f; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .timeline-date { font-size: 0.85rem; color: #999; margin-bottom: 5px; }
        .timeline-status { font-weight: 600; margin-bottom: 3px; }
        .timeline-comment { font-size: 0.9rem; color: #666; }
        
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .order-item { flex-wrap: wrap; }
            .item-price { text-align: left; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-header">
            <h1>Заказ <span>#<?= htmlspecialchars($order['order_number'] ?? '') ?></span></h1>
            <a href="profile.php#active" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> К заказам
            </a>
        </div>

        <div class="status-bar">
            <div class="status-badge" style="background: <?= $status_colors[$order['status']] ?? '#666' ?>">
                <?= $status_names[$order['status']] ?? 'Неизвестен' ?>
            </div>
            <div class="order-date">
                <i class="fa-regular fa-calendar"></i>
                Заказ от <strong><?= date('d.m.Y', strtotime($order['created_at'] ?? 'now')) ?></strong> 
                в <strong><?= date('H:i', strtotime($order['created_at'] ?? 'now')) ?></strong>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h2><i class="fa-regular fa-user"></i> Получатель</h2>
                <div class="info-content">
                    <p><strong>Имя:</strong> <?= htmlspecialchars($user['full_name'] ?? '') ?></p>
                    <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone_number'] ?? 'Не указан') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></p>
                </div>
            </div>

            <div class="info-card">
                <h2><i class="fa-regular fa-credit-card"></i> Оплата</h2>
                <div class="info-content">
                    <p><strong>Способ:</strong> <?= $payment_methods[$order['payment_method'] ?? 'cash'] ?></p>
                    <p><strong>Статус:</strong> 
                        <?php if (($order['payment_status'] ?? '') == 'paid'): ?>
                            <span style="color: #28a745;">Оплачено</span>
                        <?php else: ?>
                            <span style="color: #ffc107;">Ожидает</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="info-card">
                <h2><i class="fa-regular fa-calendar"></i> Дата и время</h2>
                <div class="info-content">
                    <p><strong>Дата:</strong> <?= date('d.m.Y', strtotime($order['delivery_date'] ?? 'now')) ?></p>
                    <p><strong>Время:</strong> <?= $order['delivery_time'] ? date('H:i', strtotime($order['delivery_time'])) : 'Не указано' ?></p>
                </div>
            </div>

            <div class="info-card">
                <h2><i class="fa-regular fa-map"></i> Получение</h2>
                <div class="info-content">
                    <p><strong>Способ:</strong> <?= $delivery_types[$order['delivery_type'] ?? 'pickup'] ?></p>
                    <?php if (($order['delivery_type'] ?? '') == 'pickup'): ?>
                        <p><strong>Адрес:</strong> <?= htmlspecialchars($order['pickup_address'] ?? 'ул. Асфальтная 63А-24') ?></p>
                    <?php else: ?>
                        <p><strong>Адрес:</strong> <?= htmlspecialchars($order['delivery_address'] ?? 'Адрес не указан') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($order['customer_notes'])): ?>
        <div class="notes-card">
            <h3><i class="fa-regular fa-note-sticky"></i> Комментарий</h3>
            <p><?= nl2br(htmlspecialchars($order['customer_notes'] ?? '')) ?></p>
        </div>
        <?php endif; ?>

<!-- Состав заказа -->
<div class="items-card">
    <h2><i class="fa-solid fa-cake-candles"></i> Состав заказа</h2>
    
    <?php 
    $items_subtotal = 0; 
    foreach ($order['items'] as $item): 
        $items_subtotal += $item['total_price'];
    ?>
        <div class="order-item">
            <img src="<?= htmlspecialchars($item['item_image'] ?? 'img/default.jpg') ?>" 
                 alt="" class="item-image" onerror="this.src='img/default.jpg'">
            <div class="item-details">
                <div class="item-name"><?= htmlspecialchars($item['product_name'] ?? '') ?></div>
                
                <?php if (!empty($item['variant_name'])): ?>
                    <div class="item-options">📏 <?= htmlspecialchars($item['variant_name']) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($item['modifier_name'])): ?>
                    <div class="item-options">➕ <?= htmlspecialchars($item['modifier_name']) ?></div>
                <?php endif; ?>
                
                <div class="item-quantity">Количество: <?= $item['quantity'] ?> шт.</div>
            </div>
            <div class="item-price"><?= number_format($item['total_price'], 2) ?> BYN</div>
        </div>
    <?php endforeach; ?>

    <!-- Блок расчета ИТОГО -->
    <div style="margin-top: 25px; border-top: 2px solid #f0e8e0; padding-top: 20px;">
        
        <!-- Стоимость товаров -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #666;">
            <span style="font-size: 1rem;">Стоимость товаров:</span>
            <span style="font-weight: 500;"><?= number_format($items_subtotal, 2) ?> BYN</span>
        </div>

        <!-- Доставка -->
        <?php if (($order['delivery_type'] ?? '') == 'delivery'): ?>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #666;">
            <span style="font-size: 1rem;">Доставка:</span>
            <span style="font-weight: 500; color: #d8737f;">+ <?= number_format($order['delivery_cost'] ?? 0, 2) ?> BYN</span>
        </div>
        <?php endif; ?>

        <!-- Итоговая строка (в одну линию) -->
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
            <span style="font-size: 1.2rem; font-weight: 600; color: #333;">ИТОГО К ОПЛАТЕ:</span>
            <span style="font-size: 1.5rem; font-weight: 700; color: #d8737f; white-space: nowrap; margin-left: 20px;">
                <?= number_format($order['total_amount'], 2) ?> BYN
            </span>
        </div>
    </div>
</div>

        <?php if (!empty($order['status_history'])): ?>
        <div class="history-card">
            <h2><i class="fa-regular fa-clock"></i> История</h2>
            <div class="timeline">
                <?php foreach ($order['status_history'] as $history): ?>
                    <div class="timeline-item">
                        <div class="timeline-date"><?= date('d.m.Y H:i', strtotime($history['created_at'])) ?></div>
                        <div class="timeline-status"><?= $status_names[$history['status']] ?? $history['status'] ?></div>
                        <?php if (!empty($history['comment'])): ?>
                            <div class="timeline-comment"><?= htmlspecialchars($history['comment'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>