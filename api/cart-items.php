<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

if (empty($items)) {
    echo json_encode([]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $dish_ids = array_keys($items);
    $placeholders = str_repeat('?,', count($dish_ids) - 1) . '?';
    
    $query = "SELECT id, name, price FROM dishes WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($dish_ids);
    
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $items[$row['id']]
        ];
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([]);
}
?>