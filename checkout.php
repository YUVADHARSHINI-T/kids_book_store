
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int)$_SESSION["customer_id"];
$message = "";
$buy_now = isset($_GET["book_id"]) || isset($_POST["buy_now_book_id"]);
$selected_book_id = 0;

if (isset($_GET["book_id"])) {
    $selected_book_id = (int)$_GET["book_id"];
} elseif (isset($_POST["buy_now_book_id"])) {
    $selected_book_id = (int)$_POST["buy_now_book_id"];
}

$selected_book = null;

if ($buy_now && $selected_book_id > 0) {
    $stmt = $conn->prepare(
        "SELECT id, book_name, price FROM books WHERE id = ?"
    );
    $stmt->bind_param("i", $selected_book_id);
    $stmt->execute();
    $selected_book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$selected_book) {
        exit("Book not found.");
    }
}

$cart_items = [];

if (!$buy_now) {
    $stmt = $conn->prepare(
        "SELECT b.id, b.book_name, b.price, c.quantity
         FROM cart_items c
         JOIN books b ON b.id = c.book_id
         WHERE c.customer_id = ?"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($item = $result->fetch_assoc()) {
        $cart_items[] = $item;
    }
    $stmt->close();

    if (!$cart_items) {
        exit("Your cart is empty. <a href='index.php'>Browse books</a>");
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["place_order"])) {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $country_code = $_POST["country_code"] ?? "";
    $phone = trim($_POST["phone"] ?? "");

    $allowed_codes = ["+91", "+1", "+44", "+61", "+971"];

    if (
        $full_name === "" || strlen($full_name) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $address === "" || strlen($address) > 2000 ||
        !in_array($country_code, $allowed_codes, true) ||
        !preg_match('/^[0-9]{10}$/', $phone)
    ) {
        $message = "Please enter valid details. Phone must contain 10 digits.";
    } else {
        $items_to_order = [];
        $total = 0;

        if ($buy_now && $selected_book) {
            $items_to_order[] = [
                "id" => (int)$selected_book["id"],
                "name" => $selected_book["book_name"],
                "price" => (float)$selected_book["price"],
                "quantity" => 1
            ];
        } else {
            $stmt = $conn->prepare(
                "SELECT b.id, b.book_name, b.price, c.quantity
                 FROM cart_items c
                 JOIN books b ON b.id = c.book_id
                 WHERE c.customer_id = ?"
            );
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($item = $result->fetch_assoc()) {
                $items_to_order[] = $item;
            }
            $stmt->close();
        }

        foreach ($items_to_order as $item) {
            $total += (float)$item["price"] * (int)$item["quantity"];
        }

        if (!$items_to_order || $total <= 0) {
            $message = "No valid books are available to order.";
        } else {
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO store_orders
                     (customer_id, full_name, email, address,
                      country_code, phone, total_amount, payment_method)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Cash on Delivery')"
                );

                $stmt->bind_param(
                    "isssssd",
                    $customer_id,
                    $full_name,
                    $email,
                    $address,
                    $country_code,
                    $phone,
                    $total
                );
                $stmt->execute();
                $order_id = $conn->insert_id;
                $stmt->close();

                $item_stmt = $conn->prepare(
                    "INSERT INTO order_items
                     (order_id, book_id, quantity, price)
                     VALUES (?, ?, ?, ?)"
                );

                foreach ($items_to_order as $item) {
                    $book_id = (int)$item["id"];
                    $quantity = (int)$item["quantity"];
                    $price = (float)$item["price"];

                    $item_stmt->bind_param(
                        "iiid",
                        $order_id,
                        $book_id,
                        $quantity,
                        $price
                    );
                    $item_stmt->execute();
                }
                $item_stmt->close();

                if (!$buy_now) {
                    $stmt = $conn->prepare(
                        "DELETE FROM cart_items WHERE customer_id = ?"
                    );
                    $stmt->bind_param("i", $customer_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();

                header("Location: my_orders.php?placed=1");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $message = "Order could not be placed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kids Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Kids Book Store</h1>
    <p>Complete your order</p>
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
    <h2>Checkout</h2>

    <?php if ($message !== ""): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h3>Order Summary</h3>

    <?php if ($buy_now && $selected_book): ?>
        <p>
            <?= htmlspecialchars($selected_book["book_name"]) ?>
            — $<?= number_format((float)$selected_book["price"], 2) ?>
        </p>
    <?php else: ?>
        <?php foreach ($cart_items as $item): ?>
            <p>
                <?= htmlspecialchars($item["book_name"]) ?>
                × <?= (int)$item["quantity"] ?>
                — $<?= number_format(
                    (float)$item["price"] * (int)$item["quantity"], 2
                ) ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="checkout.php<?= $buy_now && $selected_book ? '?book_id=' . (int)$selected_book['id'] : '' ?>">
        <?php if ($buy_now && $selected_book): ?>
            <input type="hidden" name="buy_now_book_id"
                   value="<?= (int)$selected_book["id"] ?>">
        <?php endif; ?>

        <p>
            <input type="text" name="full_name"
                   placeholder="Full name" maxlength="100" required>
        </p>

        <p>
            <input type="email" name="email"
                   placeholder="Email address" required>
        </p>

        <p>
            <textarea name="address" placeholder="Delivery address"
                      maxlength="2000" required></textarea>
        </p>

        <p>
            <select name="country_code" required>
                <option value="+91">India (+91)</option>
                <option value="+1">USA/Canada (+1)</option>
                <option value="+44">UK (+44)</option>
                <option value="+61">Australia (+61)</option>
                <option value="+971">UAE (+971)</option>
            </select>
        </p>

        <p>
            <input type="tel" name="phone"
                   placeholder="10-digit phone number"
                   pattern="[0-9]{10}" maxlength="10" required>
        </p>

        <p>
            Payment method: <strong>Cash on Delivery</strong>
        </p>

        <button type="submit" name="place_order">
            Place Order
        </button>
    </form>
</main>

<footer>
    <p>&copy; 2026 Kids Book Store</p>
</footer>

</body>
</html>
