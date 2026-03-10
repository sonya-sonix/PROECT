<?php
require_once 'db.php';

// Получаем классические торты из БД (для кругового конструктора)
$stmt = $pdo->query("
    SELECT * FROM products 
    WHERE product_type = 'classic_cake' 
        AND is_available = 1 
    ORDER BY id
");
$classic_cakes = $stmt->fetchAll();

// Если в БД нет тортов, используем запасной массив
if (empty($classic_cakes)) {
    $classic_cakes = [
        ['id' => 16, 'name' => 'Красный бархат', 'description' => 'Нежные молочные бисквиты на сгущённом молоке, сливочный крем-чиз, клубника.', 'image_url' => 'img/бархат.jpg'],
        ['id' => 17, 'name' => 'Шоколад-вишня', 'description' => 'Шоколадный бисквит, вишнёвая пропитка, крем-чиз, вишнёвое конфи.', 'image_url' => 'img/шоколад вишн.jpg'],
        ['id' => 18, 'name' => 'Карамельный латте', 'description' => 'Ванильный бисквит, пропитка латте, карамельный крем, фундучное хрустящее пралине, кофейный мусс.', 'image_url' => 'img/лате.jpg'],
        ['id' => 19, 'name' => 'Молочная девочка', 'description' => 'Коржи на сгущённом молоке, сметанно-сливочный крем, ягодное конфи по желанию.', 'image_url' => 'img/молочная.jpg'],
        ['id' => 20, 'name' => 'Сникерс', 'description' => 'Шоколадный бисквит, пропитка какао, арахисовый крем на основе чиза, солёный арахис, карамель.', 'image_url' => 'img/сникерс.jpg'],
        ['id' => 21, 'name' => 'Ферреро', 'description' => 'Шоколадный бисквит, шоколадный крем на основе маскарпоне, шоколадный ганаш, хрустящий слой.', 'image_url' => 'img/фереро.jpg'],
        ['id' => 22, 'name' => 'Цитрус', 'description' => 'Цитрусовый бисквит, апельсиновый мармелад, лимонная намелака, крем-чиз.', 'image_url' => 'img/циртрус.jpg'],
        ['id' => 23, 'name' => 'Ягодный пломбир', 'description' => 'Ванильный шифоновый бисквит, крем с маскарпоне, ягодное конфи на выбор (малина, клубника, черника).', 'image_url' => 'img/ягодный.jpg'],
        ['id' => 24, 'name' => 'Карамельный', 'description' => 'Шоколадный бисквит, карамельная пропитка, крем-чиз, взбитая карамель.', 'image_url' => 'img/карамельный.jpg'],
        ['id' => 25, 'name' => 'Тирамису', 'description' => 'Миндальный бисквит, кофейная пропитка, крем с маскарпоне, кофейный ганаш.', 'image_url' => 'img/тирамису.jpg'],
        ['id' => 26, 'name' => 'Медовик', 'description' => 'Медовые коржи, сметанно-сливочный крем, по желанию вишня.', 'image_url' => 'img/медовик.jpg'],
        ['id' => 27, 'name' => 'Миндаль-кокос', 'description' => 'Кокосово-миндальный бисквит, кокосовая пропитка, кокосовый крем на основе маскарпоне, хрустящее пралине.', 'image_url' => 'img/рафаэ.jpg'],
        ['id' => 28, 'name' => 'Морковный', 'description' => 'Пряные коржи, крем-чиз, вишёвое конфи, солёная карамель.', 'image_url' => 'img/морковный.jpg'],
        ['id' => 29, 'name' => 'Банановый', 'description' => 'Шоколадный бисквит, крем-чиз, банан в карамеле.', 'image_url' => 'img/банан.jpg'],
        ['id' => 30, 'name' => 'Фисташка-малина', 'description' => 'Фисташковый бисквит, крем маскарпоне, белый шоколад, хрустящий слой, малиновое конфи.', 'image_url' => 'img/фисташка.jpg'],
        ['id' => 31, 'name' => 'Халвичный раф', 'description' => 'Шоколадный бисквит, латте пропитка, крем латте, ганаш кофейный, халва.', 'image_url' => 'img/халва.jpg']
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>City Tort — Главная</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">
  <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="notifications.css">
  <style>
    /* Стили для модалки заказа */
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
    
    /* Кнопка добавления в корзину в круговом конструкторе */
    .add-to-cart-circle {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background: #d8737f;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      z-index: 100;
      box-shadow: 0 5px 15px rgba(216,115,127,0.3);
      transition: 0.3s;
    }
    .add-to-cart-circle:hover {
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
        <li><a href="clasic.php">Классические торты</a></li>
        <li><a href="bento.php">Бенто</a></li>
        <li><a href="desert.php">Десерты</a></li>
      </ul>
    </li>
    <li><a href="#reviews">Отзывы</a></li>
    <li><a href="#delivery">Доставка</a></li>
    <li><a href="#map">Контакты</a></li>
  </ul>

  <div class="menu-phone">
    <a href="tel:+375297523044">+375 (29) 752-30-44</a>
  </div>
</nav>

<div class="overlay" id="overlay"></div>

<main>
  <!-- HERO СЕКЦИЯ -->
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-top">
        <h1 class="hero__title">Лучшие вкусные торты</h1>
        <div class="hero__meta">
          <div class="hero__subtitle">С любовью и заботой</div>
          <div class="hero__author">от Ольги</div>
        </div>
      </div>

      <div class="hero-body">
        <div class="hero__image">
          <img src="img/1.jpg" alt="Торт">
        </div>

        <aside class="hero__right">
          <a href="clasic.php" class="hero__card">
            <div class="card__text">Классические тортики</div>
            <div class="card__imgwrap"><img src="img/2.jpg" alt=""></div>
          </a>

          <a href="bento.php" class="hero__card">
            <div class="card__text">Бенто тортики</div>
            <div class="card__imgwrap"><img src="img/3.jpg" alt=""></div>
          </a>

          <a href="desert.php" class="hero__card">
            <div class="card__text">Десерты</div>
            <div class="card__imgwrap"><img src="img/4.jpg" alt=""></div>
          </a>
        </aside>
      </div>
    </div>
  </section>

  <!-- REASONS СЕКЦИЯ -->
  <section class="reasons">
    <div class="reasons-inner">
      <h2 class="reasons__title">ПОЧЕМУ ВЫБИРАЮТ МЕНЯ</h2>
      <div class="reasons__grid">
        <div class="reason fade-in">
          <div class="reason__icon"><i class="fa-regular fa-face-smile"></i></div>
          <h3 class="reason__subtitle">7 лет опыта и более 10 000 довольных клиентов</h3>
          <p class="reason__text">Я работаю с 2018 года. За 7 лет я улучшила рецепты, расширила ассортимент и завоевала доверие более 10 000 клиентов. Мои торты становятся украшением праздничных столов, семейных торжеств и особых моментов жизни.</p>
        </div>
        <div class="reason fade-in">
          <div class="reason__icon"><i class="fa-solid fa-apple-whole"></i></div>
          <h3 class="reason__subtitle">Нежный вкус и качественные ингредиенты</h3>
          <p class="reason__text">Торты создаются из отборных продуктов: свежих сливок, качественной муки, натуральных ягод и фруктов. Каждый ингредиент тщательно подобран, чтобы создать идеальный баланс вкуса и нежности.</p>
        </div>
        <div class="reason fade-in">
          <div class="reason__icon"><i class="fa-solid fa-leaf"></i></div>
          <h3 class="reason__subtitle">Индивидуальный подход к каждому заказу</h3>
          <p class="reason__text">Я внимательно выслушиваю все ваши пожелания и помогаю создать торт мечты. Учитываю особенности диеты, вкусовые предпочтения и оформление.</p>
        </div>
        <div class="reason fade-in">
          <div class="reason__icon"><i class="fa-regular fa-gift"></i></div>
          <h3 class="reason__subtitle">Элегантная фирменная упаковка</h3>
          <p class="reason__text">Каждый торт бережно упаковывается в прочный прозрачный тубус с фирменной лентой и стильным вкладышем. Упаковка надёжно сохраняет десерт и делает его украшением стола.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- GALLERY СЕКЦИЯ -->
  <section class="gallery">
    <div class="gallery-inner">
      <h2 class="gallery__title">ТОРТ С ДЕКОРОМ</h2>
      <p class="gallery__text">Стоимость рассчитывается индивидуально. Вы можете выбрать готовый вариант или прислать свои пожелания и примеры.</p>
      <div class="gallery-wrapper">
        <button class="gallery-btn prev">❮</button>
        <div class="gallery-slider">
          <div class="gallery-track">
            <div class="gallery-slide">
              <img src="img/декор1.jpg" alt=""><img src="img/декор2.jpg" alt=""><img src="img/декор3.jpg" alt=""><img src="img/декор4.jpg" alt="">
            </div>
            <div class="gallery-slide">
              <img src="img/декор5.jpg" alt=""><img src="img/декор6.jpg" alt=""><img src="img/декор7.jpg" alt=""><img src="img/декор8.jpg" alt="">
            </div>
            <div class="gallery-slide">
              <img src="img/декор9.jpg" alt=""><img src="img/декор10.jpg" alt=""><img src="img/декор11.jpg" alt=""><img src="img/декор12.jpg" alt="">
            </div>
          </div>
        </div>
        <button class="gallery-btn next">❯</button>
      </div>
    </div>
  </section>

  <!-- Секция с начинками (ПОДКЛЮЧЕНА К БД) -->
  <section class="fillings-section">
    <div class="fillings-inner">
      <h2 class="fillings__title">ВЫБЕРИТЕ ВКУС</h2>
      <p class="fillings__subtitle">Попробуйте наши самые популярные десерты</p>

      <div class="tabs">
        <button class="active" data-tab="classic">Классические торты</button>
        <button data-tab="cupcakes">Капкейки</button>
        <button data-tab="bento">Бенто</button>
      </div>

      <div class="stage">
        <!-- Классические торты (из БД) -->
        <div class="stage-content active" id="classic">
          <div class="cake-wrap">
            <div class="cake-bg"></div>
            <img class="cake" id="cakeImg" src="img/бархат.jpg" alt="Разрез торта">
          </div>
          <div class="arc-wrap">
            <svg class="arc" viewBox="0 0 700 700" preserveAspectRatio="xMidYMid meet">
              <path id="arcPath" d="M350 80 A270 270 0 0 1 350 620"></path>
            </svg>
            <div class="items" id="items"></div>
          </div>
          <div class="info">
            <h2 id="title">Красный бархат</h2>
            <p id="desc">Нежные молочные бисквиты на сгущённом молоке, сливочный крем-чиз, клубника.</p>
          </div>
          <!-- Кнопка добавления в корзину -->
          <button class="add-to-cart-circle" id="addToCartCircle">
            <i class="fa-solid fa-cart-plus"></i> Добавить в корзину
          </button>
        </div>

        <!-- Капкейки -->
        <div class="stage-content" id="cupcakes">
          <div class="cupcakes-grid">
            <div class="cupcake-item"><img src="img/ягода.png" alt="Ягодная"><p>Ягодная</p></div>
            <div class="cupcake-item"><img src="img/лимон.png" alt="Лимонная"><p>Лимонная</p></div>
            <div class="cupcake-item"><img src="img/сникерс.png" alt="Сникерс"><p>Сникерс</p></div>
            <div class="cupcake-item"><img src="img/карамель.png" alt="Карамель"><p>Карамель</p></div>
            <div class="cupcake-item"><img src="img/ганаш.png" alt="Молочный ганаш"><p>Молочный ганаш</p></div>
            <div class="cupcake-item"><img src="img/банан.png" alt="Банановая"><p>Банановая</p></div>
          </div>
          <div class="cupcakes-info">
            <div class="left"><h4>Варианты кекса:</h4><p>Ванильный, шоколадный, красный бархат</p></div>
            <div class="right"><h4>Варианты шапочки:</h4><p>Ваниль, шоколад</p></div>
          </div>
        </div>

        <!-- Бенто -->
        <div class="stage-content" id="bento">
          <div class="bento-wrap">
            <div class="bento-item">
              <img src="img/бенто1.png" alt="Ванильный бенто">
              <h3>Ванильный</h3>
              <p>Ванильный бисквит, ягодная начинка/карамель, крем-чиз на сливках</p>
            </div>
            <div class="bento-item">
              <img src="img/бенто2.png" alt="Шоколадный бенто">
              <h3>Шоколадный</h3>
              <p>Шоколадный бисквит, ягодная начинка/карамель/сникерс, крем-чиз на сливках</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- МОДАЛКА ДЛЯ ЗАКАЗА ТОРТА (БЕЗ ВЫБОРА ДЕКОРА) -->
  <div class="modal" id="orderCakeModal">
    <div class="modal-content modal-order">
      <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
      <h3>Оформление заказа</h3>
      
      <div class="modal-form-group">
        <label>Название торта:</label>
        <input type="text" id="cakeName" readonly value="Красный бархат">
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
        <i class="fa-solid fa-check"></i> Подтвердить заказ
      </button>
    </div>
  </div>

  <!-- REVIEWS СЕКЦИЯ -->
  <section class="reviews-section" id="reviews">
    <h2 class="section-title">ОТЗЫВЫ</h2>
    <div class="reviews-container">
      <div class="reviews-slider">
        <button class="arrow left">❮</button>
        <div class="reviews-track">
          <?php for ($i = 1; $i <= 10; $i++): ?>
          <div class="review" style="background-image: url('img/отзывы<?= $i ?>.jpg')">
            <div class="overlay"></div>
            <div class="text">
              <h3>
                <?php 
                  $names = ['Светлана', 'Валерия', 'Халимат', 'Наталья', 'Анастасия', 'Марина', 'Екатерина', 'Татьяна', 'Юлия', 'Ольга'];
                  echo $names[$i-1];
                ?>
              </h3>
              <p>
                <?php 
                  $reviews = [
                    'Счастлива, что попробовала ваш Наполеон — просто восторг!',
                    'Перепробовала три вкуса — все шикарные, но Маковый на первом месте!',
                    '«Ванильный с ягодами» — просто любовь с первого кусочка!',
                    'Торт был восхитителен, все гости спрашивали, где заказывала.',
                    'Всем понравилось, никто не остался равнодушным!',
                    'Ваши торты — это искусство! Каждый заказ удивляет!',
                    'Самые вкусные десерты! Идеальный баланс вкуса и красоты.',
                    'Очень довольна заказом! Всё свежее и невероятно красиво.',
                    'Десерты просто тают во рту, оформление потрясающее.',
                    'Спасибо за заботу и вкус! Будем заказывать снова.'
                  ];
                  echo $reviews[$i-1];
                ?>
              </p>
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <button class="arrow right">❯</button>
      </div>
    </div>
  </section>

  <!-- DELIVERY СЕКЦИЯ -->
  <section class="delivery-section" id="delivery">
    <div class="delivery-inner">
      <h2 class="delivery__title">УСЛОВИЯ ДОСТАВКИ</h2>
      <div class="delivery-line">
        <div class="delivery-track"></div>
        <div class="delivery-car"><img src="img/car.png" alt="car" /></div>
        <div class="delivery-point" data-step="1" style="left: 0%">
          <div class="delivery-text"><h4>Доставка по городу</h4><p>от 7руб.</p></div>
        </div>
        <div class="delivery-point" data-step="2" style="left: 32%">
          <div class="delivery-text"><h4>За город</h4><p>от 20руб.</p></div>
        </div>
        <div class="delivery-point" data-step="3" style="left: 66%">
          <div class="delivery-text"><h4>Более 100км</h4><p>по договорённости</p></div>
        </div>
        <div class="delivery-point" data-step="4" style="left: 99%">
          <div class="delivery-text"><h4>свадебники от 5кг</h4><p>бесплатно</p></div>
        </div>
      </div>
      <div class="delivery-note">
        <p>Также есть самовывоз по адресу ул.Асфальтная 63а</p>
        <p>*Доставка к точному часу рассчитывается индивидуально.</p>
        <p>На 29, 30, 31 декабря действуют повышенные тарифы.</p>
      </div>
    </div>
  </section>

  <!-- CONTACTS СЕКЦИЯ -->
  <section class="contacts-section">
    <div id="map" class="contacts-map"></div>
    <div class="contacts-info">
      <h2 class="contacts-title">КОНТАКТЫ</h2>
      <p class="contacts-phone">+375 (29) 2894038</p>
      <p class="contacts-email">romanovskaya_1981@mail.ru</p>
      <p class="contacts-address">г. Гродно, ул. Асфальтная, 63А</p>
      <p class="contacts-hours">Ежедневно</p>
      <p class="contacts-note">Заказы принимаются не менее, чем за 24 часа.<br>Доставка по Гродно и области или самовывоз.</p>
      <p class="contacts-extra">Но у меня всегда есть что-то в наличии — <strong>звоните или пишите в Telegram</strong></p>
      <button class="order-btn feedback-trigger" onclick="openModal('feedbackModal')">Написать сообщение</button>
      <div class="contacts-icons">
        <a href="https://t.me/cyti_tort" aria-label="Telegram"><i class="fa-brands fa-telegram"></i></a>
        <a href="https://www.instagram.com/city_tort_?igsh=MWNvcHV5cTB5cHdqdg==" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
      </div>
    </div>
  </section>
  
  <!-- FOOTER -->
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
          <li><a href="#reviews">Отзывы</a></li>
          <li><a href="#delivery">Доставка</a></li>
          <li><a href="#map">Контакты</a></li>
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
</main>

<!-- Модальное окно обратной связи -->
<div class="modal" id="feedbackModal">
  <div class="modal-content">
    <button class="close-modal" onclick="closeModal('feedbackModal')">✕</button>
    <h3>Обратная связь</h3>
    <p>Напишите нам, и мы ответим в ближайшее время!</p>
    <form id="feedbackForm" method="POST">
      <div class="form-group"><input type="text" name="name" required placeholder="Ваше имя"></div>
      <div class="form-group"><input type="email" name="email" required placeholder="Ваш email"></div>
      <div class="form-group"><input type="tel" name="phone" placeholder="Телефон (необязательно)"></div>
      <div class="form-group"><textarea name="message" rows="4" required placeholder="Ваше сообщение..."></textarea></div>
      <button type="submit" class="order-btn submit-btn"><i class="fa-solid fa-paper-plane"></i> Отправить сообщение</button>
    </form>
    <div id="feedbackResult" style="display:none;"></div>
    <div class="modal-contact-info">
      <p><i class="fa-solid fa-phone"></i> +375 (29) 2894038</p>
      <p><i class="fa-solid fa-envelope"></i> romanovskaya_1981@mail.ru</p>
      <p><i class="fa-solid fa-clock"></i> Ежедневно</p>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script src="auth.js"></script>
<script src="notifications.js"></script>
<script>
// ===== ДАННЫЕ ИЗ БД (передаём в JS) =====
window.classicCakes = <?= json_encode($classic_cakes) ?>;

// ===== КРУГОВОЙ КОНСТРУКТОР =====
document.addEventListener('DOMContentLoaded', function() {
  const items = document.getElementById("items");
  const path = document.getElementById("arcPath");
  const cake = document.getElementById("cakeImg");
  const title = document.getElementById("title");
  const desc = document.getElementById("desc");
  const addToCartCircle = document.getElementById("addToCartCircle");

  if (!items || !path || !cake || !title || !desc) return;

  // Очищаем контейнер
  items.innerHTML = '';

  // Функция для пути к изображению
  function getImagePath(src) {
    if (!src) return 'img/default-cake.jpg';
    if (src.startsWith('http') || src.startsWith('/')) return src;
    return src.startsWith('img/') ? src : `img/${src}`;
  }

  // Создаём кружки из данных БД
  window.classicCakes.forEach((cakeItem, i) => {
    const el = document.createElement("div");
    el.className = "item";
    el.dataset.index = i;
    
    const imgSrc = getImagePath(cakeItem.image_url);
    
    el.innerHTML = `<img src="${imgSrc}" alt="${cakeItem.name}" 
      onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\"width:100%;height:100%;background:#f0f0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;\">${cakeItem.name.charAt(0)}</div>'">`;
    el.onclick = () => setActive(i);
    items.appendChild(el);
  });

  // Функции конструктора
  let pathLen, stepLen, N = window.classicCakes.length;
  let currentIndex = Math.floor(N/2);
  let currentOffset = 0;
  let animating = false;

  function init() {
    pathLen = path.getTotalLength();
    stepLen = pathLen / (N - 1);
    const centerPoint = pathLen / 2;
    currentOffset = centerPoint - (currentIndex * stepLen);
    render();
    updateInfo(currentIndex);
  }

  function render() {
    const els = [...items.children];
    const maxScale = 1.3, minScale = 0.5, decay = 0.09;
    const centerPoint = pathLen / 2;

    els.forEach((el, i) => {
      let L = (i * stepLen + currentOffset) % pathLen;
      if (L < 0) L += pathLen;

      const pt = path.getPointAtLength(L);
      el.style.left = pt.x + "px";
      el.style.top = pt.y + "px";

      const distance = Math.abs(L - centerPoint);
      const normalized = distance / stepLen;

      const scale = Math.max(minScale, maxScale - normalized * decay);
      const opacity = Math.max(0.15, 1 - normalized * 0.1);

      el.style.transform = `translate(-50%, -50%) scale(${scale})`;
      el.style.opacity = opacity;
      el.style.zIndex = Math.round(100 - normalized * 10);

      if (Math.abs(normalized) < 0.3) {
        el.classList.add("isActive");
      } else {
        el.classList.remove("isActive");
      }
    });
  }

  function setActive(targetIndex, instant = false) {
    if (animating && !instant) return;
    if (targetIndex === currentIndex) return;
    
    const centerPoint = pathLen / 2;
    const targetOffset = centerPoint - (targetIndex * stepLen);
    let diff = targetOffset - currentOffset;
    
    if (Math.abs(diff) > pathLen / 2) {
      diff = diff > 0 ? diff - pathLen : diff + pathLen;
    }
    
    const finalOffset = currentOffset + diff;
    
    if (instant) {
      currentOffset = finalOffset;
      currentIndex = targetIndex;
      render();
      updateInfo(targetIndex);
      return;
    }
    
    animating = true;
    animate(currentOffset, finalOffset, 700, () => {
      currentIndex = targetIndex;
      updateInfo(targetIndex);
      animating = false;
    });
  }

  function updateInfo(index) {
    const active = window.classicCakes[index];
    if (!active) return;
    
    title.textContent = active.name;
    desc.textContent = active.description;

    cake.classList.add('fade-out');

    const newImg = new Image();
    const imgSrc = getImagePath(active.image_url);
    newImg.src = imgSrc;
    
    newImg.onload = () => {
      setTimeout(() => {
        cake.src = imgSrc;
        cake.alt = active.name;
        cake.classList.remove('fade-out');
        cake.classList.add('fade-in');
        setTimeout(() => cake.classList.remove('fade-in'), 800);
      }, 200);
    };
    
    newImg.onerror = () => {
      setTimeout(() => {
        cake.src = 'img/default-cake.jpg';
        cake.alt = active.name;
        cake.classList.remove('fade-out');
        cake.classList.add('fade-in');
        setTimeout(() => cake.classList.remove('fade-in'), 800);
      }, 200);
    };
  }

  function animate(from, to, dur, cb) {
    const start = performance.now();
    const ease = t => 1 - Math.pow(1 - t, 3);
    
    function frame(now) {
      const t = Math.min(1, (now - start) / dur);
      currentOffset = from + (to - from) * ease(t);
      render();
      
      if (t < 1) {
        requestAnimationFrame(frame);
      } else {
        currentOffset = to;
        render();
        cb && cb();
      }
    }
    requestAnimationFrame(frame);
  }

  setTimeout(init, 100);

  // ===== КНОПКА ДОБАВЛЕНИЯ В КОРЗИНУ =====
  if (addToCartCircle) {
    addToCartCircle.addEventListener('click', () => {
      if (typeof window.requireAuth === 'function') {
        window.requireAuth(function() {
      const activeCake = window.classicCakes[currentIndex];
      const modal = document.getElementById('orderCakeModal');
      const cakeNameInput = document.getElementById('cakeName');
      const cakeWeightInput = document.getElementById('cakeWeight');
      const cakeTotalSpan = document.getElementById('cakeTotal');
      
      cakeNameInput.value = activeCake.name;
      updateTotal();
      
    modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }, 'Для заказа торта необходимо войти в аккаунт');
        } else {
            console.error('requireAuth не найден!');
            alert('Ошибка авторизации. Перезагрузите страницу.');
        }
    });
}

  // ===== РАСЧЁТ СТОИМОСТИ =====
  const weightInput = document.getElementById('cakeWeight');
  const totalSpan = document.getElementById('cakeTotal');

  function updateTotal() {
    const weight = parseFloat(weightInput?.value || 2);
    const basePrice = 55; // цена за кг
    const total = weight * basePrice;
    totalSpan.textContent = `Итого: ${total.toFixed(2)} BYN`;
  }

  if (weightInput) {
    weightInput.addEventListener('input', updateTotal);
  }

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
      }
    });
  }

// ===== ПОДТВЕРЖДЕНИЕ ЗАКАЗА =====
const confirmBtn = document.getElementById('confirmCakeOrder');
if (confirmBtn) {
    confirmBtn.addEventListener('click', function() {
        if (typeof window.requireAuth === 'function') {
            window.requireAuth(function() {
                // Получаем активный торт
                const activeCake = window.classicCakes[currentIndex];
                
                const weight = parseFloat(weightInput.value);
                const wishes = document.getElementById('cakeWishes').value;
                
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('product_id', activeCake.id);
                formData.append('product_type', 'classic_cake');
                formData.append('weight_kg', weight);
                formData.append('wishes', wishes);

                fetch('cart_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Торт добавлен в корзину!', 'success');
                        document.getElementById('orderCakeModal').classList.remove('active');
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
            }, 'Для добавления в корзину необходимо войти в аккаунт');
        } else {
            console.error('requireAuth не найден!');
            alert('Ошибка авторизации. Перезагрузите страницу.');
        }
    });
}

  // ===== ЗАКРЫТИЕ МОДАЛКИ =====
  const closeModalBtn = document.querySelector('#orderCakeModal .close-modal');
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      document.getElementById('orderCakeModal').classList.remove('active');
      document.body.style.overflow = '';
    });
  }

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