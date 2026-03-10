<?php
session_start();
// ВРЕМЕННАЯ ОТЛАДКА
error_log("=== НАЧАЛО ОБРАБОТКИ cart_actions.php ===");
error_log("POST: " . print_r($_POST, true));
error_log("FILES: " . print_r($_FILES, true));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
require_once 'db.php';
require_once 'cart_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
    error_log("ACTION: add");
    error_log("FILES в add: " . print_r($_FILES, true));
    
    $product_id = $_POST['product_id'] ?? 0;
    error_log("product_id: $product_id");
    
    // ... остальной код ...
        $product_id = $_POST['product_id'] ?? 0;
        $product_type = $_POST['product_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);
        $weight_kg = isset($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : null;
        $wishes = $_POST['wishes'] ?? '';
        
        // Для бенто цена передаётся отдельно
        $unit_price = isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : null;
        $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : null;
        
        // Получаем опции для конструкторов
        $selected_options = [];
        if (isset($_POST['selected_options'])) {
            $selected_options = json_decode($_POST['selected_options'], true);
        }
        
        // 👇 ОБРАБОТКА ЗАГРУЖЕННОГО ФОТО
        $design_image = null;
        if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] == 0) {
            $upload_dir = 'uploads/designs/';
            
            // Создаем папку если её нет
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['design_file']['name'];
            $file_tmp = $_FILES['design_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Разрешенные форматы
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed)) {
                // Генерируем уникальное имя файла
                $new_filename = 'design_' . time() . '_' . $user_id . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $design_image = $upload_path;
                    error_log("Фото сохранено: " . $design_image);
                } else {
                    error_log("Ошибка сохранения файла: " . $file_tmp . " -> " . $upload_path);
                }
            } else {
                error_log("Недопустимый формат файла: " . $file_ext);
            }
        }
        
        $result = addToCart($user_id, $product_id, $product_type, $quantity, $weight_kg, $wishes, $selected_options, $design_image, $unit_price, $total_price);
        echo json_encode($result);
        break;
        
    case 'update':
        $cart_id = $_POST['cart_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Количество должно быть больше 0']);
            break;
        }
        
        if (updateCartQuantity($cart_id, $quantity)) {
            echo json_encode(['success' => true, 'message' => 'Количество обновлено']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка обновления']);
        }
        break;
        
    case 'remove':
        $cart_id = $_POST['cart_id'] ?? 0;
        
        if (removeFromCart($cart_id)) {
            echo json_encode(['success' => true, 'message' => 'Товар удален из корзины']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка удаления']);
        }
        break;
        
    case 'remove_selected':
        $cart_ids = $_POST['cart_ids'] ?? '';
        if (empty($cart_ids)) {
            echo json_encode(['success' => false, 'message' => 'Нет выбранных товаров']);
            break;
        }
        
        $ids = explode(',', $cart_ids);
        if (removeMultipleFromCart($ids)) {
            echo json_encode(['success' => true, 'message' => 'Выбранные товары удалены']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при удалении']);
        }
        break;
        
    case 'clear':
        if (clearCart($user_id)) {
            echo json_encode(['success' => true, 'message' => 'Корзина очищена']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка очистки']);
        }
        break;
        
    case 'get':
        $cart = getCart($user_id);
        $total = array_sum(array_column($cart, 'total_price'));
        echo json_encode([
            'success' => true,
            'items' => $cart,
            'total' => $total,
            'count' => count($cart)
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
?>