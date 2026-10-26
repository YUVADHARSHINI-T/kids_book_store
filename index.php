
<?php
session_start();
require_once "db.php";

$sql = "SELECT id, book_name, price, image, description FROM books";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Discover wonderful books for little readers!</p>
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
    <h2>Our Books</h2>

    <div class="book-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($book = $result->fetch_assoc()): ?>
                <div class="book-card">

                    <?php if (!empty($book['image'])): ?>
                        <img
                            src="<?= htmlspecialchars($book['image']) ?>"
                            alt="<?= htmlspecialchars($book['book_name']) ?>"
                        >
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($book['book_name']) ?></h3>

                    <p>
                        <?= htmlspecialchars($book['description'] ?? '') ?>
                    </p>

                    <p class="price">
                        $<?= number_format((float)$book['price'], 2) ?>
                    </p>

                    <form action="cart.php" method="post">
                        <input type="hidden" name="book_id"
                               value="<?= (int)$book['id'] ?>">
                        <button type="submit" name="add_to_cart">
                            Add to Cart
                        </button>
                    </form>

                    <form action="buy.php" method="get">
                        <input type="hidden" name="book_id"
                               value="<?= (int)$book['id'] ?>">
                        <button type="submit">Buy Now</button>
                    </form>

                    
                    <form action="checkout.php" method="get">
                        <input type="hidden" name="book_id"
                                value="<?= (int)$book['id'] ?>">
                        <button type="submit">Buy Now</button>
                    </form>


                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No books found. Check the books table in phpMyAdmin.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
