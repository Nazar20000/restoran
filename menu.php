<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Получение категорий для фильтра
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Фильтрация по категории
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Построение SQL запроса
$sql = "SELECT d.*, c.name as category_name FROM dishes d 
        LEFT JOIN categories c ON d.category_id = c.id 
        WHERE d.available = 1";

$params = [];

if ($category_filter > 0) {
    $sql .= " AND d.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (d.name LIKE :search OR d.description LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY d.name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Меню - Ресторан "Вкус"</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem;">Наше меню</h1>

            <!-- Фильтры -->
            <div style="background: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <form method="GET" action="" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                        <label for="search" class="form-label">Поиск блюд</label>
                        <input type="text" id="search" name="search" class="form-input" 
                               placeholder="Введите название блюда..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                        <label for="category" class="form-label">Категория</label>
                        <select id="category" name="category" class="form-input">
                            <option value="0">Все категории</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn">Найти</button>
                    <?php if ($category_filter > 0 || !empty($search_query)): ?>
                        <a href="menu.php" class="btn btn-outline">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Результаты поиска -->
            <?php if (!empty($search_query) || $category_filter > 0): ?>
                <div class="alert alert-info">
                    Найдено блюд: <?= count($dishes) ?>
                    <?php if (!empty($search_query)): ?>
                        по запросу "<?= htmlspecialchars($search_query) ?>"
                    <?php endif; ?>
                    <?php if ($category_filter > 0): ?>
                        в категории "<?= htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['name'] ?? '') ?>"
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Блюда -->
            <?php if (empty($dishes)): ?>
                <div class="alert alert-info" style="text-align: center;">
                    <h3>Блюда не найдены</h3>
                    <p>Попробуйте изменить параметры поиска или <a href="menu.php">посмотреть все блюда</a></p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($dishes as $dish): ?>
                    <div class="card fade-in">
                        <img src="/placeholder.svg?height=200&width=300&query=<?= urlencode($dish['name']) ?>" 
                             alt="<?= htmlspecialchars($dish['name']) ?>" class="card-image">
                        <div class="card-content">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <h3 class="card-title"><?= htmlspecialchars($dish['name']) ?></h3>
                                <span style="background: #667eea; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">
                                    <?= htmlspecialchars($dish['category_name']) ?>
                                </span>
                            </div>
                            <p class="card-description"><?= htmlspecialchars($dish['description']) ?></p>
                            <div class="card-price"><?= number_format($dish['price'], 0, ',', ' ') ?> ₽</div>
                            
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="number" value="1" min="1" max="10" 
                                       style="width: 60px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"
                                       id="quantity-<?= $dish['id'] ?>">
                                <button class="btn add-to-cart" data-dish-id="<?= $dish['id'] ?>" 
                                        onclick="this.dataset.quantity = document.getElementById('quantity-<?= $dish['id'] ?>').value">
                                    Добавить в корзину
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>