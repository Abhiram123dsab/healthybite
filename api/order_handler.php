<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/order.php';
require_once __DIR__ . '/../config/db_config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$order = new Order($conn);
$userId = $_SESSION['user_id'];

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['order_id'])) {
            // Get specific order
            $result = $order->getOrder($_GET['order_id'], $userId);
        } else {
            // Get all orders for user
            $result = $order->getUserOrders($userId);
        }
        echo json_encode($result);
        break;
        
    case 'POST':
        // Create new order from cart
        $result = $order->createOrder($userId);
        echo json_encode($result);
        break;
        
    case 'PUT':
        // Update order status (admin only)
        if (!isset($data['order_id']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        // TODO: Add admin check here
        $result = $order->updateOrderStatus($data['order_id'], $data['status']);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}
?>