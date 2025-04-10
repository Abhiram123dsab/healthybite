<?php
session_start();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "healthybites");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["username"] = $username;
            echo "<script>alert('Login successful!'); window.location='dashboard.php';</script>";
        } else {
            $errors[] = "Invalid password.";
        }
    } else {
        $errors[] = "No account found with this email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!-- Login HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | HealthyBites</title>
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
      <a href="signup.php">Sign Up</a>
      <a href="checkout.html" class="cart-link">
        <span class="cart-icon">🛒</span>
        <span class="cart-count" style="display: none;">0</span>
      </a>
    </div>
  </nav>

  <div class="auth-container">
    <h2>Login</h2>
    <?php foreach($errors as $error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" class="auth-form">
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <div class="auth-links">
      <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
    </div>
  </div>

  <footer class="footer">
    &copy; 2025 HealthyBites. All rights reserved.
  </footer>

  <script src="js/cart.js"></script>
</body>
</html>
