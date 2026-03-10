<?php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';

// ID товара-конструктора
$constructor_product_id = 9;

// Получаем размеры
$stmt = $pdo->prepare("SELECT * FROM product_options WHERE product_id = ? AND option_type = 'size' ORDER BY sort_order");
$stmt->execute([$constructor_product_id]);
$sizes = $stmt->fetchAll();

// Получаем бисквиты
$stmt = $pdo->prepare("SELECT * FROM product_options WHERE product_id = ? AND option_type = 'biscuit' ORDER BY sort_order");
$stmt->execute([$constructor_product_id]);
$biscuits = $stmt->fetchAll();

// Получаем начинки
$stmt = $pdo->prepare("SELECT * FROM product_options WHERE product_id = ? AND option_type = 'filling' ORDER BY sort_order");
$stmt->execute([$constructor_product_id]);
$fillings = $stmt->fetchAll();

// Получаем декор
$stmt = $pdo->prepare("SELECT * FROM product_options WHERE product_id = ? AND option_type = 'decor' ORDER BY sort_order");
$stmt->execute([$constructor_product_id]);
$decor_options = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>City Tort — Бенто торты</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="notifications.css">
  <style>
    .choice-btn.active {
      background: #d8737f !important;
      border-color: #d8737f !important;
      color: white !important;
    }
    .cake-layer {
      background-size: cover !important;
      background-position: center !important;
      background-repeat: no-repeat !important;
    }
    .animate-drop {
      animation: dropDown 0.6s ease forwards;
    }
    @keyframes dropDown {
      0% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
      100% { transform: translateX(-50%) translateY(0); opacity: 1; }
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
      <button class="login">Войти</button>
    </div>
  </div>
</header>

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
        <li><a href="clasic.php">Классические торты</a></li>
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

<!-- ИНФОРМАЦИОННЫЙ БЛОК -->
<section class="bento-section">
  <div class="bento-container">
    <h2 class="cakes-title">Бенто-торты</h2>
    <p class="cakes-subtitle">Милые мини-торты для особенных моментов</p>
    <div class="bento-sizes">
      <?php foreach ($sizes as $size): ?>
      <div class="bento-size">
        <div class="bento-circle"><?= $size['name'] == 'S' ? '10 см' : ($size['name'] == 'M' ? '12 см' : '16 см') ?></div>
        <h4>Bento <?= $size['name'] ?></h4>
        <p class="grams"><?= $size['name'] == 'S' ? '450–500 г' : ($size['name'] == 'M' ? '700–800 г' : '1000–1200 г') ?></p>
        <p class="price"><?= $size['price_modifier'] ?> BYN</p>
        <p class="people"><?= $size['name'] == 'S' ? 'на 1–2 человек' : ($size['name'] == 'M' ? 'на 3–4 человек' : 'до 6 человек') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bento-flavors">
      <?php foreach ($biscuits as $biscuit): ?>
      <div class="flavor">
        <h3><?= $biscuit['name'] ?></h3>
        <p><?= $biscuit['name'] ?> бисквит, 
          <?php 
            if ($biscuit['name'] == 'Ванильный') {
                $available = array_filter($fillings, fn($f) => $f['name'] != 'Сникерс');
                echo implode(' / ', array_column($available, 'name'));
            } else {
                echo implode(' / ', array_column($fillings, 'name'));
            }
          ?>, крем-чиз на сливках
        </p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bento-gallery">
      <img src="img/бенто1.jpg" alt=""><img src="img/бенто2.jpg" alt=""><img src="img/бенто3.jpg" alt=""><img src="img/бенто4.jpg" alt=""><img src="img/бенто5.jpg" alt="">
    </div>
  </div>
</section>

<!-- КОНСТРУКТОР -->
<section class="bento-builder">
  <div class="builder-container">
    <div class="builder-left">
      <h2>Собери свой бенто-торт</h2>
      <div class="builder-choices">
        <div class="choice-group">
          <h4>Выбери размер:</h4>
          <div class="choice-options" id="sizeOptions">
            <?php foreach ($sizes as $size): ?>
            <button data-value="<?= $size['name'] ?>" data-price="<?= $size['price_modifier'] ?>" class="choice-btn size-btn <?= $size['name'] == 'M' ? 'active' : '' ?>"><?= $size['name'] ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="choice-group">
          <h4>Выбери бисквит:</h4>
          <div class="choice-options" id="biscuitOptions">
            <?php foreach ($biscuits as $index => $biscuit): ?>
            <button data-value="<?= $biscuit['name'] == 'Ванильный' ? 'vanilla' : 'choco' ?>" class="choice-btn biscuit-btn <?= $index == 0 ? 'active' : '' ?>"><?= $biscuit['name'] ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="choice-group">
          <h4>Выбери начинку:</h4>
          <div class="choice-options" id="fillingOptions">
            <?php 
            $filling_map = ['Ягодная' => 'berry', 'Карамельная' => 'caramel', 'Карамель' => 'caramel', 'Сникерс' => 'snickers'];
            foreach ($fillings as $index => $filling): 
                $val = $filling_map[$filling['name']] ?? 'berry';
            ?>
            <button data-value="<?= $val ?>" class="choice-btn filling-btn <?= $index == 0 ? 'active' : '' ?>"><?= $filling['name'] ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="order-btn" id="orderBtn">Заказать</button>
      </div>
    </div>
    <div class="builder-right">
      <div class="cake-preview" id="cakePreview">
        <div class="cake-layer cake-base"></div>
        <div class="cake-layer cake-cream"></div>
        <div class="cake-layer cake-filling"></div>
        <div class="cake-layer cake-top"></div>
      </div>
    </div>
  </div>
</section>

<!-- МОДАЛКА ЗАКАЗА -->
<div class="modal" id="orderModal">
  <div class="modal-content modal-order">
    <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
    <h3>Оформление заказа</h3>
    <div id="orderSummary" class="order-summary-text"></div>
    <p>Выберите декор:</p>
    <select id="decorSelect">
      <?php foreach ($decor_options as $decor): ?>
      <option value="<?= $decor['name'] ?>" data-price="<?= $decor['price_modifier'] ?>">
        <?= htmlspecialchars($decor['name']) ?><?= $decor['price_modifier'] > 0 ? ' (+' . $decor['price_modifier'] . ' BYN)' : '' ?>
      </option>
      <?php endforeach; ?>
    </select>
    <textarea placeholder="Ваши пожелания..." id="orderWishes"></textarea>
    <div class="file-upload">
      <label>Прикрепить фото примера:
        <input type="file" accept="image/*" id="orderFile">
      </label>
    </div>
    <div class="order-price" id="orderPrice">Итого: 55 BYN</div>
    <button class="add-to-cart" id="addToCartBtn">Добавить в корзину</button>
  </div>
</div>

<section class="help-section">
  <div class="help-content">
    <h2 class="help-title fade-in-up">НУЖНА ПОМОЩЬ С ВЫБОРОМ?</h2>
    <p class="help-text fade-in-up">Чат в Telegram с нашим кондитером</p>
    <a href="https://t.me/cyti_tort" target="_blank" class="help-btn fade-in-up">Написать в Telegram</a>
  </div>
</section>

<footer class="footer">
  <div class="footer-inner">
    <div class="footer-column footer-left">
      <div class="footer-logo"><img src="img/лого.png" alt="City Tort"></div>
      <div class="footer-socials">
        <a href="https://t.me/cyti_tort" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
        <a href="https://www.instagram.com/city_tort_?igsh=MWNvcHV5cTB5cHdqdg==" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
      </div>
      <p class="footer-info">ИНН 165118521086<br>ОГРНИП 315165100002472</p>
    </div>
    <div class="footer-column">
      <h3>АССОРТИМЕНТ</h3>
      <ul>
        <li><a href="classic.php">Классические торты</a></li>
        <li><a href="bento.php">Бенто</a></li>
        <li><a href="desert.php">Десерты</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>КЛИЕНТАМ</h3>
      <ul>
        <li><a href="index.php#reviews">Отзывы</a></li>
        <li><a href="index.php#delivery">Доставка</a></li>
        <li><a href="index.php#map">Контакты</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>ИНФОРМАЦИЯ</h3>
      <ul>
        <li><a href="#">Договор-оферта</a></li>
        <li><a href="#">Политика обработки персональных данных</a></li>
        <li><a href="#">Правила оплаты и безопасность платежей</a></li>
        <li><a href="#">Правила возврата товара</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <img src="img/visa.png" alt="VISA" class="payment-icon">
    <img src="img/mastercard.png" alt="MasterCard" class="payment-icon">
  </div>
</footer>
<script src="script.js"></script>
<script src="auth.js"></script>
<script src="notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cakePreview = document.getElementById('cakePreview');
    const orderBtn = document.getElementById('orderBtn');
    const orderModal = document.getElementById('orderModal');
    const orderSummary = document.getElementById('orderSummary');
    const orderPriceElem = document.getElementById('orderPrice');
    const decorSelect = document.getElementById('decorSelect');
    const addToCartBtn = document.getElementById('addToCartBtn');

    // Устанавливаем начальные изображения
    const base = document.querySelector('.cake-base');
    const top = document.querySelector('.cake-top');
    const filling = document.querySelector('.cake-filling');
    if (base && top && filling) {
        base.style.backgroundImage = "url('img/ваниль.png')";
        top.style.backgroundImage = "url('img/ваниль.png')";
        filling.style.backgroundImage = "url('img/начягод.png')";
        base.style.opacity = top.style.opacity = filling.style.opacity = '1';
    }

    let selected = {
        size: 'M', sizePrice: 55,
        biscuit: 'vanilla',
        filling: 'berry',
        decor: 'Без надписи/короткая надпись', decorPrice: 0
    };

    // Размер
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selected.size = btn.dataset.value;
            selected.sizePrice = parseFloat(btn.dataset.price);
        });
    });

    // Бисквит
    document.querySelectorAll('.biscuit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.biscuit-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selected.biscuit = btn.dataset.value;
            const img = selected.biscuit === 'choco' ? 'img/шоко.png' : 'img/ваниль.png';
            [base, top].forEach(el => {
                if (el) {
                    el.style.opacity = '0';
                    setTimeout(() => {
                        el.style.backgroundImage = `url('${img}')`;
                        el.classList.add('animate-drop');
                        el.style.opacity = '1';
                        setTimeout(() => el.classList.remove('animate-drop'), 800);
                    }, 200);
                }
            });
        });
    });

    // Начинка
    document.querySelectorAll('.filling-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filling-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selected.filling = btn.dataset.value;
            let img = '';
            if (selected.filling === 'berry') img = 'img/начягод.png';
            else if (selected.filling === 'caramel') img = 'img/карам.png';
            else if (selected.filling === 'snickers') img = 'img/начсникер.png';
            if (filling && img) {
                filling.style.opacity = '0';
                setTimeout(() => {
                    filling.style.backgroundImage = `url('${img}')`;
                    filling.classList.add('animate-drop');
                    filling.style.opacity = '1';
                    setTimeout(() => filling.classList.remove('animate-drop'), 800);
                }, 200);
            }
        });
    });

    // Декор
    decorSelect.addEventListener('change', function() {
        selected.decor = this.value;
        selected.decorPrice = parseFloat(this.options[this.selectedIndex].dataset.price || 0);
        updatePrice();
    });

    function updatePrice() {
        const total = selected.sizePrice + selected.decorPrice;
        orderPriceElem.textContent = `Итого: ${total} BYN`;
    }

    // ✅ ИСПРАВЛЕННАЯ ФУНКЦИЯ updateSummary
    function updateSummary() {
        const sizeText = selected.size === 'S' ? 'Маленький' : (selected.size === 'M' ? 'Средний' : 'Большой');
        const biscuitText = selected.biscuit === 'choco' ? 'шоколадный бисквит' : 'ванильный бисквит';
        let fillingText = '';
        if (selected.filling === 'berry') fillingText = 'ягодной начинкой';
        else if (selected.filling === 'caramel') fillingText = 'карамельной начинкой';
        else if (selected.filling === 'snickers') fillingText = 'начинкой Сникерс';
        
        orderSummary.textContent = `${sizeText} бенто-торт на ${biscuitText} с ${fillingText}. Декор: ${selected.decor}`;
        updatePrice();
    }

// Кнопка "Заказать" - ПОСЛЕДНЯЯ НАДЕЖДА
orderBtn.addEventListener("click", function() {
    if (typeof window.requireAuth === 'function') {
        // 1. Сначала проверяем авторизацию
        if (!window.auth || !window.auth.currentUser) {
            // Если не авторизован - показываем ТОЛЬКО модалку входа
            window.auth.showAuthModalWithMessage('Чтобы собрать свой бенто-торт, пожалуйста, войдите в аккаунт');
            return; // ТУТ ВАЖНО! Выходим, ничего больше не делаем
        }
        
        // 2. Если авторизован - открываем модалку заказа
        updateSummary();
        orderModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
});

    // Закрытие модалки
    document.querySelector('#orderModal .close-modal').addEventListener('click', () => {
        orderModal.classList.remove('active');
        document.body.style.overflow = '';
    });

    // Добавление в корзину
    addToCartBtn.addEventListener('click', function() {
        window.requireAuth(function() {
            const totalPrice = selected.sizePrice + selected.decorPrice;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', <?= $constructor_product_id ?>);
            formData.append('product_type', 'bento');
            formData.append('quantity', 1);
            formData.append('unit_price', totalPrice);
            formData.append('total_price', totalPrice);
            formData.append('wishes', document.getElementById('orderWishes').value);
            formData.append('selected_options', JSON.stringify({
                size: selected.size,
                biscuit: selected.biscuit,
                filling: selected.filling,
                decor: selected.decor
            }));

            const fileInput = document.getElementById('orderFile');
            if (fileInput && fileInput.files[0]) {
                formData.append('design_file', fileInput.files[0]);
            }

            fetch('cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Бенто-торт добавлен в корзину!', 'success');
                    orderModal.classList.remove('active');
                    document.body.style.overflow = '';
                    if (window.auth && window.auth.getCartCount) {
                        window.auth.getCartCount();
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Ошибка при добавлении в корзину', 'error');
            });
        });
    });

    // Логика совместимости начинок
    const biscuitBtns = document.querySelectorAll('.biscuit-btn');
    const fillingBtns = document.querySelectorAll('.filling-btn');
    
    function updateAvailableFillings(selectedBiscuit) {
        fillingBtns.forEach(btn => {
            if (selectedBiscuit === 'vanilla' && btn.dataset.value === 'snickers') {
                btn.style.display = 'none';
                if (btn.classList.contains('active')) {
                    const firstAvailable = Array.from(fillingBtns).find(b => b.style.display !== 'none');
                    if (firstAvailable) firstAvailable.click();
                }
            } else {
                btn.style.display = '';
            }
        });
    }

    biscuitBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            updateAvailableFillings(this.dataset.value);
        });
    });
    
    const activeBiscuit = document.querySelector('.biscuit-btn.active');
    if (activeBiscuit) updateAvailableFillings(activeBiscuit.dataset.value);
});
</script>
</body>
</html>