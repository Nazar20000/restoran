<?php
class Cart {
    public function __construct() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addItem($dish_id, $quantity = 1) {
        if (isset($_SESSION['cart'][$dish_id])) {
            $_SESSION['cart'][$dish_id] += $quantity;
        } else {
            $_SESSION['cart'][$dish_id] = $quantity;
        }
    }

    public function removeItem($dish_id) {
        unset($_SESSION['cart'][$dish_id]);
    }

    public function updateQuantity($dish_id, $quantity) {
        if ($quantity <= 0) {
            $this->removeItem($dish_id);
        } else {
            $_SESSION['cart'][$dish_id] = $quantity;
        }
    }

    public function getItems() {
        return $_SESSION['cart'] ?? [];
    }

    public function getItemCount() {
        return array_sum($_SESSION['cart'] ?? []);
    }

    public function clear() {
        $_SESSION['cart'] = [];
    }

    public function getTotal($conn) {
        $total = 0;
        $items = $this->getItems();
        
        if (!empty($items)) {
            $dish_ids = implode(',', array_keys($items));
            $query = "SELECT id, price FROM dishes WHERE id IN ($dish_ids)";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $total += $row['price'] * $items[$row['id']];
            }
        }
        
        return $total;
    }
}
?>