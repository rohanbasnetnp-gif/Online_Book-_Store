<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the most recent order for this user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: index.php");
    exit();
}

$transaction_id = isset($_SESSION['transaction_id']) ? $_SESSION['transaction_id'] : '';
$payment_method = isset($_SESSION['payment_method']) ? $_SESSION['payment_method'] : '';

// Clear session payment data
unset($_SESSION['transaction_id']);
unset($_SESSION['payment_method']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Book Haven</title>
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

        .success-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .order-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }

        .order-details h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ddd;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-row .label {
            color: #666;
        }

        .detail-row .value {
            font-weight: bold;
            color: #2c3e50;
        }

        .payment-info {
            background-color: #e8f5e9;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: left;
        }

        .payment-info.paid {
            background-color: #e8f5e9;
            border-color: #a5d6a7;
        }

        .payment-info.pending {
            background-color: #fff3e0;
            border-color: #ffcc80;
        }

        .payment-info h4 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .payment-info.paid h4 {
            color: #27ae60;
        }

        .payment-info.pending h4 {
            color: #f39c12;
        }

        .transaction-id {
            font-family: monospace;
            background-color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            margin: 0.5rem;
        }

        .btn:hover {
            background-color: #c0392b;
        }

        .btn-secondary {
            background-color: #3498db;
        }

        .btn-secondary:hover {
            background-color: #2980b9;
        }

        .button-group {
            margin-top: 1.5rem;
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
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            
            <?php if ($order['payment_status'] == 'completed'): ?>
                <p>Thank you for your purchase. Your payment has been processed successfully.</p>
                
                <div class="payment-info paid">
                    <h4>✓ Payment Completed</h4>
                    <div class="detail-row">
                        <span class="label">Transaction ID:</span>
                        <span class="value transaction-id"><?php echo htmlspecialchars($transaction_id); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Payment Method:</span>
                        <span class="value"><?php echo htmlspecialchars(ucfirst($payment_method)); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p>Thank you for your order. You will pay when the order is delivered to your address.</p>
                
                <div class="payment-info pending">
                    <h4>⏳ Cash on Delivery</h4>
                    <p>Please keep NPR <?php echo number_format($order['total'], 0); ?> ready for the delivery person.</p>
                </div>
            <?php endif; ?>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span class="label">Order ID:</span>
                    <span class="value">#<?php echo $order['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value">NPR <?php echo number_format($order['total'], 0); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Shipping Address:</span>
                    <span class="value"><?php echo htmlspecialchars($order['address']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
            </div>

            <div class="button-group">
                <a href="index.php" class="btn">Continue Shopping</a>
                <a href="view_order.php" class="btn btn-secondary">View Orders</a>
            </div>
        </div>
    </div>
</body>
</html>
