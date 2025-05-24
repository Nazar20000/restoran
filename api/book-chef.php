<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо войти в систему']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit();
}

$chef_id = (int)$_POST['chef_id'];
$booking_date = $_POST['booking_date'];
$start_time = $_POST['start_time'];
$duration_hours = (int)$_POST['duration_hours'];
$special_requests = trim($_POST['special_requests']);

// Валидация
if (empty($chef_id) || empty($booking_date) || empty($start_time) || empty($duration_hours)) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, заполните все обязательные поля']);
    exit();
}

// Проверка даты (не раньше завтра)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
if ($booking_date < $tomorrow) {
    echo json_encode(['success' => false, 'message' => 'Дата бронирования должна быть не раньше завтра']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Получение информации о поваре
    $query = "SELECT hourly_rate FROM chefs WHERE id = :chef_id AND available = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':chef_id', $chef_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Повар не найден или недоступен']);
        exit();
    }
    
    $chef = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_cost = $chef['hourly_rate'] * $duration_hours;

    // Проверка на конфликт времени
    $end_time = date('H:i:s', strtotime($start_time . ' +' . $duration_hours . ' hours'));
    
    $conflict_query = "SELECT id FROM chef_bookings 
                       WHERE chef_id = :chef_id 
                       AND booking_date = :booking_date 
                       AND status NOT IN ('cancelled')
                       AND (
                           (start_time <= :start_time AND DATE_ADD(CONCAT(booking_date, ' ', start_time), INTERVAL duration_hours HOUR) > :start_time)
                           OR 
                           (start_time < :end_time AND start_time >= :start_time)
                       )";
    
    $conflict_stmt = $db->prepare($conflict_query);
    $conflict_stmt->bindParam(':chef_id', $chef_id);
    $conflict_stmt->bindParam(':booking_date', $booking_date);
    $conflict_stmt->bindParam(':start_time', $start_time);
    $conflict_stmt->bindParam(':end_time', $end_time);
    $conflict_stmt->execute();
    
    if ($conflict_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'На выбранное время повар уже забронирован']);
        exit();
    }

    // Создание бронирования
    $insert_query = "INSERT INTO chef_bookings 
                     (user_id, chef_id, booking_date, start_time, duration_hours, total_cost, special_requests) 
                     VALUES (:user_id, :chef_id, :booking_date, :start_time, :duration_hours, :total_cost, :special_requests)";
    
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $insert_stmt->bindParam(':chef_id', $chef_id);
    $insert_stmt->bindParam(':booking_date', $booking_date);
    $insert_stmt->bindParam(':start_time', $start_time);
    $insert_stmt->bindParam(':duration_hours', $duration_hours);
    $insert_stmt->bindParam(':total_cost', $total_cost);
    $insert_stmt->bindParam(':special_requests', $special_requests);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Повар успешно забронирован! Мы свяжемся с вами для подтверждения.',
            'redirect' => 'profile.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при создании бронирования']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка: ' . $e->getMessage()]);
}
?>