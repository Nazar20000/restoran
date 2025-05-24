<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Получение популярных блюд
$query = "SELECT d.*, c.name as category_name FROM dishes d 
          LEFT JOIN categories c ON d.category_id = c.id 
          WHERE d.available = 1 
          ORDER BY d.id DESC LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение категорий
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ресторан "Вкус" - Главная</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Добро пожаловать в ресторан "Вкус"</h1>
                <p>Изысканная кухня и незабываемые впечатления</p>
                <a href="menu.php" class="btn">Посмотреть меню</a>
                <a href="chefs.php" class="btn btn-secondary">Заказать повара</a>
            </div>
        </section>

        <!-- Featured Dishes -->
        <section class="container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Популярные блюда</h2>
            <div class="grid grid-3">
                <?php foreach ($featured_dishes as $dish): ?>
                <div class="card fade-in">
                    <img src="/placeholder.svg?height=200&width=300&query=<?= urlencode($dish['name']) ?>" 
                         alt="<?= htmlspecialchars($dish['name']) ?>" class="card-image">
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($dish['name']) ?></h3>
                        <p class="card-description"><?= htmlspecialchars($dish['description']) ?></p>
                        <div class="card-price"><?= number_format($dish['price'], 0, ',', ' ') ?> ₽</div>
                        <button class="btn add-to-cart" data-dish-id="<?= $dish['id'] ?>">
                            Добавить в корзину
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Categories -->
        <section class="container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Категории</h2>
            <div class="grid grid-4">
                <?php foreach ($categories as $category): ?>
                <div class="card fade-in">
                    <img src="/placeholder.svg?height=150&width=200&query=<?= urlencode($category['name']) ?>" 
                         alt="<?= htmlspecialchars($category['name']) ?>" class="card-image">
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($category['name']) ?></h3>
                        <p class="card-description"><?= htmlspecialchars($category['description']) ?></p>
                        <a href="menu.php?category=<?= $category['id'] ?>" class="btn">Смотреть блюда</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- About Section -->
        <section class="container">
            <div class="grid grid-2" style="align-items: center; gap: 3rem;">
                <div>
                    <h2>О нашем ресторане</h2>
                    <p style="margin-bottom: 1rem;">
                        Ресторан "Вкус" - это место, где традиции встречаются с инновациями. 
                        Наши опытные повара создают блюда из свежайших ингредиентов, 
                        а уютная атмосфера делает каждый визит незабываемым.
                    </p>
                    <p style="margin-bottom: 2rem;">
                        Мы также предлагаем уникальную услугу - заказ повара на дом. 
                        Наши профессиональные шеф-повара приедут к вам и приготовят 
                        изысканный ужин прямо у вас дома.
                    </p>
                    <a href="about.php" class="btn btn-outline">Узнать больше</a>
                </div>
                <div>
                    <img src="/placeholder.svg?height=400&width=500&query=restaurant+chef+cooking" 
                         alt="Наш шеф-повар" style="width: 100%; border-radius: 15px;">
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>