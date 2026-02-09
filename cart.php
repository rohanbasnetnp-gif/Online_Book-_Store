<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query cart items with book details
$query = "SELECT c.id as cart_id, b.title, b.author, b.price, c.quantity
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Book Haven</title>
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

        .cart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 2rem auto;
            padding: 2rem;
        }

        .cart-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .cart-header h1 {
            color: #2c3e50;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .item-author {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .item-price {
            color: #e74c3c;
            font-weight: bold;
        }

        .item-quantity {
            margin: 0 1rem;
        }

        .item-total {
            font-weight: bold;
            color: #2c3e50;
        }

        .remove-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .remove-btn:hover {
            background-color: #c0392b;
        }

        .cart-total {
            text-align: right;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #2c3e50;
        }

        .cart-total h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-cart a {
            color: #e74c3c;
            text-decoration: none;
        }

        .empty-cart a:hover {
            text-decoration: underline;
        }

        .checkout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-quantity, .item-total {
                margin: 0.5rem 0;
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
                        <li><a href="#">Best Sellers</a></li>
                        <li><a href="#">New Releases</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="cart.php" class="cart-link">Cart<?php if ($item_count > 0): ?><span class="cart-badge"><?php echo $item_count; ?></span><?php endif; ?></a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="cart-container">
            <div class="cart-header">
                <h1>Your Shopping Cart</h1>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p><a href="index.php">Continue shopping</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="item-details">
                            <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                            <div class="item-price">NPR <?php echo number_format($item['price'], 0); ?> each</div>
                        </div>
                        <div class="item-quantity">
                            Quantity: <?php echo $item['quantity']; ?>
                        </div>
                        <div class="item-total">
                            NPR <?php echo number_format($item['price'] * $item['quantity'], 0); ?>
                        </div>
                        <form method="POST" action="remove_from_cart.php" style="margin-left: 1rem;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                            <button type="submit" class="remove-btn">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <div class="cart-total">
                    <h2>Total: NPR <?php echo number_format($total, 0); ?></h2>
                    <form method="POST" action="checkout.php" style="display: inline;">
                        <button type="submit" class="checkout-btn">Proceed to Checkout (<?php echo $item_count; ?> items - NPR <?php echo number_format($total, 0); ?>)</button>
                    </form>
                    <p><a href="index.php" style="color: #e74c3c; text-decoration: none;">Continue shopping</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
