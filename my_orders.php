
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION["customer_id"];

// Cancel an order only when it belongs to this customer
// and its status is still Placed.
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["cancel_order"])) {

    $order_id = filter_input(
        INPUT_POST,
        "order_id",
        FILTER_VALIDATE_INT
    );

    if ($order_id && $order_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE store_orders
             SET order_status = 'Cancelled'
             WHERE id = ?
             AND customer_id = ?
             AND order_status = 'Placed'"
        );

        $stmt->bind_param("ii", $order_id, $customer_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: my_orders.php");
    exit;
}

// Fetch only the logged-in customer's orders.
$stmt = $conn->prepare(
    "SELECT * FROM store_orders
     WHERE customer_id = ?
     ORDER BY order_date DESC"
);

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$orders = [];

while ($order_row = $orders_result->fetch_assoc()) {
    $orders[] = $order_row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Kids Book Store</title>

    <style>
        body {
            font-family: "Comic Sans MS", cursive;
            background: #fff8f3;
            margin: 0;
            color: #333;
        }

        header {
            background: #ffcc80;
            padding: 18px;
            text-align: center;
        }

        nav {
            background: #ffa58f;
            padding: 12px;
            text-align: center;
        }

        nav a {
            color: #333;
            text-decoration: none;
            margin: 0 10px;
        }

        .container {
            max-width: 900px;
            margin: 25px auto;
            padding: 15px;
        }

        .order {
            background: white;
            border: 1px solid #f0d6c8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .success {
            background: #e6f5e6;
            padding: 12px;
            border-radius: 5px;
            color: #236b23;
        }

        button, .action-link {
            display: inline-block;
            background: #ffcc80;
            color: #333;
            border: none;
            border-radius: 5px;
            padding: 9px 12px;
            margin: 5px 5px 5px 0;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }

        .cancel-button {
            background: #ffd6d6;
        }

        .items {
            padding-left: 22px;
        }
    </style>
</head>

<body>

<header>
    <h1>My Orders</h1>
    <p>Welcome, <?= htmlspecialchars(
        $_SESSION["customer_name"] ?? "Customer",
        ENT_QUOTES,
        "UTF-8"
    ) ?>!</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="cart.php">Cart</a>
    <a href="about.php">About</a>
    <a href="my_orders.php">My Orders</a>
    <a href="contact.php">Contact</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">

    <!-- SUCCESS MESSAGE: appears after delivery details are updated -->
    <?php if (
        isset($_GET["updated"]) &&
        $_GET["updated"] === "1"
    ): ?>
        <p class="success">
            Delivery details updated successfully!
        </p>
    <?php endif; ?>

    <!-- SUCCESS MESSAGE: appears after an order is placed -->
    <?php if (
        isset($_GET["placed"]) &&
        $_GET["placed"] === "1"
    ): ?>
        <p class="success">
            Your order has been placed successfully!
        </p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>

        <p>You have not placed any orders yet.</p>
        <a class="action-link" href="index.php">Browse Books</a>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <div class="order">

                <h2>Order #<?= (int) $order["id"] ?></h2>

                <p>
                    <strong>Order Date:</strong>
                    <?= htmlspecialchars(
                        $order["order_date"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars(
                        $order["order_status"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Name:</strong>
                    <?= htmlspecialchars(
                        $order["full_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?= htmlspecialchars(
                        $order["email"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Delivery Address:</strong>
                    <?= nl2br(htmlspecialchars(
                        $order["address"],
                        ENT_QUOTES,
                        "UTF-8"
                    )) ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?= htmlspecialchars(
                        $order["country_code"] . " " . $order["phone"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Payment:</strong>
                    <?= htmlspecialchars(
                        $order["payment_method"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    $<?= number_format(
                        (float) $order["total_amount"],
                        2
                    ) ?>
                </p>

                <h3>Books Ordered</h3>

                <ul class="items">
                    <?php
                    $items_stmt = $conn->prepare(
                        "SELECT books.book_name,
                                order_items.quantity,
                                order_items.price
                         FROM order_items
                         JOIN books
                           ON books.id = order_items.book_id
                         WHERE order_items.order_id = ?"
                    );

                    $items_stmt->bind_param("i", $order["id"]);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();

                    while ($item = $items_result->fetch_assoc()):
                    ?>
                        <li>
                            <?= htmlspecialchars(
                                $item["book_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                            — Quantity:
                            <?= (int) $item["quantity"] ?>
                            — $<?= number_format(
                                (float) $item["price"],
                                2
                            ) ?> each
                        </li>
                    <?php
                    endwhile;
                    $items_stmt->close();
                    ?>
                </ul>

                <!-- EDIT LINK: shown only while the order is Placed -->
                <?php if ($order["order_status"] === "Placed"): ?>
                    <a
                        class="action-link"
                        href="edit_order.php?id=<?= (int) $order["id"] ?>"
                    >
                        Edit Delivery Details
                    </a>

                    <!-- CANCEL BUTTON: shown only while the order is Placed -->
                    <form method="POST" style="display: inline;">
                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= (int) $order["id"] ?>"
                        >

                        <button
                            type="submit"
                            name="cancel_order"
                            class="cancel-button"
                            onclick="return confirm('Are you sure you want to cancel this order?');"
                        >
                            Cancel Order
                        </button>
                    </form>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>
