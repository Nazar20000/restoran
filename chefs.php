<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Получение списка поваров
$query = "SELECT * FROM chefs WHERE available = 1 ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Наши повара - Ресторан "Вкус"</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 1rem;">Наши повара</h1>
            <p style="text-align: center; margin-bottom: 3rem; color: #666; font-size: 1.1rem;">
                Закажите профессионального повара на дом для особого случая
            </p>

            <div class="grid grid-3">
                <?php foreach ($chefs as $chef): ?>
                <div class="card chef-card fade-in">
                    <img src="/placeholder.svg?height=150&width=150&query=professional+chef+portrait" 
                         alt="<?= htmlspecialchars($chef['name']) ?>" class="chef-image">
                    <div class="card-content">
                        <h3 class="chef-name"><?= htmlspecialchars($chef['name']) ?></h3>
                        <div class="chef-specialization"><?= htmlspecialchars($chef['specialization']) ?></div>
                        <div class="chef-experience">Опыт: <?= $chef['experience_years'] ?> лет</div>
                        <p class="card-description"><?= htmlspecialchars($chef['description']) ?></p>
                        <div class="chef-rate"><?= number_format($chef['hourly_rate'], 0, ',', ' ') ?> ₽/час</div>
                        
                        <?php if (isLoggedIn()): ?>
                            <button class="btn" data-modal="booking-modal" 
                                    onclick="selectChef(<?= $chef['id'] ?>, '<?= htmlspecialchars($chef['name']) ?>', <?= $chef['hourly_rate'] ?>)">
                                Заказать
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn">Войти для заказа</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Информация об услуге -->
            <div style="background: white; padding: 2rem; border-radius: 15px; margin-top: 3rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h2 style="text-align: center; margin-bottom: 2rem;">Как это работает</h2>
                <div class="grid grid-3">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">1️⃣</div>
                        <h3>Выберите повара</h3>
                        <p>Ознакомьтесь с профилями наших поваров и выберите подходящего специалиста</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">2️⃣</div>
                        <h3>Забронируйте время</h3>
                        <p>Выберите удобную дату и время, укажите количество часов</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">3️⃣</div>
                        <h3>Наслаждайтесь</h3>
                        <p>Повар приедет к вам домой и приготовит изысканные блюда</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Booking Modal -->
    <?php if (isLoggedIn()): ?>
    <div id="booking-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Заказ повара на дом</h2>
            <form id="booking-form" action="api/book-chef.php" method="POST" class="ajax-form">
                <input type="hidden" id="chef-id" name="chef_id">
                
                <div class="form-group">
                    <label class="form-label">Выбранный повар</label>
                    <div id="selected-chef" style="font-weight: bold; color: #667eea;"></div>
                </div>

                <div class="form-group">
                    <label for="booking-date" class="form-label">Дата</label>
                    <input type="date" id="booking-date" name="booking_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="start-time" class="form-label">Время начала</label>
                    <input type="time" id="start-time" name="start_time" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="duration" class="form-label">Продолжительность (часы)</label>
                    <select id="duration" name="duration_hours" class="form-input" required onchange="calculateTotal()">
                        <option value="">Выберите продолжительность</option>
                        <option value="2">2 часа</option>
                        <option value="3">3 часа</option>
                        <option value="4">4 часа</option>
                        <option value="5">5 часов</option>
                        <option value="6">6 часов</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="special-requests" class="form-label">Особые пожелания</label>
                    <textarea id="special-requests" name="special_requests" class="form-input form-textarea" 
                              placeholder="Укажите ваши предпочтения в еде, аллергии, особые требования..."></textarea>
                </div>

                <div class="form-group">
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Стоимость за час:</span>
                            <span id="hourly-rate">0 ₽</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Количество часов:</span>
                            <span id="hours-count">0</span>
                        </div>
                        <hr>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                            <span>Итого:</span>
                            <span id="total-cost">0 ₽</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Забронировать</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        let selectedChefRate = 0;

        function selectChef(chefId, chefName, hourlyRate) {
            document.getElementById('chef-id').value = chefId;
            document.getElementById('selected-chef').textContent = chefName;
            document.getElementById('hourly-rate').textContent = hourlyRate.toLocaleString('ru-RU') + ' ₽';
            selectedChefRate = hourlyRate;
            calculateTotal();
        }

        function calculateTotal() {
            const duration = parseInt(document.getElementById('duration').value) || 0;
            const total = selectedChefRate * duration;
            
            document.getElementById('hours-count').textContent = duration;
            document.getElementById('total-cost').textContent = total.toLocaleString('ru-RU') + ' ₽';
        }

        // Установить минимальную дату - завтра
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('booking-date').min = tomorrow.toISOString().split('T')[0];
        });
    </script>
</body>
</html>