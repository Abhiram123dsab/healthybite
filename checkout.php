<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | HealthyBites</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .cart-items {
            margin-bottom: 2rem;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 1rem;
        }
        .item-details {
            flex-grow: 1;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .quantity-btn {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ddd;
            background: #f5f5f5;
            cursor: pointer;
        }
        .order-summary {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            border-top: 2px solid #ddd;
            padding-top: 1rem;
        }
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        .checkout-btn:hover {
            background: #45a049;
        }
        .empty-cart {
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

    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <div id="cart-content">
            <!-- Cart items will be dynamically loaded here -->
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="summary-row">
                <span>Tax (10%)</span>
                <span id="tax">$0.00</span>
            </div>
            <div class="summary-row total-row">
                <span>Total</span>
                <span id="total">$0.00</span>
            </div>
            <button id="place-order" class="checkout-btn">Place Order</button>
        </div>
    </div>

    <footer class="footer">
        &copy; 2025 HealthyBites. All rights reserved.
    </footer>

    <script src="js/cart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = window.healthyBitesCart;
            const cartContent = document.getElementById('cart-content');
            const subtotalElement = document.getElementById('subtotal');
            const taxElement = document.getElementById('tax');
            const totalElement = document.getElementById('total');
            const placeOrderButton = document.getElementById('place-order');

            function updateCartDisplay() {
                if (!cart.items.length) {
                    cartContent.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
                    placeOrderButton.disabled = true;
                    return;
                }

                const itemsHtml = cart.items.map(item => `
                    <div class="cart-item">
                        <img src="${item.image}" alt="${item.name}">
                        <div class="item-details">
                            <h3>${item.name}</h3>
                            <p>$${item.price.toFixed(2)}</p>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="window.healthyBitesCart.updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn" onclick="window.healthyBitesCart.updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                `).join('');

                cartContent.innerHTML = `<div class="cart-items">${itemsHtml}</div>`;

                const subtotal = cart.getTotal();
                const tax = subtotal * 0.1;
                const total = subtotal + tax;

                subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
                taxElement.textContent = `$${tax.toFixed(2)}`;
                totalElement.textContent = `$${total.toFixed(2)}`;
                placeOrderButton.disabled = false;
            }

            // Update display when cart changes
            window.addEventListener('storage', updateCartDisplay);
            updateCartDisplay();

            // Handle order placement
            placeOrderButton.addEventListener('click', async function() {
                try {
                    placeOrderButton.disabled = true;
                    placeOrderButton.textContent = 'Processing...';

                    const result = await cart.createOrder();
                    if (result.success) {
                        alert('Order placed successfully!');
                        window.location.href = 'orders.php';
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    alert('Failed to place order: ' + error.message);
                    placeOrderButton.disabled = false;
                    placeOrderButton.textContent = 'Place Order';
                }
            });
        });
    </script>
</body>
</html>