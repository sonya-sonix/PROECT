<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// --- ОБРАБОТКА ДЕЙСТВИЙ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Обновить цену
    if (isset($_POST['update_price'])) {
        $product_id = $_POST['product_id'];
        $new_price = $_POST['new_price'];
        $stmt = $pdo->prepare("UPDATE products SET base_price = ? WHERE id = ?");
        $stmt->execute([$new_price, $product_id]);
        $_SESSION['message'] = 'Цена обновлена!';
    }
    
    // 2. Удалить товар
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product && $product['image_url']) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $product['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['message'] = 'Товар удален!';
    }
    
    // 3. Добавить товар С ФОТО
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $type = $_POST['product_type'];
        $desc = $_POST['description'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        $image_url = null;
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($file_ext, $allowed)) {
                $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    $image_url = '/img/' . $new_filename;
                }
            }
        }
        
        $sql = "INSERT INTO products (name, base_price, category_id, product_type, description, image_url, is_available, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $category_id, $type, $desc, $image_url, $is_available]);
        
        $_SESSION['message'] = 'Товар успешно добавлен!';
    }
    
    // 4. Изменить статус "В наличии"
    if (isset($_POST['toggle_availability'])) {
        $product_id = $_POST['product_id'];
        $is_available = $_POST['is_available'];
        
        $stmt = $pdo->prepare("UPDATE products SET is_available = ? WHERE id = ?");
        $stmt->execute([$is_available, $product_id]);
        
        $_SESSION['message'] = $is_available ? 'Товар появится в наличии' : 'Товар скрыт с сайта';
    }
    
    header('Location: admin.php');
    exit;
}

// --- ПОЛУЧАЕМ ДАННЫЕ ---
$products = $pdo->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY 
                            CASE 
                                WHEN p.is_available = 1 THEN 0 
                                ELSE 1 
                            END,
                            p.id DESC")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Статистика
$stats = [
    'total_products' => count($products),
    'available' => count(array_filter($products, fn($p) => $p['is_available'] == 1)),
    'unavailable' => count(array_filter($products, fn($p) => $p['is_available'] == 0)),
    'categories' => count($categories)
];

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Количество товаров для отображения
$display_limit = 5;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | City Tort</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Montserrat', sans-serif; 
            background: #faf7f2; 
            padding: 30px;
        }
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #d8737f;
        }
        .admin-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            color: #333;
        }
        .admin-header h1 span {
            color: #d8737f;
        }
        .logout-btn {
            background: #d8737f;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: #fce9e9;
            color: #d8737f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-info h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #333;
        }
        .stat-info p {
            color: #999;
            font-size: 0.85rem;
        }
        
        /* Кнопка добавления */
        .add-product-btn {
            background: #d8737f;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            margin-bottom: 20px;
        }
        .add-product-btn:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
        
        /* Форма добавления (сверху) */
        .add-form-container {
            background: white;
            border-radius: 20px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease-out;
        }
        .add-form-container.active {
            max-height: 800px;
        }
        .add-form {
            padding: 25px;
            border-top: 3px solid #d8737f;
        }
        .add-form h3 {
            margin-bottom: 20px;
            color: #d8737f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d8737f;
            box-shadow: 0 0 0 2px rgba(216,115,127,0.1);
        }
        .file-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 15px;
        }
        .file-upload:hover {
            border-color: #d8737f;
            background: #fff5f5;
        }
        .file-upload i {
            font-size: 2rem;
            color: #d8737f;
            margin-bottom: 10px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            cursor: pointer;
        }
        .submit-btn {
            background: #d8737f;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
            font-size: 1rem;
        }
        .submit-btn:hover {
            background: #c76571;
            transform: translateY(-2px);
        }
        
        /* Фильтры и поиск */
        .filters-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 25px;
            border-radius: 30px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .filter-btn:hover {
            border-color: #d8737f;
        }
        .filter-btn.active {
            background: #d8737f;
            color: white;
            border-color: #d8737f;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 1px solid #ddd;
            border-radius: 40px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: #d8737f;
            box-shadow: 0 0 0 2px rgba(216,115,127,0.1);
        }
        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }
        
        /* Таблица товаров с ФИКСИРОВАННЫМИ колонками */
        .products-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* ФИКСИРОВАННАЯ ШИРИНА */
        }
        
        /* Задаем фиксированную ширину каждой колонки */
        .products-table th:nth-child(1),
        .products-table td:nth-child(1) { width: 80px; } /* Фото */
        .products-table th:nth-child(2),
        .products-table td:nth-child(2) { width: 70px; } /* ID */
        .products-table th:nth-child(3),
        .products-table td:nth-child(3) { width: auto; } /* Название (занимает оставшееся место) */
        .products-table th:nth-child(4),
        .products-table td:nth-child(4) { width: 130px; } /* Категория */
        .products-table th:nth-child(5),
        .products-table td:nth-child(5) { width: 130px; } /* Цена */
        .products-table th:nth-child(6),
        .products-table td:nth-child(6) { width: 100px; } /* Статус */
        .products-table th:nth-child(7),
        .products-table td:nth-child(7) { width: 120px; } /* Действия */
        
        .products-table th {
            text-align: left;
            padding: 15px 10px;
            color: #666;
            font-weight: 500;
            border-bottom: 2px solid #f0e8e0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .products-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Разрешаем перенос для названия */
        .products-table td:nth-child(3) {
            white-space: normal;
            word-break: break-word;
        }
        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .price-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            text-align: center;
        }
        .badge-available {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-unavailable {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-direction: column;
        }
        .btn-small {
            padding: 6px 10px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: 0.3s;
            width: 100%;
            white-space: nowrap;
        }
        .btn-small:hover {
            transform: translateY(-2px);
        }
        .btn-update {
            background: #d8737f;
            color: white;
        }
        .btn-hide {
            background: #ff9800;
            color: white;
        }
        .btn-show {
            background: #4CAF50;
            color: white;
        }
        .btn-delete {
            background: #ff6b6b;
            color: white;
        }
        
        /* Кнопка "Показать ещё" */
        .show-more-container {
            text-align: center;
            margin-top: 25px;
        }
        .show-more-btn {
            background: none;
            border: 2px solid #d8737f;
            color: #d8737f;
            padding: 12px 35px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .show-more-btn:hover {
            background: #d8737f;
            color: white;
            transform: translateY(-2px);
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .hidden-row {
            display: none !important;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 992px) {
            .products-table th:nth-child(4),
            .products-table td:nth-child(4) { display: none; } /* Скрываем категорию */
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .products-table th:nth-child(2),
            .products-table td:nth-child(2) { display: none; } /* Скрываем ID */
        }
    </style>
</head>
<body>
    <div class="admin-container">
        
        <div class="admin-header">
            <h1><span>CITY</span>TORT • Админ-панель</h1>
            <div style="display: flex; gap: 10px;">
                <a href="kitchen.php" class="logout-btn" style="background: #4CAF50;">🍳 Кухня</a>
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="message">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-cake-candles"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['total_products'] ?></h3>
                    <p>Всего товаров</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-check" style="color: #28a745;"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['available'] ?></h3>
                    <p>В наличии</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-ban" style="color: #dc3545;"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['unavailable'] ?></h3>
                    <p>Нет в наличии</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['categories'] ?></h3>
                    <p>Категорий</p>
                </div>
            </div>
        </div>
        
        <!-- Кнопка добавления товара -->
        <button class="add-product-btn" onclick="toggleAddForm()">
            <i class="fa-solid fa-plus"></i> Добавить новый товар
        </button>
        
        <!-- Форма добавления товара (сверху) -->
        <div class="add-form-container" id="addForm">
            <div class="add-form">
                <h3><i class="fa-solid fa-plus-circle"></i> Добавить новый товар</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название товара *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Цена *</label>
                            <input type="number" step="0.01" name="price" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Категория *</label>
                            <select name="category_id" required>
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Тип товара *</label>
                            <select name="product_type" required>
                                <option value="">Выберите тип</option>
                                <option value="simple">Обычный</option>
                                <option value="classic_cake">Классический торт</option>
                                <option value="bento">Бенто</option>
                                <option value="cupcake">Капкейк</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" rows="3" placeholder="Описание товара..."></textarea>
                    </div>
                    
                    <div class="file-upload" onclick="document.getElementById('product_image').click()">
                        <i class="fa-solid fa-cloud-upload-alt"></i>
                        <p>Нажмите, чтобы загрузить фото</p>
                        <input type="file" id="product_image" name="product_image" accept="image/*" style="display: none;">
                    </div>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_available" checked>
                        <span>Товар в наличии (доступен для заказа)</span>
                    </label>
                    
                    <button type="submit" name="add_product" class="submit-btn">
                        <i class="fa-solid fa-plus"></i> Добавить товар
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Фильтры и поиск -->
        <div class="filters-section">
            <div class="filter-buttons">
                <button class="filter-btn active" id="filter-all" onclick="filterProducts('all')">Все (<?= $stats['total_products'] ?>)</button>
                <button class="filter-btn" id="filter-available" onclick="filterProducts('available')">В наличии (<?= $stats['available'] ?>)</button>
                <button class="filter-btn" id="filter-unavailable" onclick="filterProducts('unavailable')">Нет в наличии (<?= $stats['unavailable'] ?>)</button>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Поиск по названию..." onkeyup="searchProducts()">
            </div>
        </div>
        
        <!-- Товары с фиксированной таблицей -->
        <div class="products-section">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Фото</th>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php foreach ($products as $product): ?>
                    <tr class="product-row" 
                        data-available="<?= $product['is_available'] ?>" 
                        data-name="<?= mb_strtolower(htmlspecialchars($product['name'])) ?>">
                        <td>
                            <?php if ($product['image_url']): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="product-thumb" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; margin: 0 auto;">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>#<?= $product['id'] ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 3px;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="number" step="0.01" name="new_price" value="<?= $product['base_price'] ?>" class="price-input">
                                <button type="submit" name="update_price" class="btn-small btn-update" style="padding: 6px 8px;">✔</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($product['is_available']): ?>
                                <span class="badge-available"><i class="fa-solid fa-check"></i> В наличии</span>
                            <?php else: ?>
                                <span class="badge-unavailable"><i class="fa-solid fa-ban"></i> Нет</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="is_available" value="<?= $product['is_available'] ? 0 : 1 ?>">
                                    <button type="submit" name="toggle_availability" class="btn-small <?= $product['is_available'] ? 'btn-hide' : 'btn-show' ?>">
                                        <?= $product['is_available'] ? 'Скрыть' : 'Показать' ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Удалить товар?')">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn-small btn-delete">Удалить</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="show-more-container">
                <button class="show-more-btn" onclick="showMoreProducts()" id="showMoreBtn">
                    <i class="fa-solid fa-chevron-down"></i> Показать ещё
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let showingAll = false;
        let currentFilter = 'all';
        let searchTerm = '';
        const limit = <?= $display_limit ?>;

        function toggleAddForm() { 
            document.getElementById('addForm').classList.toggle('active'); 
        }

        function filterProducts(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('filter-' + filter).classList.add('active');
            applyFilters();
        }

        function searchProducts() {
            searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            applyFilters();
        }

        function applyFilters() {
            const rows = document.querySelectorAll('.product-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const isAvailable = row.dataset.available === '1';
                const name = row.dataset.name;
                
                // Проверка фильтра
                let filterMatch = true;
                if (currentFilter === 'available') filterMatch = isAvailable;
                if (currentFilter === 'unavailable') filterMatch = !isAvailable;
                
                // Проверка поиска
                let searchMatch = true;
                if (searchTerm) {
                    searchMatch = name.includes(searchTerm);
                }
                
                if (filterMatch && searchMatch) {
                    visibleCount++;
                    if (searchTerm.length > 0 || showingAll || visibleCount <= limit) {
                        row.classList.remove('hidden-row');
                    } else {
                        row.classList.add('hidden-row');
                    }
                } else {
                    row.classList.add('hidden-row');
                }
            });
            
            // Управление кнопкой "Показать ещё"
            const btn = document.getElementById('showMoreBtn');
            const btnContainer = btn.parentElement;
            
            if (visibleCount <= limit || searchTerm.length > 0) {
                btnContainer.style.display = 'none';
            } else {
                btnContainer.style.display = 'block';
                btn.innerHTML = showingAll ? 
                    '<i class="fa-solid fa-chevron-up"></i> Свернуть' : 
                    '<i class="fa-solid fa-chevron-down"></i> Показать ещё ' + (visibleCount - limit);
            }
        }

        function showMoreProducts() { 
            showingAll = !showingAll; 
            applyFilters(); 
        }

        // Запускаем при загрузке
        window.onload = function() {
            applyFilters();
            
            // Показываем имя выбранного файла
            document.getElementById('product_image')?.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    const uploadDiv = e.target.closest('.file-upload');
                    const p = uploadDiv.querySelector('p');
                    p.innerHTML = `<i class="fa-solid fa-check-circle" style="color: #4CAF50;"></i> Выбрано: ${fileName}`;
                }
            });
        };
    </script>
</body>
</html>