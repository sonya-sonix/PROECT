<?php
session_start();
require_once 'db.php';

// Получаем все классические торты из БД
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.product_type = 'classic_cake' 
        AND p.is_available = 1
    ORDER BY p.id ASC
");
$stmt->execute();
$cakes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>City Tort — Классические торты</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">
  <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="notifications.css">
  <style>
    .cake-card[data-available="0"] {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }
    .cake-card[data-available="0"]::after {
      content: "Нет в наличии";
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ff6b6b;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .modal-order .modal-content {
      max-width: 500px;
    }
    .modal-form-group {
      margin-bottom: 20px;
    }
    .modal-form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    .modal-form-group input,
    .modal-form-group textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 12px;
      font-family: 'Montserrat', sans-serif;
    }
    .modal-form-group input[type="number"] {
      width: 120px;
    }
    .file-upload {
      border: 2px dashed #d8737f;
      padding: 20px;
      text-align: center;
      border-radius: 12px;
      cursor: pointer;
      transition: 0.3s;
    }
    .file-upload:hover {
      background: #fff5f5;
    }
    .modal-total {
      font-size: 1.3rem;
      font-weight: 600;
      color: #d8737f;
      margin: 20px 0;
      text-align: right;
    }
    .add-to-cart-btn {
      background: #d8737f;
      color: white;
      border: none;
      padding: 15px 30px;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: 0.3s;
    }
    .add-to-cart-btn:hover {
      background: #c76571;
      transform: translateY(-2px);
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

<section class="cakes-section">
  <div class="cakes-container">
    <h2 class="cakes-title">Классические торты</h2>
    <p class="cakes-subtitle">Выберите свой любимый вкус</p>
    <p class="cakes-subtitle">Цена за 1кг - 55 BYN</p>
    <p class="cakes-subtitle">Минимальный заказ от 2-2.5кг</p>

    <div class="cakes-grid">
      <?php if (!empty($cakes)): ?>
        <?php foreach ($cakes as $cake): ?>
          <div class="cake-card" 
               data-name="<?= htmlspecialchars($cake['name']) ?>" 
               data-desc="<?= htmlspecialchars($cake['description'] ?? '') ?>" 
               data-price="<?= htmlspecialchars($cake['base_price']) ?>" 
               data-img1="<?= htmlspecialchars($cake['image_url'] ?? 'img/default-cake.jpg') ?>" 
               data-img2="<?= htmlspecialchars($cake['image_url'] ?? 'img/default-cake2.jpg') ?>"
               data-available="<?= $cake['is_available'] ?>"
               data-id="<?= $cake['id'] ?>">
            
            <div class="cake-img">
              <?php if (!empty($cake['image_url'])): ?>
                <img src="<?= htmlspecialchars($cake['image_url']) ?>" alt="<?= htmlspecialchars($cake['name']) ?>">
                <img class="hover-img" src="<?= htmlspecialchars($cake['image_url']) ?>" alt="<?= htmlspecialchars($cake['name']) ?> 2">
              <?php else: ?>
                <img src="img/default-cake.jpg" alt="<?= htmlspecialchars($cake['name']) ?>">
                <img class="hover-img" src="img/default-cake2.jpg" alt="<?= htmlspecialchars($cake['name']) ?> 2">
              <?php endif; ?>
            </div>
            <h3><?= htmlspecialchars($cake['name']) ?></h3>
            <button class="order-btn">Подробнее</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- МОДАЛКА ТОРТА -->
<div class="modal" id="cakeModal">
  <div class="modal-content modal-cake">
    <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-images">
      <img id="modalImg1" src="" alt="">
      <img id="modalImg2" src="" alt="">
    </div>
    <div class="modal-info">
      <h3 id="modalTitle"></h3>
      <p id="modalDesc"></p>
      <span class="price" id="modalPrice"></span>
      <button class="add-to-cart" id="openOrderModalBtn">Заказать</button>
    </div>
  </div>
</div>

<!-- МОДАЛКА ОФОРМЛЕНИЯ ЗАКАЗА -->
<div class="modal" id="orderCakeModal">
  <div class="modal-content modal-order">
    <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
    <h3>Оформление заказа</h3>
    
    <div class="modal-form-group">
      <label>Название торта:</label>
      <input type="text" id="cakeName" readonly value="">
    </div>
    
    <div class="modal-form-group">
      <label>Вес торта (кг):</label>
      <input type="number" id="cakeWeight" min="2" step="0.5" value="2">
      <small>Минимальный вес: 2 кг</small>
    </div>
    
    <div class="modal-form-group">
      <label>Фото дизайна (если есть):</label>
      <div class="file-upload" onclick="document.getElementById('designFile').click()">
        <i class="fa-solid fa-cloud-upload-alt"></i>
        <p>Нажмите, чтобы загрузить фото</p>
        <input type="file" id="designFile" accept="image/*" style="display: none;">
      </div>
    </div>
    
    <div class="modal-form-group">
      <label>Ваши пожелания:</label>
      <textarea id="cakeWishes" rows="3" placeholder="Напишите, что хотите..."></textarea>
    </div>
    
    <div class="modal-total" id="cakeTotal">Итого: 110 BYN</div>
    
    <button class="add-to-cart-btn" id="confirmCakeOrder">
      <i class="fa-solid fa-check"></i> Добавить в корзину
    </button>
  </div>
</div>

<section class="help-section">
  <div class="help-content">
    <h2 class="help-title fade-in-up">НУЖНА ПОМОЩЬ С ВЫБОРОМ?</h2>
    <p class="help-text fade-in-up">
      Чат в Telegram с нашим кондитером: быстро ответим на ваши вопросы
    </p>
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
// ===== ДАННЫЕ ИЗ БД =====
window.classicCakes = <?= json_encode($cakes) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const cakeModal = document.getElementById('cakeModal');
    const orderModal = document.getElementById('orderCakeModal');
    let currentCakeId = null;
    let currentCakeName = '';

    // ===== ОТКРЫТИЕ МОДАЛКИ ТОРТА =====
    document.querySelectorAll('.cake-card .order-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.cake-card');
            
            document.getElementById('modalTitle').textContent = card.dataset.name;
            document.getElementById('modalDesc').textContent = card.dataset.desc;
            document.getElementById('modalPrice').textContent = card.dataset.price + ' BYN';
            document.getElementById('modalImg1').src = card.dataset.img1;
            document.getElementById('modalImg2').src = card.dataset.img2 || card.dataset.img1;
            
            currentCakeId = card.dataset.id;
            currentCakeName = card.dataset.name;
            
            cakeModal.classList.add('active');
        });
    });

// ===== КНОПКА "ЗАКАЗАТЬ" В МОДАЛКЕ ТОРТА =====
document.getElementById('openOrderModalBtn').addEventListener('click', function() {
    if (typeof window.requireAuth === 'function') {
        window.requireAuth(function() {
            document.getElementById('cakeName').value = currentCakeName;
            updateTotal();
            cakeModal.classList.remove('active');
            orderModal.classList.add('active');
        }, 'Для заказа классического торта необходимо войти в аккаунт');
    } else {
        console.error('requireAuth не найден!');
        alert('Ошибка авторизации. Перезагрузите страницу.');
    }
});

    // ===== ЗАКРЫТИЕ МОДАЛОК =====
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            cakeModal.classList.remove('active');
            orderModal.classList.remove('active');
        });
    });

    // ===== РАСЧЁТ СУММЫ =====
    const weightInput = document.getElementById('cakeWeight');
    const totalSpan = document.getElementById('cakeTotal');

    function updateTotal() {
        const weight = parseFloat(weightInput?.value || 2);
        const total = weight * 55;
        totalSpan.textContent = `Итого: ${total.toFixed(2)} BYN`;
    }

    weightInput?.addEventListener('input', updateTotal);

    // ===== ЗАГРУЗКА ФАЙЛА =====
    const fileUpload = document.querySelector('.file-upload');
    const fileInput = document.getElementById('designFile');
    
    if (fileUpload && fileInput) {
        fileUpload.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const p = fileUpload.querySelector('p');
                if (p) {
                    p.innerHTML = `<i class="fa-solid fa-check" style="color: #4CAF50;"></i> Выбрано: ${fileName}`;
                }
                console.log('Файл выбран:', fileName);
            }
        });
    }

// ===== ДОБАВЛЕНИЕ В КОРЗИНУ =====
document.getElementById('confirmCakeOrder').addEventListener('click', function() {
    if (typeof window.requireAuth === 'function') {
        window.requireAuth(function() {
            // Проверяем, что данные загружены
            if (!window.classicCakes || window.classicCakes.length === 0) {
                showNotification('Ошибка загрузки данных', 'error');
                return;
            }

            // Получаем активный торт
            const activeCake = window.classicCakes[currentIndex];
            
            if (!activeCake) {
                showNotification('Ошибка: торт не выбран', 'error');
                return;
            }
            
            const weight = parseFloat(weightInput.value);
            const wishes = document.getElementById('cakeWishes').value;
            const fileInput = document.getElementById('designFile');
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', activeCake.id);
            formData.append('product_type', 'classic_cake');
            formData.append('weight_kg', weight);
            formData.append('wishes', wishes);
            
            if (fileInput.files.length > 0) {
                formData.append('design_file', fileInput.files[0]);
            }

            fetch('cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Торт добавлен в корзину!', 'success');
                    orderModal.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    if (fileInput) {
                        fileInput.value = '';
                        const p = fileUpload?.querySelector('p');
                        if (p) p.innerHTML = 'Нажмите, чтобы загрузить фото';
                    }
                    
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
        }, 'Для добавления в корзину необходимо войти в аккаунт');
    } else {
        console.error('requireAuth не найден!');
        alert('Ошибка авторизации. Перезагрузите страницу.');
    }
});

    // ===== ЗАКРЫТИЕ МОДАЛКИ ПРИ КЛИКЕ НА ФОН =====
    const modal = document.getElementById('orderCakeModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
});
</script>
</body>
</html>