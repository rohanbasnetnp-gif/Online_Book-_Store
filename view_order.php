<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details
$order_query = "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: admin.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: view_order.php?id=" . $order_id);
    exit();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_order'])) {
    // Delete order items first
    $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $delete_items_stmt->bind_param("i", $order_id);
    $delete_items_stmt->execute();
    $delete_items_stmt->close();

    // Delete order
    $delete_order_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $delete_order_stmt->bind_param("i", $order_id);
    $delete_order_stmt->execute();
    $delete_order_stmt->close();

    header("Location: admin.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, b.title, b.author FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Book Haven</title>
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

        .order-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 2rem auto;
            padding: 2rem;
        }

        .order-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .order-header h1 {
            color: #2c3e50;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-group {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
        }

        .info-group h3 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .order-items {
            margin-bottom: 2rem;
        }

        .order-items h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
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
        }

        .item-price {
            font-weight: bold;
            color: #e74c3c;
        }

        .order-total {
            text-align: right;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #2c3e50;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
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

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }

            .order-item {
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
                        <li><a href="admin.php">Back to Admin</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="order-container">
            <div class="order-header">
                <h1>Order #<?php echo $order['id']; ?></h1>
                <p>Ordered on <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?> | Status: <?php echo htmlspecialchars($order['status']); ?></p>
            </div>

            <div class="order-info">
                <div class="info-group">
                    <h3>Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Shipping Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                </div>
                <div class="info-group">
                    <h3>Order Summary</h3>
                    <p><strong>Total:</strong> NPR <?php echo number_format($order['total'], 0); ?></p>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                </div>
                <div class="info-group">
                    <h3>Payment Information</h3>
                    <p><strong>Payment Status:</strong> 
                        <?php if (($order['payment_status'] ?? 'pending') == 'completed'): ?>
                            <span style="color: green;">✓ Paid</span>
                        <?php else: ?>
                            <span style="color: orange;">⏳ Pending</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?></p>
                    <?php if (!empty($order['transaction_id'])): ?>
                        <p><strong>Transaction ID:</strong> <code><?php echo htmlspecialchars($order['transaction_id']); ?></code></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-items">
                <h2>Order Items</h2>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?> - Quantity: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-price">NPR <?php echo number_format($item['price'] * $item['quantity'], 0); ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="order-total">
                    Total: NPR <?php echo number_format($order['total'], 0); ?>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <?php if ($order['status'] !== 'completed'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" name="update_status" class="btn">Mark as Completed</button>
                    </form>
                <?php endif; ?>
                <form method="POST" style="display: inline; margin-left: 1rem;" onsubmit="return confirm('Are you sure you want to delete this order?')">
                    <button type="submit" name="delete_order" class="btn btn-danger">Delete Order</button>
                </form>
                <br><br>
                <a href="admin.php" class="btn btn-secondary">Back to Orders</a>
            </div>
        </div>
    </div>
</body>
</html>