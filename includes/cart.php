<?php
require_once __DIR__ . '/../config/db_config.php';

class Cart {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function addItem($userId, $menuItemId, $quantity = 1) {
        try {
            // Check if item already exists in cart
            $stmt = $this->conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
            $stmt->execute([$userId, $menuItemId]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update quantity if item exists
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Insert new item if it doesn't exist
                $stmt = $this->conn->prepare("INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $menuItemId, $quantity]);
            }
            
            return ['success' => true, 'message' => 'Item added to cart'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add item: ' . $e->getMessage()];
        }
    }
    
    public function updateQuantity($userId, $menuItemId, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($userId, $menuItemId);
            }
            
            $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND menu_item_id = ?");
            $stmt->execute([$quantity, $userId, $menuItemId]);
            
            return ['success' => true, 'message' => 'Cart updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update cart: ' . $e->getMessage()];
        }
    }
    
    public function removeItem($userId, $menuItemId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ? AND menu_item_id = ?");
            $stmt->execute([$userId, $menuItemId]);
            
            return ['success' => true, 'message' => 'Item removed from cart'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove item: ' . $e->getMessage()];
        }
    }
    
    public function getCart($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, m.name, m.price, m.image_url 
                FROM cart c 
                JOIN menu_items m ON c.menu_item_id = m.id 
                WHERE c.user_id = ?");
            $stmt->execute([$userId]);
            
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = 0;
            
            foreach ($cartItems as &$item) {
                $item['subtotal'] = $item['price'] * $item['quantity'];
                $total += $item['subtotal'];
            }
            
            return [
                'success' => true,
                'items' => $cartItems,
                'total' => $total
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to retrieve cart: ' . $e->getMessage()];
        }
    }
    
    public function clearCart($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return ['success' => true, 'message' => 'Cart cleared'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to clear cart: ' . $e->getMessage()];
        }
    }
}
?>