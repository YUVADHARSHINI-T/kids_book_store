
<?php
session_start();

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $message_text = trim($_POST["message"] ?? "");

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $subject === "" ||
        $message_text === ""
    ) {
        $message = "Please fill in all fields with valid details.";
    } else {
        $message = "Thank you for contacting Kids Book Store!";
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>We're happy to hear from you!</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="cart.php">Cart</a>
    <a href="about.php">About</a>

    <?php if (isset($_SESSION["customer_id"])): ?>
        <a href="my_orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="signup.php">Sign Up</a>
    <?php endif; ?>

    <a href="contact.php">Contact</a>
</nav>

<main>
    <h2>Contact Us</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form action="contact.php" method="post">
            <p>
                <input type="text" name="name"
                    placeholder="Your name" maxlength="100" required>
            </p>

            <p>
                <input type="email" name="email"
                    placeholder="Your email" maxlength="150" required>
            </p>

            <p>
                <input type="text" name="subject"
                    placeholder="Subject" maxlength="150" required>
            </p>

            <p>
                <textarea name="message"
                    placeholder="Your message" rows="5"
                    maxlength="3000" required></textarea>
            </p>

            <button type="submit">Send Message</button>
        </form>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
