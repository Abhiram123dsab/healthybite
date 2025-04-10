<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/auth.php';
require_once 'includes/order.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$order = new Order($conn);
$userId = $_SESSION['user_id'];
$orders = $order->getUserOrders($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | HealthyBites</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .order-header {
            background: #f5f5f5;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-items {
            padding: 1rem;
        }
        .order-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 1rem;
            border-radius: 4px;
        }
        .item-details {
            flex-grow: 1;
        }
        .order-total {
            padding: 1rem;
            background: #f9f9f9;
            border-top: 1px solid #eee;
            text-align: right;
            font-weight: bold;
        }
        .no-orders {
            text-align: center;
            padding: 2rem;
            background: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">HealthyBites</div>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="breakfast.html">Breakfast</a></li>
            <li><a href="lunch.html">Lunch</a></li>
            <li><a href="dinner.html">Dinner</a></li>
            <li><a href="snacks.html">Snacks</a></li>
            <li><a href="custom-juice.html">Custom Juice</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="contact.html">Contact</a></li>
        </ul>
        <div class="nav-buttons">
            <?php if ($auth->isLoggedIn()): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
            <a href="checkout.html" class="cart-link">
                <span class="cart-icon">🛒</span>
                <span class="cart-count" style="display: none;">0</span>
            </a>
        </div>
    </nav>

    <div class="orders-container">
        <h1>My Orders</h1>
        
        <?php if ($orders['success'] && !empty($orders['orders'])): ?>
            <?php foreach ($orders['orders'] as $orderData): ?>
                <?php $orderDetails = $order->getOrder($orderData['id'])['order']; ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo $orderData['id']; ?></h3>
                            <small><?php echo date('F j, Y g:i A', strtotime($orderData['created_at'])); ?></small>
                        </div>
                        <span class="order-status status-<?php echo strtolower($orderData['status']); ?>">
                            <?php echo ucfirst($orderData['status']); ?>
                        </span>
                    </div>
                    <div class="order-items">
                        <?php foreach ($orderDetails['items'] as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                    <p>$<?php echo number_format($item['unit_price'], 2); ?> each</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">
                        Total: $<?php echo number_format($orderData['total_amount'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-orders">
                <h2>No orders yet</h2>
                <p>Start shopping to place your first order!</p>
                <a href="index.html" class="btn">Browse Menu</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        &copy; 2025 HealthyBites. All rights reserved.
    </footer>

    <script src="js/cart.js"></script>
</body>
</html>