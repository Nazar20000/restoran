<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        // Проверка на существование пользователя
        if ($user->usernameExists($username)) {
            $error = 'Пользователь с таким именем уже существует';
        } elseif ($user->emailExists($email)) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            // Регистрация пользователя
            if ($user->register($username, $email, $password, $phone, $address)) {
                $success = 'Регистрация прошла успешно! Теперь вы можете войти в систему.';
            } else {
                $error = 'Ошибка при регистрации. Попробуйте снова.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Ресторан "Вкус"</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="auth-container">
            <h1 class="auth-title">Регистрация</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Имя пользователя *</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль *</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Подтвердите пароль *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Адрес</label>
                    <textarea id="address" name="address" class="form-input form-textarea"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Зарегистрироваться</button>
            </form>

            <div class="auth-link">
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>