<?php
session_start();
require_once 'db.php';
require_once 'order_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Получаем детали заказа
$order = getOrderDetails($order_id, $user_id);

if (!$order || $order['payment_method'] != 'card') {
    header('Location: profile.php#orders');
    exit;
}

// Если заказ уже оплачен
if ($order['payment_status'] == 'paid') {
    header("Location: order_success.php?order_id=$order_id");
    exit;
}

// Обработка возврата после оплаты
if (isset($_GET['success']) && $_GET['success'] == 1) {
    // Обновляем статус оплаты
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$order_id]);
    
    header("Location: order_success.php?order_id=$order_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата заказа | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .payment-icon {
            font-size: 4rem;
            color: #d8737f;
            margin-bottom: 20px;
        }
        
        .payment-title {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .payment-order {
            color: #666;
            margin-bottom: 30px;
        }
        
        .payment-amount {
            font-size: 2.5rem;
            font-weight: 600;
            color: #d8737f;
            margin-bottom: 40px;
        }
        
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .payment-method-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px solid #f0e8e0;
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
        }
        
        .payment-method-btn:hover {
            border-color: #d8737f;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(216,115,127,0.1);
        }
        
        .payment-method-icon {
            font-size: 2rem;
            color: #d8737f;
        }
        
        .payment-method-info {
            flex: 1;
            text-align: left;
        }
        
        .payment-method-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .payment-method-desc {
            font-size: 0.85rem;
            color: #999;
        }
        
        .payment-note {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 12px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #d8737f;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Имитация платёжной формы */
        .mock-payment {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 20px;
            margin-top: 20px;
        }
        
        .card-input {
            text-align: left;
            margin-bottom: 20px;
        }
        
        .card-input label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .card-input input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .pay-btn {
            background: #d8737f;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }
        
        .pay-btn:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-icon">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        
        <h1 class="payment-title">Оплата заказа</h1>
        <p class="payment-order">Заказ #<?= htmlspecialchars($order['order_number']) ?></p>
        
        <div class="payment-amount">
            <?= number_format($order['total_amount'], 2) ?> BYN
        </div>
        
        <!-- Выбор способа оплаты -->
        <div class="payment-methods">
            <a href="#" class="payment-method-btn" onclick="showCardForm('webpay')">
                <div class="payment-method-icon">
                    <i class="fa-regular fa-credit-card"></i>
                </div>
                <div class="payment-method-info">
                    <div class="payment-method-title">WebPay</div>
                    <div class="payment-method-desc">Оплата картами Visa, Mastercard</div>
                </div>
            </a>
            
            <a href="#" class="payment-method-btn" onclick="showCardForm('erip')">
                <div class="payment-method-icon">
                    <i class="fa-solid fa-barcode"></i>
                </div>
                <div class="payment-method-info">
                    <div class="payment-method-title">ЕРИП</div>
                    <div class="payment-method-desc">Оплата через систему ЕРИП</div>
                </div>
            </a>
        </div>
        
        <!-- Имитация формы оплаты (для теста) -->
        <div id="cardPaymentForm" style="display: none;">
            <div class="mock-payment">
                <h3 style="margin-bottom: 20px;">Введите данные карты</h3>
                
                <div class="card-input">
                    <label>Номер карты</label>
                    <input type="text" placeholder="**** **** **** ****" value="4111 1111 1111 1111">
                </div>
                
                <div class="card-row">
                    <div class="card-input">
                        <label>Срок</label>
                        <input type="text" placeholder="ММ/ГГ" value="12/25">
                    </div>
                    <div class="card-input">
                        <label>CVV</label>
                        <input type="text" placeholder="***" value="123">
                    </div>
                </div>
                
                <div class="card-input">
                    <label>Владелец карты</label>
                    <input type="text" placeholder="IVAN IVANOV" value="TEST USER">
                </div>
                
                <button class="pay-btn" onclick="processPayment()">
                    <i class="fa-solid fa-lock"></i> Оплатить <?= number_format($order['total_amount'], 2) ?> BYN
                </button>
                
                <p style="margin-top: 15px; font-size: 0.8rem; color: #999;">
                    <i class="fa-solid fa-shield"></i> 
                    Платеж защищён. Данные не сохраняются.
                </p>
            </div>
        </div>
        
        <div class="payment-note">
            <i class="fa-solid fa-info-circle"></i> 
            После оплаты вы будете перенаправлены на страницу с подтверждением заказа.
        </div>
        
        <a href="order_details.php?id=<?= $order_id ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Вернуться к заказу
        </a>
    </div>

    <script>
        function showCardForm(type) {
            document.getElementById('cardPaymentForm').style.display = 'block';
            // Здесь можно добавить логику для разных типов оплаты
        }
        
        function processPayment() {
            // Имитация успешной оплаты
            setTimeout(() => {
                window.location.href = 'payment.php?order_id=<?= $order_id ?>&success=1';
            }, 1500);
            
            // Показываем индикатор загрузки
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Обработка...';
            btn.disabled = true;
        }
    </script>
</body>
</html>