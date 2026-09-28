<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query cart items with book details
$query = "SELECT c.id as cart_id, c.book_id, b.title, b.author, b.price, c.quantity
          FROM cart c
          JOIN books b ON c.book_id = b.id
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total and item count
$total = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($address) || empty($phone)) {
        $error = "All fields are required.";
    } else {
        // Insert order with pending payment status
        $payment_status = 'pending';
        $stmt = $conn->prepare("INSERT INTO orders (user_id, name, email, phone, address, total, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssds", $user_id, $name, $email, $phone, $address, $total, $payment_status);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['book_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();
        }

        // Store pending order info in session and redirect to payment
        $_SESSION['pending_order_id'] = $order_id;
        $_SESSION['pending_total'] = $total;
        
        header("Location: payment.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Book Haven</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #e74c3c;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.5rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #e74c3c;
        }

        .cart-link {
            position: relative;
            display: inline-block;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .checkout-container {
            display: flex;
            gap: 2rem;
            margin: 2rem auto;
        }

        .order-summary, .checkout-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .order-summary {
            flex: 1;
        }

        .checkout-form {
            flex: 1;
        }

        .order-summary h2, .checkout-form h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #2c3e50;
            text-align: right;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }

        .btn:hover {
            background-color: #c0392b;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Book Haven</div>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php">Books</a></li>
                        <li><a href="cart.php" class="cart-link">Cart<?php if ($item_count > 0): ?><span class="cart-badge"><?php echo $item_count; ?></span><?php endif; ?></a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="checkout-container">
            <div class="order-summary">
                <h2>Order Summary</h2>
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                            <small>by <?php echo htmlspecialchars($item['author']); ?> - Quantity: <?php echo $item['quantity']; ?></small>
                        </div>
                        <div>NPR <?php echo number_format($item['price'] * $item['quantity'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="order-total">
                    Total: NPR <?php echo number_format($total, 0); ?>
                </div>
            </div>

            <div class="checkout-form">
                <h2>Shipping Details</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="checkout.php">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Shipping Address</label>
                        <textarea id="address" name="address" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn">Complete Order</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>