<?php
date_default_timezone_set('Europe/Minsk');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $_SESSION['message'] = 'Статус заказа обновлен!';
    header('Location: kitchen.php?date=' . ($_GET['date'] ?? date('Y-m-d')));
    exit;
}

$pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// ========== ПАРАМЕТРЫ ==========
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_time = strtotime($selected_date . ' 00:00:00');
$selected_year = date('Y', $selected_time);
$selected_month = date('m', $selected_time);

// ========== РАСЧЕТ НЕДЕЛИ ==========
$n = (int)date('N', $selected_time);
$monday_ts = $selected_time - (($n - 1) * 86400);
$sunday_ts = $monday_ts + (6 * 86400);
$prev_week_date = date('Y-m-d', $monday_ts - (7 * 86400));
$next_week_date = date('Y-m-d', $monday_ts + (7 * 86400));

// ========== РУССКИЕ МАССИВЫ ==========
$weekdays_short = [1 => 'ПН', 2 => 'ВТ', 3 => 'СР', 4 => 'ЧТ', 5 => 'ПТ', 6 => 'СБ', 7 => 'ВС'];
$weekdays_full  = [1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг', 5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'];
$months_rod     = ['01' => 'янв', '02' => 'фев', '03' => 'мар', '04' => 'апр', '05' => 'май', '06' => 'июн', '07' => 'июл', '08' => 'авг', '09' => 'сен', '10' => 'окт', '11' => 'ноя', '12' => 'дек'];
$months_full    = ['01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля', '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа', '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'];

$status_names = [
    'pending' => 'Ожидает',
    'confirmed' => 'Подтверждён',
    'preparing' => 'Готовится',
    'ready' => 'Готов',
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

// ========== ВЫБОР МЕСЯЦА ==========
$first_day = strtotime("$selected_year-$selected_month-01");
$days_in_month = date('t', $first_day);

$dates = [];
for ($i = 1; $i <= $days_in_month; $i++) {
    $date = date('Y-m-d', strtotime("$selected_year-$selected_month-$i"));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id) as cnt 
        FROM orders 
        WHERE DATE(delivery_date) = :date 
            AND status NOT IN ('cancelled', 'delivered')
    ");
    $stmt->execute(['date' => $date]);
    $count = $stmt->fetch()['cnt'] ?? 0;
    
    $dates[] = [
        'order_date' => $date,
        'orders_count' => $count,
        'day_number' => $i,
        'day_name' => date('N', strtotime($date)),
        'month' => date('m', strtotime($date))
    ];
}

$prev_month = date('m', strtotime("-1 month", $first_day));
$prev_year  = date('Y', strtotime("-1 month", $first_day));
$next_month = date('m', strtotime("+1 month", $first_day));
$next_year  = date('Y', strtotime("+1 month", $first_day));

$years = [];
for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 2; $y++) {
    $years[] = $y;
}

// ========== ДНИ НЕДЕЛИ ==========
$week_days = [];
for ($i = 0; $i < 7; $i++) {
    $day_ts = strtotime(date('Y-m-d', $monday_ts) . " +$i days 12:00:00");
    $date_str = date('Y-m-d', $day_ts);
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id) as cnt FROM orders WHERE DATE(delivery_date) = ? AND status NOT IN ('cancelled', 'delivered')");
    $stmt->execute([$date_str]);
    $count = $stmt->fetch()['cnt'] ?? 0;

    $week_days[] = [
        'date'         => $date_str,
        'day_num'      => date('j', $day_ts),
        'day_name_idx' => (int)date('N', $day_ts),
        'month'        => date('m', $day_ts),
        'is_today'     => ($date_str == date('Y-m-d')),
        'is_selected'  => ($date_str == $selected_date),
        'orders_count' => $count
    ];
}

// ========== ЗАГОЛОВОК НЕДЕЛИ ==========
if (date('m', $monday_ts) == date('m', $sunday_ts)) {
    $week_title = date('j', $monday_ts) . '–' . date('j', $sunday_ts) . ' ' . $months_full[date('m', $monday_ts)] . ' ' . date('Y', $monday_ts);
} else {
    $week_title = date('j', $monday_ts) . ' ' . $months_full[date('m', $monday_ts)] . ' – ' . 
                  date('j', $sunday_ts) . ' ' . $months_full[date('m', $sunday_ts)] . ' ' . date('Y', $sunday_ts);
}

// ========== ПОЛУЧАЕМ ЗАКАЗЫ ==========
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.delivery_date,
        o.delivery_time,
        o.customer_notes,
        o.total_amount,
        o.delivery_type,
        o.delivery_address,
        o.pickup_address,
        o.payment_method,
        o.payment_status,
        o.delivery_cost,
        u.full_name as customer_name,
        u.phone_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.delivery_date LIKE :date_like
        AND o.status NOT IN ('cancelled', 'delivered')
    ORDER BY o.delivery_time ASC, o.created_at ASC
");
$stmt->execute(['date_like' => $selected_date . '%']);
$orders = $stmt->fetchAll();

// 👇 СОЗДАЕМ НОВЫЙ МАССИВ ДЛЯ РЕЗУЛЬТАТА (как во втором примере)
$result = [];

foreach ($orders as $order) {
    // Получаем детали для каждого заказа
    $stmt = $pdo->prepare("
        SELECT 
            oi.id,
            oi.product_name,
            oi.quantity,
            oi.weight_kg,
            oi.variant_name,
            oi.modifier_name,
            oi.unit_price,
            oi.total_price,
            oi.wishes,
            oi.design_image,
            oi.item_image,
            p.image_url as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
    
    // Добавляем items к заказу и помещаем в новый массив
    $order['items'] = $items;
    $result[] = $order;
}

// Заменяем старый массив новым
$orders = $result;

echo "<!-- НАЙДЕНО ЗАКАЗОВ: " . count($orders) . " -->";
foreach ($orders as $o) {
    echo "<!-- ЗАКАЗ: " . $o['order_number'] . " ID: " . $o['id'] . " -->";
    echo "<!-- ПОЗИЦИЙ: " . count($o['items']) . " -->";
}

// СТАТИСТИКА
$total_orders = count($orders);
$total_items = 0;
$total_sum = 0;
$total_delivery = 0;

foreach ($orders as $order) {
    $total_sum += $order['total_amount'];
    $total_delivery += $order['delivery_cost'];
    $total_items += count($order['items']);
}

$stats = [
    'total_orders' => $total_orders,
    'total_items' => $total_items,
    'total_sum' => $total_sum,
    'total_delivery' => $total_delivery
];

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кухня | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #faf7f2; 
            padding: 30px;
            display: flex;
            justify-content: center;
        }
        
        .kitchen-container {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Шапка */
        .kitchen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #d8737f;
        }
        .kitchen-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 1.8rem;
            color: #333;
        }
        .kitchen-header h1 span {
            color: #d8737f;
        }
        .kitchen-header h1 i {
            color: #d8737f;
            margin-right: 8px;
        }
        .nav-links {
            display: flex;
            gap: 12px;
        }
        .nav-btn {
            background: white;
            color: #d8737f;
            padding: 8px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            border: 1.5px solid #d8737f;
            transition: 0.3s;
        }
        .nav-btn:hover {
            background: #d8737f;
            color: white;
        }
        
        /* Панель управления датой */
        .date-panel {
            background: white;
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .date-selectors {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
            min-width: 120px;
        }
        
        .today-btn-small {
            background: #d8737f;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
        }
        
        .today-btn-small:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
        
        .current-date-display {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            background: #f9f9f9;
            padding: 8px 25px;
            border-radius: 40px;
        }
        
        /* Навигация по неделям */
        .week-nav {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.03);
        }
        .week-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .week-arrows {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .arrow {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #666;
            transition: 0.3s;
        }
        .arrow:hover {
            background: #d8737f;
            color: white;
        }
        .today-btn {
            background: #d8737f;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .week-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            background: #f9f9f9;
            padding: 8px 25px;
            border-radius: 40px;
        }
        
        /* Дни недели */
        .weekdays {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .day-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 5px;
            border-radius: 40px;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
            position: relative;
            background: #f9f9f9;
        }
        .day-item:hover {
            background: #ffe6e6;
        }
        .day-item.selected {
            background: #d8737f;
            color: white;
        }
        .day-name {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .day-number {
            font-size: 1.3rem;
            font-weight: 700;
        }
        .day-month {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .day-badge {
            position: absolute;
            top: -5px;
            right: 5px;
            background: #d8737f;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        /* Сводка */
        .day-summary {
            background: white;
            border-radius: 14px;
            padding: 18px 25px;
            margin-bottom: 25px;
            border-left: 4px solid #d8737f;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .stats {
            display: flex;
            gap: 35px;
        }
        .stat-item {
            text-align: right;
        }
        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #d8737f;
        }
        .stat-label {
            font-size: 0.7rem;
            color: #999;
            text-transform: uppercase;
        }
        
        /* Сообщение */
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Сетка заказов */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 5px solid;
            transition: 0.3s;
            height: fit-content;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(216,115,127,0.1);
        }
        .order-card.status-pending { border-top-color: #ffc107; }
        .order-card.status-confirmed { border-top-color: #17a2b8; }
        .order-card.status-preparing { border-top-color: #fd7e14; }
        .order-card.status-ready { border-top-color: #28a745; }
        
        /* Шапка заказа */
        .order-header {
            background: #f9f9f9;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0e8e0;
            cursor: pointer;
        }
        .order-number {
            font-weight: 700;
            font-size: 1rem;
            color: #333;
        }
        .order-time {
            background: #fff;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #d8737f;
        }
        .toggle-btn {
            background: none;
            border: none;
            color: #d8737f;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s;
        }
        
        /* Краткая информация */
        .order-preview {
            padding: 12px 15px;
            background: white;
        }
        .customer-short {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .items-preview {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 6px;
            padding-left: 8px;
            border-left: 2px solid #f0e8e0;
        }
        .item-preview-line {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 2px 0;
        }
        .total-preview {
            text-align: right;
            font-weight: 600;
            color: #d8737f;
            margin-top: 6px;
            font-size: 1rem;
        }
        
        /* Детали заказа - ЭЛЕГАНТНЫЙ ДИЗАЙН */
        .order-details {
            display: none;
            padding: 20px;
            border-top: 2px dashed #f0e8e0;
            background: #fcfcfc;
        }
        .order-details.active {
            display: block;
        }
        
        /* ИНФОРМАЦИЯ О КЛИЕНТЕ - МИНИМАЛИСТИЧНО */
        .client-info {
            background: white;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            border: 1px solid #f0e8e0;
        }
        
        .client-row {
            display: flex;
            align-items: baseline;
            padding: 6px 0;
            border-bottom: 1px dashed #f5f5f5;
        }
        
        .client-row:last-child {
            border-bottom: none;
        }
        
        .client-label {
            width: 85px;
            font-size: 0.8rem;
            color: #999;
        }
        
        .client-value {
            flex: 1;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .payment-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        /* ДОСТАВКА/САМОВЫВОЗ - КОМПАКТНО */
        .delivery-block {
            background: #f8f9fa;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .delivery-block.pickup {
            border-left-color: #17a2b8;
        }
        
        .delivery-block.delivery {
            border-left-color: #28a745;
        }
        
        .delivery-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .delivery-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .delivery-text {
            font-weight: 500;
            color: #333;
        }
        
        .delivery-address {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
        }
        
        .delivery-cost-badge {
            background: #28a745;
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* ТОВАРЫ - ВСЁ В ОДНОМ БЛОКЕ */
        .items-section {
            margin: 20px 0;
        }
        
        .items-section h4 {
            font-size: 0.95rem;
            margin-bottom: 12px;
            color: #d8737f;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .product-card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #f0e8e0;
            transition: 0.2s;
        }
        
        .product-card:hover {
            border-color: #d8737f;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .product-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: #333;
        }
        
        .product-quantity {
            background: #f0f0f0;
            color: #666;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .product-body {
            display: flex;
            gap: 15px;
        }
        
        .product-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: 0.2s;
            flex-shrink: 0;
        }
        
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }
        
        .product-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            background: #f5f5f5;
            color: #666;
        }
        
        .product-tag i {
            color: #d8737f;
            font-size: 0.65rem;
        }
        
        .product-tag.client-photo {
            background: #d8737f;
            color: white;
        }
        
        .product-tag.client-photo i {
            color: white;
        }
        
        .product-wishes {
            margin-top: 8px;
            padding: 8px 12px;
            background: #fff9e6;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #856404;
            border-left: 3px solid #ffc107;
        }
        
        .order-notes {
            background: #fff9e6;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            font-size: 0.9rem;
            border-left: 4px solid #ffc107;
        }
        
        .status-form {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .status-select {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }
        
        .status-form button {
            background: #d8737f;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .status-form button:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
            color: #ccc;
            grid-column: 1/-1;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        /* Модалка для фото */
        .photo-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .photo-modal.active {
            display: flex;
        }
        .photo-modal-content {
            max-width: 800px;
            max-height: 90vh;
            background: white;
            border-radius: 20px;
            overflow: hidden;
        }
        .photo-modal-content img {
            width: 100%;
            height: auto;
            max-height: 70vh;
            object-fit: contain;
        }
        .photo-modal-header {
            background: #d8737f;
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .photo-modal-close {
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
        }
        .photo-modal-footer {
            background: white;
            padding: 15px 20px;
            color: #333;
            border-top: 1px solid #eee;
            max-height: 100px;
            overflow-y: auto;
        }
        .orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
    align-items: start; /* Важно: start, а не stretch */
}

.order-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border-top: 5px solid;
    transition: 0.3s;
    /* Убираем height и flex */
}

/* Фиксируем высоту ТОЛЬКО для закрытого состояния */
.order-card:not(.has-open) .order-preview {
    min-height: 110px; /* Подбери под свой контент */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Делаем превью одинаковым */
.customer-short {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    height: 24px; /* Фиксированная высота */
}

.items-preview {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 6px;
    padding-left: 8px;
    border-left: 2px solid #f0e8e0;
    min-height: 60px; /* Место под 2-3 строчки */
}

.total-preview {
    text-align: right;
    font-weight: 600;
    color: #d8737f;
    margin-top: 6px;
    font-size: 1rem;
    height: 24px;
    line-height: 24px;
}

/* Когда карточка открыта - убираем ограничения */
.order-card.opened .order-preview {
    min-height: auto;
}

.order-details {
    display: none;
    padding: 20px;
    border-top: 2px dashed #f0e8e0;
    background: #fcfcfc;
}

.order-details.active {
    display: block;
}
        @media print {
            .week-nav, .nav-links, .toggle-btn, .status-form, .date-panel {
                display: none !important;
            }
            .order-details {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Модалка для фото -->
    <div class="photo-modal" id="photoModal" onclick="closePhotoModal()">
        <div class="photo-modal-content" onclick="event.stopPropagation()">
            <div class="photo-modal-header">
                <span id="modalPhotoTitle">Фото</span>
                <span class="photo-modal-close" onclick="closePhotoModal()">✕</span>
            </div>
            <img src="" id="modalPhoto" alt="">
            <div class="photo-modal-footer" id="modalPhotoInfo"></div>
        </div>
    </div>

    <div class="kitchen-container">
        
        <div class="kitchen-header">
            <h1><i class="fa-solid fa-kitchen-set"></i> <span>CITY</span>TORT • Кухня</h1>
            <div class="nav-links">
                <a href="admin.php" class="nav-btn"><i class="fa-solid fa-cog"></i> Админка</a>
                <a href="logout.php" class="nav-btn"><i class="fa-solid fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="message">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Панель управления датой -->
        <div class="date-panel">
            <div class="date-selectors">
                <select class="date-select" id="year-select" onchange="changeYearMonth()">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                            <?= $year ?> год
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select class="date-select" id="month-select" onchange="changeYearMonth()">
                    <?php foreach ($months_full as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button class="today-btn-small" onclick="window.location.href='?date=<?= date('Y-m-d') ?>'">
                    <i class="fa-regular fa-calendar-check"></i> Сегодня
                </button>
            </div>
            
            <div class="current-date-display">
                <i class="fa-regular fa-calendar" style="color: #d8737f; margin-right: 8px;"></i>
                <?= date('j', $selected_time) ?> <?= $months_full[date('m', $selected_time)] ?> <?= date('Y', $selected_time) ?>
            </div>
        </div>
        
        <!-- Навигация по неделям -->
        <div class="week-nav">
            <div class="week-control">
                <div class="week-arrows">
                    <a href="?date=<?= $prev_week_date ?>" class="arrow"><i class="fa-solid fa-chevron-left"></i></a>
                    <a href="?date=<?= date('Y-m-d') ?>" class="today-btn">Сегодня</a>
                    <a href="?date=<?= $next_week_date ?>" class="arrow"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <div class="week-title"><?= $week_title ?></div>
            </div>
            
            <div class="weekdays">
                <?php foreach ($week_days as $day): ?>
                    <a href="?date=<?= $day['date'] ?>" class="day-item <?= $day['is_selected'] ? 'selected' : '' ?>">
                        <span class="day-name"><?= $weekdays_short[$day['day_name_idx']] ?></span>
                        <span class="day-number"><?= $day['day_num'] ?></span>
                        <span class="day-month"><?= $months_rod[$day['month']] ?></span>
                        <?php if ($day['orders_count'] > 0): ?>
                            <span class="day-badge"><?= $day['orders_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Сводка за день -->
        <div class="day-summary">
            <div style="font-size: 1.2rem; font-weight: 600;">
                <?= date('j', $selected_time) ?> <?= $months_full[date('m', $selected_time)] ?> <?= date('Y', $selected_time) ?>
                <small style="color: #999; font-size: 0.85rem;"><?= $weekdays_full[(int)date('N', $selected_time)] ?></small>
            </div>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
                    <div class="stat-label">Заказов</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['total_items'] ?? 0 ?></div>
                    <div class="stat-label">Позиций</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['total_sum'] ?? 0, 2) ?> BYN</div>
                    <div class="stat-label">Сумма</div>
                </div>
                <?php if ($stats['total_delivery'] > 0): ?>
                <div class="stat-item">
                    <div class="stat-value">+<?= number_format($stats['total_delivery'], 2) ?> BYN</div>
                    <div class="stat-label">Доставка</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Заказы -->
        <h2 style="margin-bottom: 20px; font-size: 1.2rem;">
            <i class="fa-solid fa-clipboard-list" style="color: #d8737f;"></i> 
            Заказы на <?= date('d.m.Y', $selected_time) ?>
        </h2>
        
        <div class="orders-grid">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-mug-hot" style="font-size: 3rem;"></i>
                    <p style="margin-top: 15px;">Нет заказов на этот день</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $time = $order['delivery_time'] ? date('H:i', strtotime($order['delivery_time'])) : '—';
                ?>
                <div class="order-card status-<?= $order['status'] ?>" id="order-<?= $order['id'] ?>">
                    
                    <!-- Шапка -->
                    <div class="order-header" onclick="toggleDetails(<?= $order['id'] ?>)">
                        <span class="order-number">#<?= $order['order_number'] ?></span>
                        <span class="order-time"><i class="fa-regular fa-clock"></i> <?= $time ?></span>
                        <button class="toggle-btn" onclick="event.stopPropagation(); toggleDetails(<?= $order['id'] ?>)">
                            <i class="fa-solid fa-chevron-down" id="chevron-<?= $order['id'] ?>"></i>
                        </button>
                    </div>
                    
                    <!-- Краткая информация -->
                    <div class="order-preview">
                        <div class="customer-short">
                            <i class="fa-regular fa-user"></i> <?= htmlspecialchars(explode(' ', $order['customer_name'])[0]) ?>
                        </div>
                        
                        <div class="items-preview">
                            <?php 
                            $preview_items = array_slice($order['items'], 0, 2);
                            foreach ($preview_items as $item): 
                            ?>
                            <div class="item-preview-line">
                                <?= $item['quantity'] ?>× <?= htmlspecialchars($item['product_name']) ?>
                                <?php if (!empty($item['wishes']) || !empty($item['design_image'])): ?>
                                    <i class="fa-regular fa-note-sticky" style="color: #d8737f; margin-left: 5px;"></i>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($order['items']) > 2): ?>
                                <div class="item-preview-line" style="color: #999;">
                                    + ещё <?= count($order['items']) - 2 ?> позиции
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="total-preview">
                            <?= number_format($order['total_amount'], 2) ?> BYN
                        </div>
                    </div>
                    
                    <!-- Детальная информация -->
                    <div class="order-details" id="details-<?= $order['id'] ?>">
                        
                        <!-- ИНФОРМАЦИЯ О КЛИЕНТЕ (МИНИМАЛИСТИЧНО) -->
                        <div class="client-info">
                            <div class="client-row">
                                <span class="client-label">Имя</span>
                                <span class="client-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                            </div>
                            <div class="client-row">
                                <span class="client-label">Телефон</span>
                                <span class="client-value">📞 <?= htmlspecialchars($order['phone_number'] ?? '—') ?></span>
                            </div>
                            <div class="client-row">
                                <span class="client-label">Оплата</span>
                                <span class="client-value">
                                    <?= $order['payment_method'] == 'cash' ? 'Наличные' : 'Карта' ?>
                                    <span class="payment-badge <?= $order['payment_status'] == 'paid' ? 'payment-paid' : 'payment-pending' ?>">
                                        <?= $order['payment_status'] == 'paid' ? 'оплачено' : 'не оплачено' ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- ДОСТАВКА/САМОВЫВОЗ - КОМПАКТНО -->
                        <div class="delivery-block <?= $order['delivery_type'] ?>">
                            <div class="delivery-info">
                                <div class="delivery-icon">
                                    <i class="fa-regular <?= $order['delivery_type'] == 'pickup' ? 'fa-building' : 'fa-truck' ?>"></i>
                                </div>
                                <div>
                                    <div class="delivery-text">
                                        <?= $order['delivery_type'] == 'pickup' ? 'Самовывоз' : 'Доставка' ?>
                                    </div>
                                    <div class="delivery-address">
                                        <?php if ($order['delivery_type'] == 'pickup'): ?>
                                            <?= htmlspecialchars($order['pickup_address'] ?? 'ул. Асфальтная 63А-24') ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($order['delivery_address']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($order['delivery_cost'] > 0): ?>
                                <div class="delivery-cost-badge">
                                    +<?= number_format($order['delivery_cost'], 2) ?> BYN
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- ТОВАРЫ - ВСЁ В ОДНОМ БЛОКЕ -->
                        <div class="items-section">
                            <h4><i class="fa-solid fa-cake-candles"></i> Состав заказа</h4>
                            
                            <?php foreach ($order['items'] as $item): 
                                // Определяем, какое фото показывать (приоритет у design_image)
                                $display_image = !empty($item['design_image']) ? $item['design_image'] : ($item['item_image'] ?? $item['product_image'] ?? 'img/default.jpg');
                                $has_client_photo = !empty($item['design_image']);
                            ?>
                            <div class="product-card">
                                <div class="product-header">
                                    <span class="product-name"><?= htmlspecialchars($item['product_name']) ?></span>
                                    <span class="product-quantity"><?= $item['quantity'] ?> шт</span>
                                </div>
                                
                                <div class="product-body">
                                    <img src="<?= htmlspecialchars($display_image) ?>" 
                                         class="product-image" 
                                         alt=""
                                         onclick="openPhotoModal('<?= htmlspecialchars($display_image) ?>', '<?= htmlspecialchars($item['product_name']) ?>', '<?= htmlspecialchars($item['wishes'] ?? '') ?>')">
                                    
                                    <div class="product-details">
                                        <div class="product-tags">
                                            <?php if (!empty($item['weight_kg'])): ?>
                                                <span class="product-tag"><i class="fa-solid fa-weight-scale"></i> <?= $item['weight_kg'] ?> кг</span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['variant_name'])): ?>
                                                <span class="product-tag"><i class="fa-regular fa-star"></i> <?= htmlspecialchars($item['variant_name']) ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['modifier_name'])): ?>
                                                <span class="product-tag"><i class="fa-solid fa-plus"></i> <?= htmlspecialchars($item['modifier_name']) ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($has_client_photo): ?>
                                                <span class="product-tag client-photo"><i class="fa-regular fa-image"></i> фото клиента</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($item['wishes'])): ?>
                                            <div class="product-wishes">
                                                <i class="fa-regular fa-note-sticky"></i> <?= nl2br(htmlspecialchars($item['wishes'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Комментарий к заказу -->
                        <?php if (!empty($order['customer_notes'])): ?>
                        <div class="order-notes">
                            <strong><i class="fa-regular fa-note-sticky"></i> Комментарий:</strong>
                            <p style="margin-top: 8px;"><?= nl2br(htmlspecialchars($order['customer_notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Форма изменения статуса -->
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" class="status-select">
                                <?php foreach ($status_names as $key => $name): ?>
                                <option value="<?= $key ?>" <?= $order['status'] == $key ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_order_status">Обновить статус</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetails(orderId) {
            document.querySelectorAll('.order-details.active').forEach(el => {
                if (el.id !== 'details-' + orderId) {
                    el.classList.remove('active');
                    const prevChevron = document.getElementById('chevron-' + el.id.replace('details-', ''));
                    if (prevChevron) prevChevron.style.transform = 'rotate(0deg)';
                }
            });
            
            const details = document.getElementById('details-' + orderId);
            const chevron = document.getElementById('chevron-' + orderId);
            
            if (details && chevron) {
                details.classList.toggle('active');
                chevron.style.transform = details.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }
        
        function changeYearMonth() {
            const year = document.getElementById('year-select').value;
            const month = document.getElementById('month-select').value;
            window.location.href = '?date=' + year + '-' + month + '-01';
        }
        
        function openPhotoModal(imgSrc, title, wishes) {
            document.getElementById('modalPhoto').src = imgSrc;
            document.getElementById('modalPhotoTitle').textContent = title;
            document.getElementById('modalPhotoInfo').textContent = wishes || 'Нет комментария';
            document.getElementById('photoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closePhotoModal() {
            document.getElementById('photoModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
            }
        });
    </script>
</body>
</html>