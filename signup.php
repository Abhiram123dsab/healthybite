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
<html>
<head><title>Signup</title></head>
<body>
<h2>Signup</h2>
<?php foreach($errors as $error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
    Username: <input type="text" name="username" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    Confirm Password: <input type="password" name="confirm_password" required><br><br>
    <button type="submit">Sign Up</button>
</form>
</body>
</html>
