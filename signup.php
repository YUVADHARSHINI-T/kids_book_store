
<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $username === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $message = "Enter valid details. Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM customers WHERE username = ? OR email = ?"
        );
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $message = "Username or email already registered. Please log in.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO customers
                (full_name, username, email, password_hash)
                VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssss",
                $full_name,
                $username,
                $email,
                $password_hash
            );

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Create your customer account</p>
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
    <h2>Sign Up</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" action="signup.php">
        <p>
            <input type="text" name="full_name"
                placeholder="Full name" maxlength="100"
                autocomplete="name" required>
        </p>

        <p>
            <input type="text" name="username"
                placeholder="Choose a username" maxlength="50"
                autocomplete="username" required>
        </p>

        <p>
            <input type="email" name="email"
                placeholder="Email address" maxlength="150"
                autocomplete="email" required>
        </p>

        <p>
            <input type="password" name="password"
                placeholder="Password (at least 8 characters)"
                minlength="8" autocomplete="new-password" required>
        </p>

        <p>
            <input type="password" name="confirm_password"
                placeholder="Confirm password"
                minlength="8" autocomplete="new-password" required>
        </p>

        <button type="submit">Create Account</button>
    </form>

    <p>Already registered? <a href="login.php">Log in here</a>.</p>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
