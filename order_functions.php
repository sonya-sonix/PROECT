<?php
require_once 'db.php';

/**
 * Рассчёт стоимости доставки по расстоянию
 */
function calculateDeliveryCost($distance) {
    global $pdo;
    
    if ($distance > 100) {
        return 'special';
    }
    
    $stmt = $pdo->prepare("SELECT * FROM delivery_zones WHERE min_distance <= ? AND max_distance >= ?");
    $stmt->execute([$distance, $distance]);
    $zone = $stmt->fetch();
    
    if ($zone) {
        return $zone['price'];
    }
    
    return 30;
}

/**
 * Проверка доступности даты для тортов
 */
function checkDateAvailability($date, $cake_count) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM delivery_slots WHERE delivery_date = ?");
    $stmt->execute([$date]);
    $slot = $stmt->fetch();
    
    if (!$slot) {
        $stmt = $pdo->prepare("INSERT INTO delivery_slots (delivery_date, max_cakes, booked_cakes) VALUES (?, 5, ?)");
        $stmt->execute([$date, $cake_count]);
        return true;
    }
    
    return ($slot['booked_cakes'] + $cake_count) <= $slot['max_cakes'];
}

/**
 * Создание заказа (все товары из корзины)
 */
function createOrder($user_id, $cart_items, $delivery_data) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $order_number = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $total_amount = 0;
        foreach ($cart_items as $item) { $total_amount += $item['total_price']; }
        $total_amount += ($delivery_data['delivery_cost'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, delivery_address, delivery_type, delivery_cost, payment_method, customer_notes, delivery_date, delivery_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $order_number, $total_amount, $delivery_data['delivery_address'] ?? '', $delivery_data['delivery_type'], $delivery_data['delivery_cost'] ?? 0, $delivery_data['payment_method'], $delivery_data['customer_notes'], $delivery_data['delivery_date'], $delivery_data['delivery_time']]);
        
        $order_id = $pdo->lastInsertId();

        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, wishes, quantity, unit_price, total_price, item_image, design_image, variant_name, modifier_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $order_id, $item['product_id'], $item['name'], 
                $item['wishes'], // СОХРАНЯЕМ ПОЖЕЛАНИЯ
                $item['quantity'], $item['unit_price'], $item['total_price'], 
                $item['item_image'], 
                $item['design_image'], // СОХРАНЯЕМ ФОТО-РЕФЕРЕНС
                $item['variant_name'], $item['modifier_name']
            ]);
        }

        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $pdo->commit();
        return ['success' => true, 'order_id' => $order_id];
    } catch (Exception $e) { $pdo->rollBack(); return ['success' => false, 'message' => $e->getMessage()]; }
}

/**
 * Создание заказа из выбранных товаров
 */
function createOrderFromSelected($user_id, $selected_ids, $delivery_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $params = array_merge([$user_id], $selected_ids);
        
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.image_url, p.product_type 
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND c.id IN ($placeholders)
        ");
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();
        
        if (empty($cart_items)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Нет выбранных товаров'];
        }
        
        $order_number = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $total_amount = 0;
        $cake_count = 0;
        
        foreach ($cart_items as $item) {
            $total_amount += $item['total_price'];
            if ($item['product_type'] == 'classic_cake') {
                $cake_count++;
            }
        }
        
        $delivery_cost = 0;
        if ($delivery_data['delivery_type'] == 'delivery') {
            $delivery_cost = $delivery_data['delivery_cost'] ?? 0;
            $total_amount += $delivery_cost;
        }
        
        if ($cake_count > 0 && !checkDateAvailability($delivery_data['delivery_date'], $cake_count)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'На выбранную дату уже заказано максимальное количество тортов (5)'];
        }
        
        $delivery_address = '';
        $pickup_address = null;
        
        if ($delivery_data['delivery_type'] == 'pickup') {
            $pickup_address = 'ул. Асфальтная 63А-24';
            $delivery_address = 'Самовывоз: ул. Асфальтная 63А-24';
        } else {
            $delivery_address = $delivery_data['delivery_address'] ?? '';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, order_number, status, total_amount, delivery_address,
                delivery_type, delivery_cost, payment_method, payment_status,
                customer_notes, delivery_date, delivery_time, pickup_address, created_at
            ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id, 
            $order_number, 
            $total_amount,
            $delivery_address,
            $delivery_data['delivery_type'],
            $delivery_cost,
            $delivery_data['payment_method'] ?? 'cash',
            $delivery_data['customer_notes'] ?? null,
            $delivery_data['delivery_date'],
            $delivery_data['delivery_time'] ?? null,
            $pickup_address
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        $bento_images = [
            'vanilla_berry' => 'img/бенто-ваниль-ягода.jpg',
            'vanilla_caramel' => 'img/бенто-ваниль-карамель.jpg',
            'choco_berry' => 'img/бенто-шоколад-ягода.jpg',
            'choco_caramel' => 'img/бенто-шоколад-карамель.jpg',
            'choco_snickers' => 'img/бенто-сникерс.jpg',
            'default' => 'img/бенто1.jpg'
        ];
        
        foreach ($cart_items as $item) {
            $product_name = $item['name'];
            $item_image = $item['image_url'] ?? null;
            
            if ($item['product_type'] == 'bento' && !empty($item['selected_options'])) {
                $options = json_decode($item['selected_options'], true);
                
                $size = $options['size'] ?? 'M';
                $sizeText = $size === 'S' ? 'Маленький' : ($size === 'M' ? 'Средний' : 'Большой');
                
                $biscuit = $options['biscuit'] ?? 'vanilla';
                $biscuitText = $biscuit === 'choco' ? 'шоколадный' : 'ванильный';
                
                $filling = $options['filling'] ?? 'berry';
                $fillingText = '';
                $bento_key = 'default';
                
                if ($filling === 'berry') {
                    $fillingText = 'ягодная';
                    $bento_key = ($biscuit === 'vanilla') ? 'vanilla_berry' : 'choco_berry';
                } else if ($filling === 'caramel') {
                    $fillingText = 'карамельная';
                    $bento_key = ($biscuit === 'vanilla') ? 'vanilla_caramel' : 'choco_caramel';
                } else if ($filling === 'snickers') {
                    $fillingText = 'сникерс';
                    $bento_key = 'choco_snickers';
                }
                
                $product_name = "Бенто-торт $sizeText ($biscuitText, $fillingText)";
                $item_image = $bento_images[$bento_key] ?? $bento_images['default'];
            }
            
            // Проверяем, есть ли колонки wishes и design_image
            $check_columns = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'wishes'")->rowCount();
            
            if ($check_columns > 0) {
                // Если колонки есть - вставляем с ними
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, product_name, quantity, weight_kg,
                        variant_name, modifier_name, unit_price, total_price, item_image,
                        wishes, design_image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $variant_name = null;
                $modifier_name = null;
                
                if (!empty($item['selected_options'])) {
                    $options = json_decode($item['selected_options'], true);
                    if (isset($options['size'])) $variant_name = $options['size'];
                    if (isset($options['biscuit'])) $variant_name = ($variant_name ? $variant_name . ', ' : '') . $options['biscuit'];
                    if (isset($options['filling'])) $modifier_name = $options['filling'];
                }
                
                $stmt->execute([
                    $order_id, 
                    $item['product_id'], 
                    $product_name, 
                    $item['quantity'],
                    $item['weight_kg'], 
                    $variant_name, 
                    $modifier_name,
                    $item['unit_price'], 
                    $item['total_price'],
                    $item_image,
                    $item['wishes'] ?? null,
                    $item['design_image'] ?? null
                ]);
            } else {
                // Если колонок нет - вставляем без них
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, product_name, quantity, weight_kg,
                        variant_name, modifier_name, unit_price, total_price, item_image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $variant_name = null;
                $modifier_name = null;
                
                if (!empty($item['selected_options'])) {
                    $options = json_decode($item['selected_options'], true);
                    if (isset($options['size'])) $variant_name = $options['size'];
                    if (isset($options['biscuit'])) $variant_name = ($variant_name ? $variant_name . ', ' : '') . $options['biscuit'];
                    if (isset($options['filling'])) $modifier_name = $options['filling'];
                }
                
                $stmt->execute([
                    $order_id, 
                    $item['product_id'], 
                    $product_name, 
                    $item['quantity'],
                    $item['weight_kg'], 
                    $variant_name, 
                    $modifier_name,
                    $item['unit_price'], 
                    $item['total_price'],
                    $item_image
                ]);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, 'pending')");
        $stmt->execute([$order_id]);
        
        if ($cake_count > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO delivery_slots (delivery_date, booked_cakes) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE booked_cakes = booked_cakes + ?
            ");
            $stmt->execute([$delivery_data['delivery_date'], $cake_count, $cake_count]);
        }
        
        // Удаляем только выбранные товары
        $delete_placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND id IN ($delete_placeholders)");
        $stmt->execute(array_merge([$user_id], $selected_ids));
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Заказ успешно создан', 'order_id' => $order_id, 'order_number' => $order_number];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Ошибка создания заказа из выбранных: " . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка при создании заказа: ' . $e->getMessage()];
    }
}

/**
 * Получение заказов пользователя
 */
function getUserOrders($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Получение деталей заказа
 */
function getOrderDetails($order_id, $user_id = null) {
    global $pdo;
    
    $sql = "SELECT * FROM orders WHERE id = ?";
    $params = [$order_id];
    
    if ($user_id) {
        $sql .= " AND user_id = ?";
        $params[] = $user_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order['items'] = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC");
        $stmt->execute([$order_id]);
        $order['status_history'] = $stmt->fetchAll();
    }
    
    return $order;
}
/**
 * Проверка минимальной даты заказа (за 3 дня)
 */
function getMinOrderDate() {
    // Минимальная дата - сегодня + 3 дня
    return date('Y-m-d', strtotime('+3 days'));
}

/**
 * Проверка, можно ли заказать на выбранную дату
 */
function canOrderOnDate($date) {
    $min_date = getMinOrderDate();
    return $date >= $min_date;
}

/**
 * Получение количества тортов (классических и бенто) на дату
 */
function getCakeCountOnDate($date) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as order_count,
               SUM(CASE WHEN p.product_type IN ('classic_cake', 'bento') THEN 1 ELSE 0 END) as cake_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.delivery_date) = :date 
            AND o.status NOT IN ('cancelled', 'delivered')
    ");
    $stmt->execute(['date' => $date]);
    $result = $stmt->fetch();
    
    return $result['cake_count'] ?? 0;
}

/**
 * Получение статистики по датам для админа
 */
function getDateStats($date) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as orders_count,
            SUM(CASE WHEN p.product_type IN ('classic_cake', 'bento') THEN oi.quantity ELSE 0 END) as cakes_count,
            SUM(CASE WHEN p.product_type NOT IN ('classic_cake', 'bento') THEN oi.quantity ELSE 0 END) as desserts_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.delivery_date) = :date 
            AND o.status NOT IN ('cancelled', 'delivered')
    ");
    $stmt->execute(['date' => $date]);
    return $stmt->fetch();
}
?>
