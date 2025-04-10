<?php
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "healthybites");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validation
    if (strlen($password) < 6 || !preg_match("#[0-9]+#", $password) || 
        !preg_match("#[A-Z]+#", $password) || !preg_match("#[a-z]+#", $password)) {
        $errors[] = "Password must be at least 6 characters long and include upper, lower case and numbers.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hash);

        if ($stmt->execute()) {
            echo "<script>alert('Signup successful! Please login.'); window.location='login.php';</script>";
        } else {
            $errors[] = "User already exists or DB error.";
        }
        $stmt->close();
    }

    $conn->close();
}
?>

<!-- Signup HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | HealthyBites</title>
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
      <a href="login.php">Login</a>
      <a href="checkout.html" class="cart-link">
        <span class="cart-icon">🛒</span>
        <span class="cart-count" style="display: none;">0</span>
      </a>
    </div>
  </nav>

  <div class="auth-container">
    <h2>Sign Up</h2>
    <?php foreach($errors as $error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" class="auth-form">
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Sign Up</button>
    </form>
    <div class="auth-links">
      <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
  </div>

  <footer class="footer">
    &copy; 2025 HealthyBites. All rights reserved.
  </footer>

  <script src="js/cart.js"></script>
</body>
</html>
