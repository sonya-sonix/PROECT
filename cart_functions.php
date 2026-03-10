<?php
require_once 'db.php';

function addToCart($user_id, $product_id, $product_type, $quantity = 1, $weight_kg = null, $wishes = '', $selected_options = null, $design_image = null, $unit_price = null, $total_price = null) {
    global $pdo;
    
    try {
        // Получаем информацию о товаре
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }
        
        // Определяем цену
        if ($unit_price !== null && $total_price !== null) {
            $final_unit_price = $unit_price;
            $final_total_price = $total_price;
        } elseif ($product_type == 'classic_cake' && $weight_kg) {
            $final_unit_price = 55;
            $final_total_price = $final_unit_price * $weight_kg;
        } else {
            $final_unit_price = $product['base_price'];
            $final_total_price = $final_unit_price * $quantity;
        }
        
        // Добавляем новый товар в корзину
        $sql = "INSERT INTO cart_items (
            user_id, product_id, variant_id, modifier_id, quantity, unit_price, total_price, 
            wishes, weight_kg, design_image, selected_options
        ) VALUES (?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id, 
            $product_id, 
            $quantity, 
            $final_unit_price, 
            $final_total_price,
            $wishes,
            $weight_kg,
            $design_image,
            $selected_options ? json_encode($selected_options) : null
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Товар добавлен в корзину'];
        } else {
            return ['success' => false, 'message' => 'Ошибка при добавлении в корзину'];
        }
        
    } catch (PDOException $e) {
        error_log("Ошибка добавления в корзину: " . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()];
    }
}

function getCart($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.description, p.image_url, p.product_type 
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getSelectedCartItems($user_id, $selected_ids) {
    global $pdo;
    
    if (empty($selected_ids)) {
        return [];
    }
    
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $params = array_merge([$user_id], $selected_ids);
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.description, p.image_url, p.product_type 
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.id IN ($placeholders)
        ORDER BY c.added_at DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function updateCartQuantity($cart_id, $quantity) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE id = ?");
    $stmt->execute([$cart_id]);
    $item = $stmt->fetch();
    
    if ($item) {
        $total_price = $item['unit_price'] * $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, total_price = ? WHERE id = ?");
        $stmt->execute([$quantity, $total_price, $cart_id]);
        return true;
    }
    return false;
}

function removeFromCart($cart_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    return $stmt->execute([$cart_id]);
}

function removeMultipleFromCart($cart_ids) {
    global $pdo;
    
    if (empty($cart_ids)) {
        return true;
    }
    
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id IN ($placeholders)");
    return $stmt->execute($cart_ids);
}

function clearCart($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}
?>