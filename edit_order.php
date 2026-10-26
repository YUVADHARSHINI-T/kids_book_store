
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION["customer_id"];
$order_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$order_id || $order_id < 1) {
    exit("Invalid order ID.");
}

// Fetch only this customer's order.
$stmt = $conn->prepare(
    "SELECT * FROM store_orders
     WHERE id = ? AND customer_id = ?"
);
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    exit("Order not found.");
}

if ($order["order_status"] !== "Placed") {
    exit("Delivery details can no longer be edited for this order.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address = trim($_POST["address"] ?? "");
    $country_code = trim($_POST["country_code"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $allowed_codes = ["+91", "+1", "+44", "+61", "+971"];

    if ($address === "" || strlen($address) < 5) {
        $error = "Please enter a complete delivery address.";
    } elseif (!in_array($country_code, $allowed_codes, true)) {
        $error = "Please select a valid country code.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $error = "Enter a phone number containing 7–15 digits.";
    } else {
        $update = $conn->prepare(
            "UPDATE store_orders
             SET address = ?, country_code = ?, phone = ?
             WHERE id = ? AND customer_id = ?
             AND order_status = 'Placed'"
        );

        $update->bind_param(
            "sssii",
            $address,
            $country_code,
            $phone,
            $order_id,
            $customer_id
        );

        $update->execute();
        $update->close();

        header("Location: my_orders.php?updated=1");
        exit;
    }

    // Keep the submitted values visible if validation fails.
    $order["address"] = $address;
    $order["country_code"] = $country_code;
    $order["phone"] = $phone;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Delivery Details - Kids Book Store</title>

    <style>
        body {
            font-family: "Comic Sans MS", cursive;
            background: #fff8f3;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 550px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            text-align: center;
            color: #e87550;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        input, textarea, select {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font: inherit;
        }

        button, .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background: #ffcc80;
            color: #333;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }

        .error {
            color: #b00020;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Edit Delivery Details</h1>

    <p>Order number: #<?= (int) $order["id"] ?></p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label for="address">Delivery Address</label>
        <textarea id="address" name="address" rows="4" required><?= htmlspecialchars($order["address"], ENT_QUOTES, "UTF-8") ?></textarea>

        <label for="country_code">Country Code</label>
        <select id="country_code" name="country_code" required>
            <?php
            $codes = [
                "+91" => "India (+91)",
                "+1" => "USA/Canada (+1)",
                "+44" => "United Kingdom (+44)",
                "+61" => "Australia (+61)",
                "+971" => "UAE (+971)"
            ];

            foreach ($codes as $code => $label):
            ?>
                <option value="<?= $code ?>"
                    <?= $order["country_code"] === $code ? "selected" : "" ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="phone">Phone Number (digits only)</label>
        <input
            type="tel"
            id="phone"
            name="phone"
            pattern="[0-9]{7,15}"
            value="<?= htmlspecialchars($order["phone"], ENT_QUOTES, "UTF-8") ?>"
            required
        >

        <button type="submit">Save Changes</button>
        <a class="back-link" href="my_orders.php">Back to My Orders</a>
    </form>
</div>
</body>
</html>
