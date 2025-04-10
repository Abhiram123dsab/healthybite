<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | HealthyBites</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <nav class="navbar">
    <div class="logo">HealthyBites</div>
    <ul>
      <li><a href="index.html">Home</a></li>
      <li><a href="custom-juice.html">Custom Juice</a></li>
      <li><a href="about.html">About</a></li>
      <li><a href="contact.html">Contact</a></li>
    </ul>
    <div class="nav-buttons">
      <a href="logout.php">Logout</a>
      <a href="checkout.html" class="cart-link">
        <span class="cart-icon">🛒</span>
        <span class="cart-count" style="display: none;">0</span>
      </a>
    </div>
  </nav>

  <div class="auth-container">
    <h2>Welcome, <?php echo $_SESSION["username"]; ?>!</h2>
    <div style="text-align: center; margin: 2rem 0;">
      <p>This is your dashboard. You can view your order history and manage your account settings here.</p>
      <div style="margin-top: 2rem;">
        <h3 style="color: #218c74; margin-bottom: 1rem;">Your Recent Orders</h3>
        <p style="color: #666;">You haven't placed any orders yet.</p>
        <a href="index.html" class="btn" style="margin-top: 1rem;">Browse Menu</a>
      </div>
    </div>
  </div>

  <footer class="footer">
    &copy; 2025 HealthyBites. All rights reserved.
  </footer>

  <script src="js/cart.js"></script>
</body>
</html>
