<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../config/db_config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$cart = new Cart($conn);
$userId = $_SESSION['user_id'];

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Get cart contents
        $result = $cart->getCart($userId);
        echo json_encode($result);
        break;
        
    case 'POST':
        // Add item to cart
        if (!isset($data['menu_item_id']) || !isset($data['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        $result = $cart->addItem($userId, $data['menu_item_id'], $data['quantity']);
        echo json_encode($result);
        break;
        
    case 'PUT':
        // Update cart item quantity
        if (!isset($data['menu_item_id']) || !isset($data['quantity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        $result = $cart->updateQuantity($userId, $data['menu_item_id'], $data['quantity']);
        echo json_encode($result);
        break;
        
    case 'DELETE':
        if (isset($data['menu_item_id'])) {
            // Remove specific item
            $result = $cart->removeItem($userId, $data['menu_item_id']);
        } else {
            // Clear entire cart
            $result = $cart->clearCart($userId);
        }
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}
?>