
<?php
session_start();
require_once "db.php";

if (isset($_SESSION["customer_id"])) {
    header("Location: index.php");
    exit;
}

$message = "";

if (isset($_GET["registered"])) {
    $message = "Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, full_name, username, password_hash
         FROM customers
         WHERE username = ? OR email = ?
         LIMIT 1"
    );

    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    if ($customer && password_verify($password, $customer["password_hash"])) {
        session_regenerate_id(true);

        $_SESSION["customer_id"] = $customer["id"];
        $_SESSION["customer_name"] = $customer["full_name"];

        header("Location: index.php");
        exit;
    } else {
        $message = "Incorrect username/email or password.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Welcome back!</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="cart.php">Cart</a>
    <a href="about.php">About</a>
    <a href="login.php">Login</a>
    <a href="signup.php">Sign Up</a>
    <a href="contact.php">Contact</a>
</nav>

<main>
    <h2>Customer Login</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <p>
            <input type="text" name="username"
                placeholder="Username or email"
                autocomplete="username" required>
        </p>

        <p>
            <input type="password" name="password"
                placeholder="Password"
                autocomplete="current-password" required>
        </p>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
