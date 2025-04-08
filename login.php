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
<html>
<head><title>Login</title></head>
<body>
<h2>Login</h2>
<?php foreach($errors as $error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>
</body>
</html>
