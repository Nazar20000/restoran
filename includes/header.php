<?php
require_once 'classes/Cart.php';
$cart = new Cart();
$cart_count = $cart->getItemCount();
?>

<header class="header">
    <nav class="nav">
        <a href="index.php" class="logo">Ресторан "Вкус"</a>
        
        <ul class="nav-links">
            <li><a href="index.php">Главная</a></li>
            <li><a href="menu.php">Меню</a></li>
            <li><a href="chefs.php">Повара</a></li>
            <li><a href="about.php">О нас</a></li>
            <li><a href="contact.php">Контакты</a></li>
        </ul>

        <div class="user-menu">
            <button class="cart-icon" data-modal="cart-modal">
                🛒
                <span class="cart-count" style="<?= $cart_count > 0 ? '' : 'display: none;' ?>">
                    <?= $cart_count ?>
                </span>
            </button>

            <?php if (isLoggedIn()): ?>
                <span>Привет, <?= htmlspecialchars(getUsername()) ?>!</span>
                <a href="profile.php" class="btn btn-outline">Профиль</a>
                <a href="logout.php" class="btn">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Войти</a>
                <a href="register.php" class="btn">Регистрация</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<!-- Cart Modal -->
<div id="cart-modal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h2>Корзина</h2>
        <div class="cart-items">
            <!-- Cart items will be loaded here via JavaScript -->
        </div>
        <div class="cart-total" style="text-align: right; font-weight: bold; margin: 1rem 0;">
            Итого: 0 ₽
        </div>
        <div style="text-align: center;">
            <?php if (isLoggedIn()): ?>
                <a href="checkout.php" class="btn">Оформить заказ</a>
            <?php else: ?>
                <a href="login.php" class="btn">Войти для заказа</a>
            <?php endif; ?>
        </div>
    </div>
</div>