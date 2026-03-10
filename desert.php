<?php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';

// Получаем ID категории "Десерты"
$stmt = $pdo->query("SELECT id FROM categories WHERE name = 'Десерты'");
$category = $stmt->fetch();
$dessert_category_id = $category ? $category['id'] : 5;

// Получаем все десерты из products (product_type = 'simple')
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE (product_type = 'simple' OR category_id = ?) 
        AND is_available = 1
    ORDER BY id ASC
");
$stmt->execute([$dessert_category_id]);
$desserts = $stmt->fetchAll();

// Получаем данные для конструктора капкейков
$stmt = $pdo->query("SELECT * FROM cupcake_constructor ORDER BY type, sort_order");
$cupcake_data = [];
while ($row = $stmt->fetch()) {
    $cupcake_data[$row['type']][] = $row;
}
$bases = $cupcake_data['base'] ?? [];
$fillings = $cupcake_data['filling'] ?? [];
$toppings = $cupcake_data['topping'] ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>City Tort — Десерты</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">
  <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="notifications.css">
  <style>
    /* Дополнительные стили для конструктора */
    .filling-item.hidden { display: none !important; }
    .step.hidden { display: none !important; }
    .finish-btn.disabled { opacity: 0.5; pointer-events: none; }
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

<section class="desserts-section">
  <h2 class="cakes-title">Десерты</h2>
  <p class="cakes-subtitle">Порционные десерты, сочетающие в себе воздушные текстуры, насыщенные вкусы и визуальную эстетику</p>

  <div class="desserts-grid">
    <!-- === КАПКЕЙК-КОНСТРУКТОР === -->
    <div class="cupcake-card-wide cupcake-constructor">
      <div class="cupcake-wide-content">
        <div class="cupcake-left">
          <img src="img/кекс.jpg" alt="Капкейк" class="cupcake-img">
          <div class="cupcake-info-copy">
            <h3>Капкейк «Собери свой вкус»</h3>
            <p>Выберите основу, начинку и шапочку — и создайте свой идеальный десерт.</p>
            <span class="price">От 8 BYN / шт</span>
          </div>
        </div>
        
        <div class="cupcake-right">
          <div class="cupcake-info">
            <h3>Капкейк «Собери свой вкус»</h3>
            <p>Выберите основу, начинку и шапочку — и создайте свой идеальный десерт.</p>
            <span class="price">От 8 BYN / шт</span>
            <button class="build-btn">Собрать свой вкус</button>
          </div>

          <div class="cupcake-steps">
            <!-- Шаг 1: Основы -->
            <div class="step step-bases">
              <h4>Выберите основу:</h4>
              <div class="base-options">
                <?php foreach ($bases as $base): ?>
                <div class="base-item" data-base="<?= htmlspecialchars($base['name']) ?>">
                  <img src="<?= htmlspecialchars($base['image']) ?>" alt="<?= htmlspecialchars($base['name']) ?>">
                  <span><?= htmlspecialchars($base['name']) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Шаг 2: Начинки -->
            <div class="step step-fillings hidden">
              <h4>Выберите начинку:</h4>
              <div class="filling-options">
                <?php foreach ($fillings as $filling): ?>
                <div class="filling-item" 
                     data-filling="<?= htmlspecialchars($filling['name']) ?>" 
                     data-base="<?= htmlspecialchars($filling['base_for']) ?>">
                  <img src="<?= htmlspecialchars($filling['image']) ?>" alt="<?= htmlspecialchars($filling['name']) ?>">
                  <span><?= htmlspecialchars($filling['name']) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Шаг 3: Шапочки -->
            <div class="step step-topping hidden">
              <h4>Выберите шапочку:</h4>
              <div class="topping-options">
                <?php foreach ($toppings as $topping): ?>
                <div class="topping-item">
                  <img src="<?= htmlspecialchars($topping['image']) ?>" 
                       alt="<?= htmlspecialchars($topping['name']) ?>" 
                       data-top="<?= htmlspecialchars($topping['name']) ?>">
                  <span><?= htmlspecialchars($topping['name']) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
              <button class="finish-btn disabled" disabled>Оформить заказ</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- === ОСТАЛЬНЫЕ ДЕСЕРТЫ ИЗ БД === -->
    <?php foreach ($desserts as $dessert): ?>
    <div class="dessert-card" 
         data-id="<?= $dessert['id'] ?>"
         data-name="<?= htmlspecialchars($dessert['name']) ?>" 
         data-desc="<?= htmlspecialchars($dessert['description']) ?>" 
         data-price="<?= $dessert['base_price'] ?>" 
         data-min="1" 
         data-img1="<?= htmlspecialchars($dessert['image_url']) ?>" 
         data-img2="<?= htmlspecialchars($dessert['image_url']) ?>">
      <img src="<?= htmlspecialchars($dessert['image_url']) ?>" alt="<?= htmlspecialchars($dessert['name']) ?>" class="dessert-img">
      <div class="dessert-info">
        <h3><?= htmlspecialchars($dessert['name']) ?></h3>
        <p><?= htmlspecialchars($dessert['description']) ?></p>
        <span class="price"><?= $dessert['base_price'] ?> BYN / шт</span>
        <button class="order-btn">Заказать</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- МОДАЛЬНОЕ ОКНО -->
<div class="modal" id="dessertModal">
  <div class="modal-content modal-dessert">
    <button class="close-modal">&times;</button>
    <div class="modal-top">
      <div class="modal-images">
        <img src="" alt="Основное фото" id="modalImg1">
        <img src="" alt="Дополнительное фото" id="modalImg2">
      </div>
      
      <div class="modal-text">
        <h3 id="modalTitle"></h3>
        <p id="modalDesc"></p>
        
        <div class="order-details">
          <p>Минимум: <span id="modalMin">1</span> шт</p>
          <div class="price-line">
            <span id="modalPrice" class="price"></span>
          </div>
          
          <div class="quantity">
            <button id="minus">−</button>
            <input type="number" id="count" min="1" value="6">
            <button id="plus">+</button>
          </div>
          
          <p class="total">Итого: <span id="modalTotal"></span> BYN</p>
        </div>
        
        <textarea id="wishesText" placeholder="Ваши пожелания..."></textarea>
        <button class="add-to-cart" id="addToCartBtn">Добавить в корзину</button>
      </div>
    </div>
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
<script src="auth.js"></script>
<script src="notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== ДЕСЕРТЫ =====
    let currentProduct = {
        id: null,
        name: '',
        price: 0,
        minQty: 1
    };

    // Открытие модалки для десерта
    document.querySelectorAll('.dessert-card .order-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.dessert-card');
            
            currentProduct = {
                id: card.dataset.id,
                name: card.dataset.name,
                price: parseFloat(card.dataset.price),
                minQty: parseInt(card.dataset.min) || 1
            };
            
            document.getElementById('modalTitle').textContent = currentProduct.name;
            document.getElementById('modalDesc').textContent = card.dataset.desc;
            document.getElementById('modalPrice').textContent = currentProduct.price + ' BYN / шт';
            document.getElementById('modalMin').textContent = currentProduct.minQty;
            document.getElementById('modalImg1').src = card.dataset.img1;
            document.getElementById('modalImg2').src = card.dataset.img2;
            
            const countInput = document.getElementById('count');
            countInput.min = currentProduct.minQty;
            countInput.value = currentProduct.minQty;
            
            updateTotal();
            
            document.getElementById('dessertModal').classList.add('active');
        });
    });

    // Счётчик количества
    const countInput = document.getElementById('count');
    document.getElementById('minus').addEventListener('click', () => {
        let val = parseInt(countInput.value);
        if (val > currentProduct.minQty) {
            countInput.value = val - 1;
            updateTotal();
        }
    });
    
    document.getElementById('plus').addEventListener('click', () => {
        let val = parseInt(countInput.value);
        countInput.value = val + 1;
        updateTotal();
    });
    
    countInput.addEventListener('change', function() {
        if (this.value < currentProduct.minQty) {
            this.value = currentProduct.minQty;
        }
        updateTotal();
    });

    function updateTotal() {
        const total = currentProduct.price * parseInt(countInput.value);
        document.getElementById('modalTotal').textContent = total.toFixed(2);
    }

    // Добавление в корзину
    document.getElementById('addToCartBtn').addEventListener('click', function() {
        window.requireAuth(function() {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', currentProduct.id);
            formData.append('product_type', 'simple');
            formData.append('quantity', parseInt(countInput.value));
            formData.append('wishes', document.getElementById('wishesText').value);

            fetch('cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Только одно красивое уведомление!
                    showNotification('Товар добавлен в корзину!', 'success');
                    document.getElementById('dessertModal').classList.remove('active');
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

    // Закрытие модалки
    document.querySelector('#dessertModal .close-modal').addEventListener('click', () => {
        document.getElementById('dessertModal').classList.remove('active');
    });

    window.addEventListener('click', (e) => {
        if (e.target === document.getElementById('dessertModal')) {
            document.getElementById('dessertModal').classList.remove('active');
        }
    });

    // ===== КОНСТРУКТОР КАПКЕЙКОВ =====
    const cupcakeCard = document.querySelector('.cupcake-card-wide');
    if (cupcakeCard) {
        const buildBtn = cupcakeCard.querySelector('.build-btn');
        const stepBases = cupcakeCard.querySelector('.step-bases');
        const stepFillings = cupcakeCard.querySelector('.step-fillings');
        const stepTopping = cupcakeCard.querySelector('.step-topping');
        const baseOptions = cupcakeCard.querySelectorAll('.base-item');
        const fillingItems = cupcakeCard.querySelectorAll('.filling-item');
        const toppingOptions = cupcakeCard.querySelectorAll('.topping-item');
        const finishBtn = cupcakeCard.querySelector('.finish-btn');

        let base = '', filling = '', top = '';

        if (finishBtn) {
            finishBtn.disabled = true;
            finishBtn.classList.add('disabled');
        }

        if (buildBtn) {
            buildBtn.addEventListener('click', () => {
                cupcakeCard.classList.add('constructor-active');
                buildBtn.style.display = 'none';
                if (stepBases) stepBases.classList.remove('hidden');
            });
        }

        baseOptions.forEach(option => {
            option.addEventListener('click', () => {
                baseOptions.forEach(opt => opt.classList.remove('chosen'));
                option.classList.add('chosen');
                base = option.querySelector('img')?.dataset.base || option.dataset.base;

                fillingItems.forEach(item => {
                    const itemBase = item.dataset.base;
                    if (itemBase === base) {
                        item.classList.remove('hidden');
                        item.style.display = '';
                    } else {
                        item.classList.add('hidden');
                        item.style.display = 'none';
                    }
                });

                fillingItems.forEach(item => item.classList.remove('chosen'));
                toppingOptions.forEach(opt => opt.classList.remove('chosen'));
                filling = '';
                top = '';
                updateFinishButton();

                if (stepBases && stepFillings) {
                    setTimeout(() => {
                        stepBases.classList.add('hidden');
                        stepFillings.classList.remove('hidden');
                    }, 400);
                }
            });
        });

        fillingItems.forEach(item => {
            item.addEventListener('click', () => {
                if (item.classList.contains('hidden')) return;
                
                fillingItems.forEach(i => i.classList.remove('chosen'));
                item.classList.add('chosen');
                filling = item.dataset.filling;

                toppingOptions.forEach(opt => opt.classList.remove('chosen'));
                top = '';
                updateFinishButton();

                if (stepFillings && stepTopping) {
                    setTimeout(() => {
                        stepFillings.classList.add('hidden');
                        stepTopping.classList.remove('hidden');
                    }, 400);
                }
            });
        });

        toppingOptions.forEach(option => {
            option.addEventListener('click', () => {
                toppingOptions.forEach(opt => opt.classList.remove('chosen'));
                option.classList.add('chosen');
                top = option.querySelector('img')?.dataset.top || option.dataset.top;
                
                updateFinishButton();
            });
        });

        function updateFinishButton() {
            if (finishBtn) {
                if (top) {
                    finishBtn.disabled = false;
                    finishBtn.classList.remove('disabled');
                } else {
                    finishBtn.disabled = true;
                    finishBtn.classList.add('disabled');
                }
            }
        }

        if (finishBtn) {
            finishBtn.addEventListener('click', () => {
                if (finishBtn.disabled) return;
                
                const modal = document.getElementById('dessertModal');
                const modalImg1 = document.getElementById('modalImg1');
                const modalImg2 = document.getElementById('modalImg2');
                const modalTitle = document.getElementById('modalTitle');
                const modalDesc = document.getElementById('modalDesc');
                const modalMin = document.getElementById('modalMin');
                const modalPrice = document.getElementById('modalPrice');
                const modalTotal = document.getElementById('modalTotal');
                const countInput = document.getElementById('count');

                if (modalImg1) modalImg1.src = 'img/кекс.jpg';
                if (modalImg2) modalImg2.src = 'img/кекс.jpg';
                if (modalTitle) modalTitle.textContent = 'Капкейк «Собери свой вкус»';
                if (modalDesc) modalDesc.textContent = `Вы выбрали: ${base} основу, ${filling} начинку и ${top} шапочку.`;
                
                const minQty = 6;
                const currentPrice = 8;
                
                if (countInput) {
                    countInput.min = minQty;
                    countInput.value = minQty;
                }
                if (modalMin) modalMin.textContent = minQty;
                if (modalPrice) modalPrice.textContent = `${currentPrice} BYN / шт`;
                if (modalTotal) modalTotal.textContent = (currentPrice * minQty).toFixed(2);
                
                document.getElementById('dessertModal').classList.add('active');
            });
        }
    }
});
</script>
</body>
</html>