<?php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';
require_once 'order_functions.php';
date_default_timezone_set('Europe/Minsk');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверяем, пришли ли мы с выбранными товарами
$selected_only = isset($_GET['selected']) && $_GET['selected'] == 1;
$selected_ids = [];

if ($selected_only) {
    if (isset($_GET['ids'])) {
        $selected_ids = explode(',', $_GET['ids']);
    } elseif (isset($_SESSION['checkout_items'])) {
        $selected_ids = $_SESSION['checkout_items'];
        unset($_SESSION['checkout_items']);
    }
    
    if (empty($selected_ids)) {
        header('Location: profile.php#cart');
        exit;
    }
    
    $cart_items = getSelectedCartItems($user_id, $selected_ids);
} else {
    $cart_items = getCart($user_id);
}

if (empty($cart_items)) {
    header('Location: profile.php#cart');
    exit;
}

$selected_ids_str = implode(',', array_column($cart_items, 'id'));

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$subtotal = array_sum(array_column($cart_items, 'total_price'));

// Считаем количество тортов (классических и бенто) в корзине
$current_cart_cakes = 0;
foreach ($cart_items as $item) {
    if ($item['product_type'] == 'classic_cake' || $item['product_type'] == 'bento') {
        $current_cart_cakes += $item['quantity'];
    }
}

$bento_images = [
    'vanilla_berry' => 'img/бенто-ваниль-ягода.jpg',
    'vanilla_caramel' => 'img/бенто-ваниль-карамель.jpg',
    'choco_berry' => 'img/бенто-шоколад-ягода.jpg',
    'choco_caramel' => 'img/бенто-шоколад-карамель.jpg',
    'choco_snickers' => 'img/бенто-сникерс.jpg',
    'default' => 'img/бенто1.jpg'
];

// Получаем минимальную дату для заказа
$min_order_date = getMinOrderDate();

// Генерируем время с шагом 10 минут
$times = [];
for ($h = 7; $h <= 23; $h++) {
    for ($m = 0; $m < 60; $m += 10) {
        $times[] = sprintf("%02d:%02d", $h, $m);
    }
}
$times[] = '00:00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_type = $_POST['delivery_type'] ?? 'pickup';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $delivery_date = $_POST['delivery_date'] ?? '';
    $delivery_time = $_POST['delivery_time'] ?? '';
    $customer_notes = $_POST['customer_notes'] ?? '';
    $selected_ids = explode(',', $_POST['selected_ids'] ?? '');
    
    $delivery_data = [
        'delivery_type' => $delivery_type,
        'payment_method' => $payment_method,
        'delivery_date' => $delivery_date,
        'delivery_time' => $delivery_time,
        'customer_notes' => $customer_notes
    ];
    
    if ($delivery_type == 'delivery') {
        $street = $_POST['street'] ?? '';
        $building = $_POST['building'] ?? '';
        $apartment = $_POST['apartment'] ?? '';
        $delivery_cost = floatval($_POST['delivery_cost_calculated'] ?? 0);
        
        if (empty($street) || empty($building)) {
            $error = 'Введите улицу и номер дома';
        } else {
            $delivery_address = "ул. $street, д. $building" . ($apartment ? ", кв. $apartment" : "");
            $delivery_data['delivery_address'] = $delivery_address;
            $delivery_data['delivery_cost'] = $delivery_cost;
        }
    }
    
    if (!isset($error)) {
        // Проверяем минимальную дату
        if (!canOrderOnDate($delivery_date)) {
            $error = 'Заказы принимаются минимум за 3 дня. Ближайшая доступная дата: ' . date('d.m.Y', strtotime($min_order_date));
        } else {
            // Проверяем количество тортов на дату
            $existing_cakes = getCakeCountOnDate($delivery_date);
            $total_cakes = $existing_cakes + $current_cart_cakes;
            
            if ($total_cakes > 5) {
                $available = 5 - $existing_cakes;
                if ($available <= 0) {
                    $error = 'На эту дату уже заказано максимальное количество тортов (5). Выберите другую дату.';
                } else {
                    $error = "На эту дату можно заказать только $available тортов. У вас в корзине $current_cart_cakes.";
                }
            }
        }
    }
    
    if (!isset($error)) {
        if ($selected_only) {
            $result = createOrderFromSelected($user_id, $selected_ids, $delivery_data);
        } else {
            $result = createOrder($user_id, $cart_items, $delivery_data);
        }
        
        if ($result['success']) {
            if ($payment_method == 'card') {
                header("Location: payment.php?order_id=" . $result['order_id']);
            } else {
                header("Location: order_success.php?order_id=" . $result['order_id']);
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { padding-top: 80px; background: #faf7f2; }
        .site-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .checkout-container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .checkout-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #d8737f; }
        .checkout-header h1 { font-size: 2rem; color: #333; }
        .checkout-header h1 span { color: #d8737f; }
        .date-info { background: #fff9e6; padding: 10px 20px; border-radius: 50px; color: #856404; font-size: 0.9rem; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        .checkout-form { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .form-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0e8e0; }
        .form-section h2 { font-size: 1.2rem; margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px; }
        .form-section h2 i { color: #d8737f; }
        
        .radio-group { display: flex; gap: 30px; margin: 15px 0; }
        .radio-label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 1rem; }
        .radio-label input[type="radio"] { width: 18px; height: 18px; accent-color: #d8737f; }
        
        .address-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 15px; border: 2px solid #f0e8e0; border-radius: 15px;
            font-family: 'Montserrat', sans-serif; font-size: 0.95rem; transition: 0.3s;
            background: white;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #d8737f; box-shadow: 0 0 0 3px rgba(216,115,127,0.1);
        }
        
        .date-time-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .pickup-info { background: #f9f9f9; padding: 20px; border-radius: 15px; margin: 15px 0; border-left: 4px solid #d8737f; }
        .pickup-info p { margin: 8px 0; display: flex; align-items: center; gap: 10px; }
        .pickup-info i { color: #d8737f; width: 20px; }
        
        .delivery-info { background: #f9f9f9; padding: 20px; border-radius: 15px; margin: 15px 0; }
        .delivery-cost-badge { 
            background: #d8737f; color: white; padding: 15px; border-radius: 12px;
            display: flex; justify-content: space-between; align-items: center;
            font-weight: 600; margin-top: 15px;
        }
        .delivery-cost-badge .price { font-size: 1.3rem; }
        
        .order-summary { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); position: sticky; top: 100px; }
        .summary-title { font-size: 1.2rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0e8e0; }
        .summary-items { max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px; }
        .summary-item { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0e8e0; }
        .summary-item-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
        .summary-item-info { flex: 1; min-width: 0; }
        .summary-item-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px; }
        .summary-item-name { font-weight: 600; font-size: 0.95rem; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .summary-item-price { font-weight: 600; color: #d8737f; font-size: 0.95rem; margin-left: 10px; }
        .summary-item-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 3px 0; }
        .summary-tag {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .summary-tag i { font-size: 0.65rem; }
        .summary-tag.weight { background: #f0f0f0; color: #666; }
        .summary-tag.decor { background: #fdf2f2; color: #d8737f; }
        .summary-tag.wishes { background: #fff9e6; color: #92400e; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .summary-tag.photo { background: #e8f4f8; color: #0c5460; }
        .summary-tag.qty { background: #f0f0f0; color: #666; }
        
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 0.95rem; }
        .summary-row.total { font-size: 1.3rem; font-weight: 600; color: #d8737f; padding-top: 15px; margin-top: 10px; border-top: 2px solid #f0e8e0; }
        
        .submit-btn { 
            background: #d8737f; color: white; border: none; padding: 15px 30px; 
            border-radius: 30px; font-weight: 600; cursor: pointer; width: 100%; 
            margin-top: 20px; transition: 0.3s; font-size: 1.1rem;
        }
        .submit-btn:hover { background: #c76571; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(216,115,127,0.3); }
        
        .error-message { background: #ffebee; color: #c62828; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .loading { opacity: 0.5; pointer-events: none; }
        
        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d8737f;
            border-top: none;
            z-index: 99;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
        }
        .suggestion-item:hover {
            background: #fce9e9;
            color: #d8737f;
        }
        
        .date-warning {
            font-size: 0.8rem;
            color: #856404;
            background: #fff9e6;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cake-limit-info {
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 10px;
            margin: 10px 0;
            font-size: 0.85rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cake-limit-info i {
            color: #d8737f;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) { 
            .checkout-grid { grid-template-columns: 1fr; }
            .address-row { grid-template-columns: 1fr; }
            .checkout-header { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <button class="burger">☰</button>
            <a href="index.php" class="logo">city tort</a>
            <div class="header-actions">
                <button class="login"><?= htmlspecialchars($_SESSION['user_name']) ?></button>
            </div>
        </div>
    </header>

    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Оформление <span>заказа</span></h1>
            <div class="date-info">
                <i class="fa-regular fa-calendar-check"></i> 
                Заказы принимаются минимум за 3 дня (с <?= date('d.m.Y', strtotime($min_order_date)) ?>)
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="checkout-grid" id="checkoutForm">
            <input type="hidden" name="selected_ids" value="<?= $selected_ids_str ?>">
            
            <div class="checkout-form">
                <!-- Способ получения -->
                <div class="form-section">
                    <h2><i class="fa-solid fa-truck"></i> Способ получения</h2>
                    
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="delivery_type" value="pickup" checked onchange="toggleDelivery()">
                            <span>Самовывоз</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="delivery_type" value="delivery" onchange="toggleDelivery()">
                            <span>Доставка</span>
                        </label>
                    </div>

                    <!-- Самовывоз -->
                    <div id="pickupBlock" class="pickup-info">
                        <p><i class="fa-solid fa-location-dot"></i> <strong>ул. Асфальтная 63А-24</strong></p>
                        <p><i class="fa-regular fa-clock"></i> Ежедневно с 10:00 до 20:00</p>
                    </div>

                    <!-- Доставка -->
                    <div id="deliveryBlock" style="display: none;">
                        <div class="address-row">
                            <div class="form-group" style="position: relative;">
                                <label>Улица *</label>
                                <input type="text" name="street" id="street" autocomplete="off" placeholder="Начните вводить улицу...">
                                <div id="autocomplete-list" class="autocomplete-suggestions"></div>
                            </div>
                            <div class="form-group">
                                <label>Дом *</label>
                                <input type="text" name="building" id="building" placeholder="15">
                            </div>
                            <div class="form-group">
                                <label>Квартира</label>
                                <input type="text" name="apartment" id="apartment" placeholder="42">
                            </div>
                        </div>
                        
                        <button type="button" class="submit-btn" style="margin: 10px 0; background: #666;" onclick="calculateDelivery()">
                            <i class="fa-solid fa-calculator"></i> Рассчитать доставку
                        </button>
                        
                        <div id="deliveryResult" style="display: none;"></div>
                        <input type="hidden" name="delivery_cost_calculated" id="delivery_cost" value="0">
                    </div>
                </div>

                <!-- Дата и время -->
                <div class="form-section">
                    <h2><i class="fa-regular fa-calendar"></i> Дата и время</h2>
                    
                    
                    <div class="date-time-row">
                        <div class="form-group">
                            <label>Дата *</label>
                            <input type="text" name="delivery_date" id="delivery_date" class="datepicker" required>
                        </div>
                        <div class="form-group">
                            <label>Время</label>
                            <select name="delivery_time">
                                <?php foreach ($times as $time): ?>
                                    <option value="<?= $time ?>"><?= $time ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="date-warning">
                        <i class="fa-regular fa-clock"></i>
                        Минимальный срок заказа - 3 дня. Выберите дату не ранее <?= date('d.m.Y', strtotime($min_order_date)) ?>
                    </div>
                </div>

                <!-- Оплата -->
                <div class="form-section">
                    <h2><i class="fa-solid fa-credit-card"></i> Оплата</h2>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <span>Наличными</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="payment_method" value="card">
                            <span>Картой онлайн</span>
                        </label>
                    </div>
                </div>

                <!-- Комментарий -->
                <div class="form-section">
                    <h2><i class="fa-regular fa-note-sticky"></i> Комментарий</h2>
                    <div class="form-group">
                        <label>Ваши пожелания или комментарий</label>
                        <textarea name="customer_notes" rows="4" placeholder="Например: надпись на торте, предпочтения по декору или детали доставки..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Корзина (Ваш заказ) - КОМПАКТНАЯ ВЕРСИЯ -->
            <div class="order-summary">
                <h2 class="summary-title">Ваш заказ</h2>
                <div class="summary-items">
                    <?php foreach ($cart_items as $item): 
                        $display_name = $item['name'];
                        $display_img = $item['image_url'] ?? 'img/default.jpg';

                        if ($item['product_type'] == 'bento' && !empty($item['selected_options'])) {
                            $options = json_decode($item['selected_options'], true);
                            $size = $options['size'] ?? 'M';
                            $size_t = ($size == 'S' ? 'Маленький' : ($size == 'L' ? 'Большой' : 'Средний'));
                            $display_name = "Бенто-торт $size_t";
                            
                            $img_key = ($options['biscuit'] ?? 'vanilla') . '_' . ($options['filling'] ?? 'berry');
                            $display_img = $bento_images[$img_key] ?? $bento_images['default'];
                        }
                    ?>
                        <div class="summary-item">
                            <img src="<?= htmlspecialchars($display_img) ?>" alt="" class="summary-item-img">
                            <div class="summary-item-info">
                                <div class="summary-item-header">
                                    <span class="summary-item-name"><?= htmlspecialchars($display_name) ?></span>
                                    <span class="summary-item-price"><?= number_format($item['total_price'], 2) ?> BYN</span>
                                </div>
                                
                                <div class="summary-item-tags">
                                    <?php if ($item['weight_kg']): ?>
                                        <span class="summary-tag weight">
                                            <i class="fa-solid fa-weight-scale"></i> <?= $item['weight_kg'] ?> кг
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['selected_options']) && $item['product_type'] == 'bento'): 
                                        $options = json_decode($item['selected_options'], true);
                                        if (!empty($options['decor']) && $options['decor'] != 'Без надписи/короткая надпись'): ?>
                                        <span class="summary-tag decor">
                                            <i class="fa-regular fa-star"></i> декор
                                        </span>
                                    <?php endif; endif; ?>
                                    
                                    <?php if (!empty($item['wishes'])): ?>
                                        <span class="summary-tag wishes" title="<?= htmlspecialchars($item['wishes']) ?>">
                                            <i class="fa-regular fa-note-sticky"></i> <?= htmlspecialchars(mb_substr($item['wishes'], 0, 20)) ?><?= mb_strlen($item['wishes']) > 20 ? '...' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['design_image'])): ?>
                                        <span class="summary-tag photo">
                                            <i class="fa-regular fa-image"></i> фото
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="summary-tag qty">
                                        <?= $item['quantity'] ?> шт
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Товары:</span>
                    <span><?= number_format($subtotal, 2) ?> BYN</span>
                </div>

                <div class="summary-row" id="deliveryCostRow" style="display: none;">
                    <span>Доставка:</span>
                    <span id="deliveryCostDisplay">0 BYN</span>
                </div>

                <div class="summary-row total">
                    <span>Итого:</span>
                    <span id="totalAmount"><?= number_format($subtotal, 2) ?> BYN</span>
                </div>

                <button type="submit" class="submit-btn">Подтвердить заказ</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="notifications.js"></script>
    <script>
// Минимальная дата для заказа
const minDate = '<?= $min_order_date ?>';

// Инициализация календаря
flatpickr(".datepicker", {
    locale: "ru",
    minDate: minDate,
    maxDate: new Date().fp_incr(90), // +3 месяца
    dateFormat: "Y-m-d",
    defaultDate: minDate, // Устанавливаем минимальную дату по умолчанию
    disableMobile: true, // Отключаем мобильный режим
    onReady: function(selectedDates, dateStr, instance) {
        // Убеждаемся что минимальная дата доступна
        console.log('Min date:', minDate);
    }
});
        function toggleDelivery() {
            const type = document.querySelector('input[name="delivery_type"]:checked').value;
            const pickupBlock = document.getElementById('pickupBlock');
            const deliveryBlock = document.getElementById('deliveryBlock');
            const deliveryCostRow = document.getElementById('deliveryCostRow');
            const totalAmountSpan = document.getElementById('totalAmount');
            const subtotal = <?= (float)($subtotal ?? 0) ?>;

            if (type === 'pickup') {
                pickupBlock.style.display = 'block';
                deliveryBlock.style.display = 'none';
                deliveryCostRow.style.display = 'none';
                totalAmountSpan.textContent = subtotal.toFixed(2) + ' BYN';
                document.getElementById('delivery_cost').value = 0;
            } else {
                pickupBlock.style.display = 'none';
                deliveryBlock.style.display = 'block';
            }
        }

        // Автопоиск улиц
        const streetInput = document.getElementById('street');
        const suggestionsList = document.getElementById('autocomplete-list');

        if (streetInput) {
            streetInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (val.length < 2) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                fetch('get_streets.php?term=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(data => {
                        suggestionsList.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(street => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = street;
                                div.onclick = function() {
                                    streetInput.value = street;
                                    suggestionsList.style.display = 'none';
                                };
                                suggestionsList.appendChild(div);
                            });
                            suggestionsList.style.display = 'block';
                        } else {
                            suggestionsList.style.display = 'none';
                        }
                    })
                    .catch(err => console.error('Ошибка поиска улиц:', err));
            });
        }

        document.addEventListener('click', function(e) {
            if (streetInput && e.target !== streetInput) {
                suggestionsList.style.display = 'none';
            }
        });

        function calculateDelivery() {
            const street = streetInput.value.trim();
            const building = document.getElementById('building').value.trim();
            const btn = event.target.closest('button');
            const resultDiv = document.getElementById('deliveryResult');
            const subtotal = <?= (float)($subtotal ?? 0) ?>;

            if (!street || !building) {
                showNotification('Пожалуйста, введите и улицу, и номер дома!', 'error');
                return;
            }

            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Считаем...';

            const fd = new FormData();
            fd.append('street', street);
            fd.append('building', building);

            fetch('calculate_delivery.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div style="background: #fdf2f2; padding: 15px; border-radius: 12px; border-left: 5px solid #d8737f; margin-top: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); animation: fadeIn 0.5s ease;">
                            <div style="font-size: 0.85rem; color: #888; margin-bottom: 5px;">📍 Адрес доставки:</div>
                            <div style="font-weight: 600; color: #333; margin-bottom: 10px;">${data.address}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 10px;">
                                <span style="font-size: 0.9rem;">Стоимость доставки:</span>
                                <strong style="color: #d8737f; font-size: 1.2rem;">${data.delivery_cost} BYN</strong>
                            </div>
                        </div>`;
                    resultDiv.style.display = 'block';

                    document.getElementById('delivery_cost').value = data.delivery_cost;
                    document.getElementById('deliveryCostRow').style.display = 'flex';
                    document.getElementById('deliveryCostDisplay').textContent = data.delivery_cost + ' BYN';
                    
                    const total = subtotal + parseFloat(data.delivery_cost);
                    document.getElementById('totalAmount').textContent = total.toFixed(2) + ' BYN';

                } else {
                    showNotification('Адрес не найден: ' + data.message, 'error');
                    resultDiv.style.display = 'none';
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error(err);
                showNotification('Не удалось связаться с сервером. Проверьте интернет.', 'error');
            });
        }

        // Валидация перед отправкой
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
            const deliveryCost = parseFloat(document.getElementById('delivery_cost').value);
            const date = document.getElementById('delivery_date').value;

            if (!date) {
                e.preventDefault();
                showNotification('Выберите дату получения!', 'error');
                return;
            }

            // Проверка минимальной даты
            const selectedDate = new Date(date);
            const minDateObj = new Date(minDate);
            if (selectedDate < minDateObj) {
                e.preventDefault();
                showNotification('Заказы принимаются минимум за 3 дня!', 'error');
                return;
            }

            if (deliveryType === 'delivery' && (isNaN(deliveryCost) || deliveryCost <= 0)) {
                e.preventDefault();
                showNotification('Пожалуйста, введите адрес и нажмите кнопку "Рассчитать доставку"!', 'warning');
            }
        });
    </script>
</body>
</html>