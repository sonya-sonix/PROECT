<?php
require_once 'db.php';
$date = $_GET['date'] ?? '';

// Считаем сколько КЛАССИЧЕСКИХ ТОРТОВ (classic_cake) заказано на эту дату
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.delivery_date = ? 
    AND p.product_type = 'classic_cake'
    AND o.status NOT IN ('cancelled')
");
$stmt->execute([$date]);
$count = $stmt->fetchColumn();

echo json_encode([
    'available' => ($count < 5),
    'current_count' => (int)$count
]);