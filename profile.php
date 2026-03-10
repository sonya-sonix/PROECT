<?php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';
require_once 'order_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем информацию о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Получаем корзину
$cart_items = getCart($user_id);

// Получаем заказы пользователя
$orders = getUserOrders($user_id);
$active_orders = array_filter($orders, function($order) {
    return !in_array($order['status'], ['delivered', 'cancelled']);
});
$completed_orders = array_filter($orders, function($order) {
    return in_array($order['status'], ['delivered', 'cancelled']);
});

// Массив фото для бенто
$bento_images = [
    'vanilla_berry' => 'img/бенто-ваниль-ягода.jpg',
    'vanilla_caramel' => 'img/бенто-ваниль-карамель.jpg',
    'choco_berry' => 'img/бенто-шоколад-ягода.jpg',
    'choco_caramel' => 'img/бенто-шоколад-карамель.jpg',
    'choco_snickers' => 'img/бенто-сникерс.jpg',
    'default' => 'img/бенто1.jpg'
];

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone_number'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $errors = [];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }
    
    if ($email != $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email уже используется';
        }
    }
    
    if (empty($errors)) {
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $password_hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);
        }
        $success = 'Профиль обновлён';
        $_SESSION['user_name'] = $full_name;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

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
// Определяем активную вкладку из URL
$active_tab = $_GET['tab'] ?? 'active';
if (!in_array($active_tab, ['active', 'history', 'cart', 'settings'])) {
    $active_tab = 'active';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="notifications.css">
    <style>
        body { padding-top: 80px; background: #faf7f2; }
        .site-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .profile-container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #d8737f; }
        .profile-header h1 { font-size: 2rem; color: #333; }
        .profile-header h1 span { color: #d8737f; }
        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 30px; }
        .profile-sidebar { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: fit-content; }
        .user-avatar-large { text-align: center; margin-bottom: 20px; }
        .user-avatar-large i { font-size: 5rem; color: #d8737f; }
        .user-name { font-size: 1.3rem; font-weight: 600; text-align: center; margin-bottom: 5px; }
        .user-email { text-align: center; color: #666; margin-bottom: 20px; }
        .profile-menu { list-style: none; }
        .profile-menu li { margin-bottom: 10px; }
        .profile-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; color: #333; text-decoration: none; transition: 0.3s; }
        .profile-menu a:hover, .profile-menu a.active { background: #fce9e9; color: #d8737f; }
        .profile-menu a i { width: 20px; color: #d8737f; }
        .badge { background: #d8737f; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: auto; }
        .profile-content { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .profile-section { display: none; }
        .profile-section.active { display: block; }
        .section-title { font-size: 1.3rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0e8e0; }
        
        /* Заказы */
        .orders-grid { display: grid; gap: 20px; }
        .order-card { background: #f9f9f9; border-radius: 15px; padding: 20px; border-left: 4px solid; transition: 0.3s; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .order-card.status-pending { border-left-color: #ffc107; }
        .order-card.status-confirmed { border-left-color: #17a2b8; }
        .order-card.status-preparing { border-left-color: #fd7e14; }
        .order-card.status-ready { border-left-color: #28a745; }
        .order-card.status-delivered { border-left-color: #28a745; }
        .order-card.status-cancelled { border-left-color: #dc3545; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .order-number { font-weight: 600; font-size: 1.1rem; }
        .order-date { color: #666; font-size: 0.9rem; }
        .order-status { display: inline-block; padding: 4px 12px; border-radius: 20px; color: white; font-size: 0.8rem; font-weight: 600; margin-bottom: 10px; }
        .order-total { font-weight: 600; color: #d8737f; margin: 10px 0; }
        .order-link { display: inline-block; margin-top: 10px; color: #d8737f; text-decoration: none; font-size: 0.9rem; }
        .order-link:hover { text-decoration: underline; }
        
        /* КОРЗИНА - КРАСИВОЕ ОФОРМЛЕНИЕ */
        .cart-items { margin-bottom: 20px; }
        
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: white;
            border-radius: 20px;
            margin-bottom: 15px;
            border: 1px solid #f8f1ee;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            box-shadow: 0 8px 25px rgba(216, 115, 127, 0.08);
            transform: translateY(-2px);
            border-color: #fce9e9;
        }

        .cart-item-checkbox {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cart-item-checkbox input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #d8737f;
        }

        .cart-item-img {
            width: 100px;
            height: 100px;
            border-radius: 18px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 2px solid white;
        }

        .cart-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 1.15rem;
            color: #333;
            margin-bottom: 2px;
        }

        .item-details-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
        }

        .detail-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #fdf2f2;
            color: #d8737f;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 500;
        }

        .tag-wishes {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .cart-item-price {
            font-weight: 700;
            color: #d8737f;
            font-size: 1.25rem;
            margin: 5px 0;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 1px solid #f0e8e0;
            background: white;
            color: #d8737f;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .quantity-btn:hover {
            background: #d8737f;
            color: white;
            border-color: #d8737f;
        }

        .qty-num {
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .remove-btn {
            background: #fef2f2;
            border: none;
            color: #ff6b6b;
            padding: 8px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            margin-left: auto;
        }

        .remove-btn:hover {
            background: #ff6b6b;
            color: white;
        }

        .cart-footer {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .selected-total {
            font-weight: 700;
            color: #d8737f;
            font-size: 1.6rem;
        }

        .checkout-btn {
            background: #d8737f;
            color: white;
            border: none;
            padding: 16px 35px;
            border-radius: 35px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.4s;
            width: 100%;
            font-size: 1.1rem;
            box-shadow: 0 6px 20px rgba(216, 115, 127, 0.2);
        }

        .checkout-btn:hover {
            background: #c76571;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(216, 115, 127, 0.3);
        }

        .clear-selected-btn {
            background: none;
            color: #999;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .clear-selected-btn:hover {
            color: #ff6b6b;
            border-color: #ff6b6b;
        }

        .nav-btn { 
            background: white; 
            color: #d8737f; 
            padding: 8px 20px; 
            border-radius: 30px; 
            text-decoration: none; 
            border: 1px solid #d8737f; 
            transition: 0.3s; 
        }
        
        .nav-btn:hover { 
            background: #d8737f; 
            color: white; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 60px; 
            color: #999; 
        }
        
        .empty-state i { 
            font-size: 4rem; 
            color: #ddd; 
            margin-bottom: 20px; 
        }
        
        .empty-state p { 
            margin-bottom: 20px; 
        }
        
        .order-btn { 
            background: #d8737f; 
            color: white; 
            border: none; 
            padding: 12px 25px; 
            border-radius: 30px; 
            text-decoration: none; 
            display: inline-block; 
            transition: 0.3s; 
        }
        
        .order-btn:hover { 
            background: #c76571; 
            transform: translateY(-2px); 
        }
        
        .settings-form { max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 12px; font-family: 'Montserrat', sans-serif; }
        .form-group input:focus { outline: none; border-color: #d8737f; }
        .save-btn { background: #d8737f; color: white; border: none; padding: 12px 30px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .save-btn:hover { background: #c76571; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) { 
            .profile-grid { grid-template-columns: 1fr; }
            .cart-item { flex-direction: column; text-align: center; }
            .cart-item-checkbox { position: absolute; top: 15px; left: 15px; }
            .remove-btn { margin: 0 auto; }
            .cart-item-quantity { justify-content: center; }
            .item-details-tags { justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="site-header">
    <div class="header-inner">
        <button class="burger" aria-label="Открыть меню" id="burgerBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <a href="index.php" class="logo">city tort</a>

        <div class="header-actions">
            <a class="instagram" href="https://instagram.com" target="_blank" rel="noreferrer" aria-label="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <button class="login"><?= htmlspecialchars($_SESSION['user_name']) ?></button>
        </div>
    </div>
</header>

<!-- Меню и затемнение -->
<nav class="side-menu" id="sideMenu">
    <button class="close-menu" id="closeMenuBtn" aria-label="Закрыть меню">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <ul>
        <li class="menu-title has-submenu">
            <button class="submenu-toggle" id="submenuBtn">
                АССОРТИМЕНТ <i class="fa-solid fa-chevron-down"></i>
            </button>
            <ul class="submenu" id="submenu">
                <li><a href="classic.php">Классические торты</a></li>
                <li><a href="bento.php">Бенто</a></li>
                <li><a href="desert.php">Десерты</a></li>
            </ul>
        </li>
        <li><a href="index.php#reviews">Отзывы</a></li>
        <li><a href="index.php#delivery">Доставка</a></li>
        <li><a href="index.php#map">Контакты</a></li>
    </ul>

    <div class="menu-phone">
        <a href="tel:+375297523044">+375 (29) 752-30-44</a>
    </div>
</nav>

<div class="overlay" id="overlay"></div>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Личный кабинет <span><?= htmlspecialchars($user['full_name']) ?></span></h1>
            <a href="index.php" class="nav-btn"><i class="fa-solid fa-home"></i> На главную</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="user-avatar-large">
                    <i class="fa-solid fa-user-circle"></i>
                </div>
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                
<ul class="profile-menu">
    <li><a href="profile.php?tab=active" class="<?= $active_tab == 'active' ? 'active' : '' ?>" id="menu-active"><i class="fa-solid fa-box"></i> Активные заказы <span class="badge"><?= count($active_orders) ?></span></a></li>
    <li><a href="profile.php?tab=history" class="<?= $active_tab == 'history' ? 'active' : '' ?>" id="menu-history"><i class="fa-solid fa-clock-rotate-left"></i> История заказов <span class="badge"><?= count($completed_orders) ?></span></a></li>
    <li><a href="profile.php?tab=cart" class="<?= $active_tab == 'cart' ? 'active' : '' ?>" id="menu-cart"><i class="fa-solid fa-shopping-cart"></i> Корзина <span class="badge"><?= count($cart_items) ?></span></a></li>
    <li><a href="profile.php?tab=settings" class="<?= $active_tab == 'settings' ? 'active' : '' ?>" id="menu-settings"><i class="fa-solid fa-gear"></i> Настройки</a></li>
</ul>
            </div>

            <div class="profile-content">
                <!-- Активные заказы -->
                <div id="section-active" class="profile-section <?= $active_tab == 'active' ? 'active' : '' ?>">

                    <h2 class="section-title">Активные заказы</h2>
                    
                    <?php if (empty($active_orders)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            <p>У вас нет активных заказов</p>
                            <a href="classic.php" class="order-btn">Перейти к покупкам</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-grid">
                            <?php foreach ($active_orders as $order): ?>
                                <div class="order-card status-<?= $order['status'] ?>">
                                    <div class="order-header">
                                        <span class="order-number">#<?= htmlspecialchars($order['order_number']) ?></span>
                                        <span class="order-date"><?= date('d.m.Y', strtotime($order['created_at'])) ?></span>
                                    </div>
                                    <span class="order-status" style="background: <?= $status_colors[$order['status']] ?>">
                                        <?= $status_names[$order['status']] ?>
                                    </span>
                                    <div class="order-total"><?= number_format($order['total_amount'], 2) ?> BYN</div>
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="order-link">Подробнее <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- История заказов -->
<div id="section-history" class="profile-section <?= $active_tab == 'history' ? 'active' : '' ?>">

                    <h2 class="section-title">История заказов</h2>
                    
                    <?php if (empty($completed_orders)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <p>У вас пока нет завершённых заказов</p>
                            <a href="classic.php" class="order-btn">Перейти к покупкам</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-grid">
                            <?php foreach ($completed_orders as $order): ?>
                                <div class="order-card status-<?= $order['status'] ?>">
                                    <div class="order-header">
                                        <span class="order-number">#<?= htmlspecialchars($order['order_number']) ?></span>
                                        <span class="order-date"><?= date('d.m.Y', strtotime($order['created_at'])) ?></span>
                                    </div>
                                    <span class="order-status" style="background: <?= $status_colors[$order['status']] ?>">
                                        <?= $status_names[$order['status']] ?>
                                    </span>
                                    <div class="order-total"><?= number_format($order['total_amount'], 2) ?> BYN</div>
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="order-link">Подробнее <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- КОРЗИНА - ОБНОВЛЕННАЯ КРАСИВАЯ СЕКЦИЯ -->
<div id="section-cart" class="profile-section <?= $active_tab == 'cart' ? 'active' : '' ?>">
                    <h2 class="section-title">Корзина</h2>
                    
                    <?php if (empty($cart_items)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <p>Ваша корзина пуста</p>
                            <a href="classic.php" class="order-btn">Перейти к покупкам</a>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding: 0 10px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600;">
                                <input type="checkbox" id="selectAll" checked onchange="toggleAll()" style="width:18px;height:18px;accent-color:#d8737f;"> Выбрать все
                            </label>
                            <span id="selectedCount" style="color:#d8737f; font-weight:600;">Выбрано: <?= count($cart_items) ?></span>
                        </div>
                        
                        <div class="cart-items" id="cartItems">
                            <?php foreach ($cart_items as $item): 
                                $display_name = $item['name'];
                                $display_img = $item['image_url'] ?? 'img/default.jpg';
                                
                                if ($item['product_type'] == 'bento' && !empty($item['selected_options'])) {
                                    $options = json_decode($item['selected_options'], true);
                                    $size = $options['size'] ?? 'M';
                                    $size_t = $size == 'S' ? 'Маленький' : ($size == 'L' ? 'Большой' : 'Средний');
                                    $bisc = $options['biscuit'] ?? 'vanilla';
                                    $b_text = $bisc == 'choco' ? 'шоколадный' : 'ванильный';
                                    $fill = $options['filling'] ?? 'berry';
                                    $f_map = ['berry' => 'ягодная', 'caramel' => 'карамельная', 'snickers' => 'сникерс'];
                                    $f_text = $f_map[$fill] ?? 'ягодная';
                                    $display_name = "Бенто-торт $size_t ($b_text, $f_text)";
                                    
                                    $img_key = $bisc . '_' . $fill;
                                    $display_img = $bento_images[$img_key] ?? $bento_images['default'];
                                }
                            ?>
                                <div class="cart-item" id="cart-item-<?= $item['id'] ?>">
                                    <div class="cart-item-checkbox">
                                        <input type="checkbox" class="item-checkbox" data-id="<?= $item['id'] ?>" data-price="<?= $item['total_price'] ?>" checked onchange="updateSelectedTotal()">
                                    </div>
                                    
                                    <img src="<?= htmlspecialchars($display_img) ?>" alt="" class="cart-item-img">
                                    
                                    <div class="cart-item-info">
                                        <div class="cart-item-name"><?= htmlspecialchars($display_name) ?></div>
                                        
                                        <div class="item-details-tags">
                                            <?php if ($item['weight_kg']): ?>
                                                <span class="detail-tag"><i class="fa-solid fa-weight-scale"></i> <?= $item['weight_kg'] ?> кг</span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['selected_options']) && $item['product_type'] == 'bento'): 
                                                $options = json_decode($item['selected_options'], true);
                                                if (!empty($options['decor']) && $options['decor'] != 'Без надписи/короткая надпись'): ?>
                                                <span class="detail-tag"><i class="fa-solid fa-wand-magic-sparkles"></i> <?= htmlspecialchars($options['decor']) ?></span>
                                            <?php endif; endif; ?>
                                            
                                            <?php if (!empty($item['wishes'])): ?>
                                                <span class="detail-tag tag-wishes"><i class="fa-solid fa-quote-left"></i> <?= htmlspecialchars($item['wishes']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="cart-item-price"><?= number_format($item['total_price'], 2) ?> BYN</div>
                                        
                                        <div class="cart-item-quantity">
                                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">−</button>
                                            <span class="qty-num" id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                                            <button class="remove-btn" onclick="removeFromCart(<?= $item['id'] ?>)" title="Удалить"><i class="fa-solid fa-trash-can"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-footer">
                            <div class="cart-total-row">
                                <span style="font-weight:600; color:#666;">Итого к оплате:</span>
                                <span class="selected-total" id="selectedTotal"><?= number_format(array_sum(array_column($cart_items, 'total_price')), 2) ?> BYN</span>
                            </div>
                            <button class="checkout-btn" onclick="checkoutSelected()"><i class="fa-solid fa-truck-fast"></i> Оформить заказ</button>
                            <center><button class="clear-selected-btn" onclick="clearSelected()">Очистить выбранное</button></center>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Настройки -->
<div id="section-settings" class="profile-section <?= $active_tab == 'settings' ? 'active' : '' ?>">
                    <h2 class="section-title">Настройки профиля</h2>
                    
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label>Имя и фамилия</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Новый пароль (оставьте пустым, если не хотите менять)</label>
                            <input type="password" name="new_password" placeholder="••••••••">
                        </div>
                        
                        <button type="submit" name="update_profile" class="save-btn">
                            <i class="fa-solid fa-save"></i> Сохранить изменения
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script src="script.js"></script>
    <script src="notifications.js"></script>
    <script>
function showSection(section) {
    // Обновляем URL без перезагрузки страницы
    const url = new URL(window.location);
    url.searchParams.set('tab', section);
    window.history.pushState({}, '', url);
    
    // Показываем нужную секцию
    document.querySelectorAll('.profile-section').forEach(el => el.classList.remove('active'));
    document.getElementById('section-' + section).classList.add('active');
    
    // Подсвечиваем пункт меню
    document.querySelectorAll('.profile-menu a').forEach(el => el.classList.remove('active'));
    document.getElementById('menu-' + section).classList.add('active');
}
        
        function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;
            
            fetch('cart_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update&cart_id=' + cartId + '&quantity=' + newQuantity
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
            });
        }
        
        function removeFromCart(cartId) {
            if (confirm('Удалить товар из корзины?')) {
                fetch('cart_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=remove&cart_id=' + cartId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) location.reload();
                });
            }
        }
        
        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            
            updateSelectedTotal();
        }
        
        function updateSelectedTotal() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            let total = 0;
            
            checkboxes.forEach(cb => {
                total += parseFloat(cb.dataset.price);
            });
            
            document.getElementById('selectedTotal').textContent = total.toFixed(2) + ' BYN';
            document.getElementById('selectedCount').textContent = 'Выбрано: ' + checkboxes.length;
            
            const selectAll = document.getElementById('selectAll');
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            selectAll.checked = (checkboxes.length === allCheckboxes.length);
        }
        
        function checkoutSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            
            if (checkboxes.length === 0) {
                showNotification('Выберите товары для оформления', 'info');
                return;
            }
            
            const selectedIds = [];
            checkboxes.forEach(cb => {
                selectedIds.push(cb.dataset.id);
            });
            
            window.location.href = 'checkout.php?selected=1&ids=' + selectedIds.join(',');
        }

        function clearSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            
            if (checkboxes.length === 0) {
                return;
            }
            
            if (confirm('Удалить выбранные товары из корзины?')) {
                const selectedIds = [];
                checkboxes.forEach(cb => {
                    selectedIds.push(cb.dataset.id);
                });
                
                fetch('cart_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=remove_selected&cart_ids=' + selectedIds.join(',')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Выбранные товары удалены', 'success');
                        location.reload();
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }
        window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'active';
    
    document.querySelectorAll('.profile-section').forEach(el => el.classList.remove('active'));
    document.getElementById('section-' + tab).classList.add('active');
    
    document.querySelectorAll('.profile-menu a').forEach(el => el.classList.remove('active'));
    document.getElementById('menu-' + tab).classList.add('active');
});
        // Инициализация
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectedTotal);
        });
    </script>
</body>
</html>