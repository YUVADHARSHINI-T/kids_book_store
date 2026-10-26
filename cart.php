
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION["customer_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["add_to_cart"])) {
        $book_id = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);

        if ($book_id && $book_id > 0) {
            $check = $conn->prepare("SELECT id FROM books WHERE id = ?");
            $check->bind_param("i", $book_id);
            $check->execute();
            $valid_book = $check->get_result()->fetch_assoc();
            $check->close();

            if ($valid_book) {
                $stmt = $conn->prepare(
                    "INSERT INTO cart_items (customer_id, book_id, quantity)
                     VALUES (?, ?, 1)
                     ON DUPLICATE KEY UPDATE quantity = quantity + 1"
                );
                $stmt->bind_param("ii", $customer_id, $book_id);
                $stmt->execute();
                $stmt->close();

                header("Location: cart.php");
                exit;
            }
        }

        $message = "Please select a valid book.";
    }

    if (isset($_POST["update_quantity"])) {
        $cart_id = filter_input(INPUT_POST, "cart_id", FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

        if ($cart_id && $cart_id > 0 && $quantity !== false && $quantity !== null) {
            if ($quantity < 1) {
                $stmt = $conn->prepare(
                    "DELETE FROM cart_items
                     WHERE id = ? AND customer_id = ?"
                );
                $stmt->bind_param("ii", $cart_id, $customer_id);
            } elseif ($quantity <= 99) {
                $stmt = $conn->prepare(
                    "UPDATE cart_items SET quantity = ?
                     WHERE id = ? AND customer_id = ?"
                );
                $stmt->bind_param("iii", $quantity, $cart_id, $customer_id);
            } else {
                $stmt = null;
                $message = "Maximum quantity is 99.";
            }

            if ($stmt) {
                $stmt->execute();
                $stmt->close();
                header("Location: cart.php");
                exit;
            }
        } else {
            $message = "Enter a valid quantity.";
        }
    }

    if (isset($_POST["remove_item"])) {
        $cart_id = filter_input(INPUT_POST, "cart_id", FILTER_VALIDATE_INT);

        if ($cart_id && $cart_id > 0) {
            $stmt = $conn->prepare(
                "DELETE FROM cart_items
                 WHERE id = ? AND customer_id = ?"
            );
            $stmt->bind_param("ii", $cart_id, $customer_id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: cart.php");
        exit;
    }
}

$sql = "SELECT c.id AS cart_id, c.quantity, b.id AS book_id,
               b.book_name, b.price, b.image
        FROM cart_items c
        JOIN books b ON b.id = c.book_id
        WHERE c.customer_id = ?
        ORDER BY c.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;

while ($item = $result->fetch_assoc()) {
    $item["subtotal"] = (float)$item["price"] * (int)$item["quantity"];
    $total += $item["subtotal"];
    $items[] = $item;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Your shopping cart</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="cart.php">Cart</a>
    <a href="about.php">About</a>
    <a href="my_orders.php">My Orders</a>
    <a href="contact.php">Contact</a>
    <a href="logout.php">Logout</a>
</nav>

<main>
    <h2>My Cart</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (count($items) === 0): ?>
        <p>Your cart is empty.</p>
        <a href="index.php">Browse Books</a>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="book-card">
                <?php if (!empty($item["image"])): ?>
                    <img
                        src="<?= htmlspecialchars($item["image"]) ?>"
                        alt="<?= htmlspecialchars($item["book_name"]) ?>"
                    >
                <?php endif; ?>

                <h3><?= htmlspecialchars($item["book_name"]) ?></h3>

                <p>Price: $<?= number_format((float)$item["price"], 2) ?></p>
                <p>Subtotal: $<?= number_format($item["subtotal"], 2) ?></p>

                <form method="post" action="cart.php">
                    <input type="hidden" name="cart_id"
                           value="<?= (int)$item["cart_id"] ?>">
                    <label>
                        Quantity:
                        <input type="number" name="quantity"
                               min="0" max="99"
                               value="<?= (int)$item["quantity"] ?>" required>
                    </label>
                    <button type="submit" name="update_quantity">
                        Update Quantity
                    </button>
                    <button type="submit" name="remove_item">
                        Remove
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

        <h2>Total: $<?= number_format($total, 2) ?></h2>
        <a href="checkout.php">Proceed to Checkout</a>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
