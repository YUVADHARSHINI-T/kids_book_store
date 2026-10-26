
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Stories, imagination, and happy little readers!</p>
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
    <h2>About Our Store</h2>

    <p>
        Welcome to Kids Book Store! We offer children's books
        that encourage reading, creativity, and imagination.
    </p>

    <p>
        Our collection includes books such as Shady Baby Feels,
        A Good Thing Happened Today, and Little Bat Up All Day.
    </p>

    <p>
        Browse our collection, add your favourite books to your cart,
        and place your order using Cash on Delivery.
    </p>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
