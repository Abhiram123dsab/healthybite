<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/cart.php';

class Order {
    private $conn;
    private $cart;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->cart = new Cart($db);
    }
    
    public function createOrder($userId) {
        try {
            $this->conn->beginTransaction();
            
            // Get cart items
            $cartResult = $this->cart->getCart($userId);
            if (!$cartResult['success'] || empty($cartResult['items'])) {
                throw new Exception('Cart is empty');
            }
            
            // Create order
            $stmt = $this->conn->prepare(
                "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')"
            );
            $stmt->execute([$userId, $cartResult['total']]);
            $orderId = $this->conn->lastInsertId();
            
            // Add order items
            $stmt = $this->conn->prepare(
                "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) 
                 VALUES (?, ?, ?, ?)"
            );
            
            foreach ($cartResult['items'] as $item) {
                $stmt->execute([
                    $orderId,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // Clear the cart
            $this->cart->clearCart($userId);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ];
        }
    }
    
    public function getOrder($orderId, $userId = null) {
        try {
            $sql = "
                SELECT o.*, oi.menu_item_id, oi.quantity, oi.unit_price,
                       m.name as item_name, m.image_url
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN menu_items m ON oi.menu_item_id = m.id
                WHERE o.id = ?"
            ;
            $params = [$orderId];
            
            if ($userId !== null) {
                $sql .= " AND o.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($items)) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            $order = [
                'id' => $items[0]['id'],
                'user_id' => $items[0]['user_id'],
                'total_amount' => $items[0]['total_amount'],
                'status' => $items[0]['status'],
                'created_at' => $items[0]['created_at'],
                'items' => array_map(function($item) {
                    return [
                        'menu_item_id' => $item['menu_item_id'],
                        'name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'image_url' => $item['image_url']
                    ];
                }, $items)
            ];
            
            return ['success' => true, 'order' => $order];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve order: ' . $e->getMessage()
            ];
        }
    }
    
    public function getUserOrders($userId) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$userId]);
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'orders' => $orders];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve orders: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateOrderStatus($orderId, $status) {
        try {
            $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $this->conn->prepare(
                "UPDATE orders SET status = ? WHERE id = ?"
            );
            $stmt->execute([$status, $orderId]);
            
            return ['success' => true, 'message' => 'Order status updated'];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ];
        }
    }
}
?>