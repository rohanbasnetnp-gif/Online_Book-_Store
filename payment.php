<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['pending_order_id'];
$total = isset($_SESSION['pending_total']) ? $_SESSION['pending_total'] : 0;

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = false;

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    if ($payment_method == 'esewa') {
        $esewa_id = isset($_POST['esewa_id']) ? trim($_POST['esewa_id']) : '';
        $esewa_pin = isset($_POST['esewa_pin']) ? trim($_POST['esewa_pin']) : '';
        
        // Simulated validation - in real eSewa, this would call their API
        if (empty($esewa_id) || empty($esewa_pin)) {
            $error = "Please enter your eSewa ID and PIN.";
        } elseif (strlen($esewa_id) < 5) {
            $error = "Invalid eSewa ID format.";
        } elseif (strlen($esewa_pin) != 4 || !is_numeric($esewa_pin)) {
            $error = "Invalid PIN. Must be 4 digits.";
        } else {
            // Simulate successful payment
            $payment_status = 'completed';
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            $payment_date = date('Y-m-d H:i:s');
            
            // Update order with payment details
            $stmt = $conn->prepare("UPDATE orders SET payment_method = ?, payment_status = ?, transaction_id = ?, payment_date = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $payment_method, $payment_status, $transaction_id, $payment_date, $order_id);
            $stmt->execute();
            $stmt->close();
            
            // Clear cart after successful payment
            $user_id = $_SESSION['user_id'];
            $delete_query = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Store transaction info for success page
            $_SESSION['transaction_id'] = $transaction_id;
            $_SESSION['payment_method'] = $payment_method;
            
            unset($_SESSION['pending_order_id']);
            unset($_SESSION['pending_total']);
            
            header("Location: checkout_success.php");
            exit();
        }
    } elseif ($payment_method == 'cod') {
        // Cash on Delivery
        $payment_status = 'pending';
        
        $stmt = $conn->prepare("UPDATE orders SET payment_method = ?, payment_status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $payment_method, $payment_status, $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Clear cart
        $user_id = $_SESSION['user_id'];
        $delete_query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['payment_method'] = $payment_method;
        unset($_SESSION['pending_order_id']);
        unset($_SESSION['pending_total']);
        
        header("Location: checkout_success.php");
        exit();
    } else {
        $error = "Please select a payment method.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Book Haven</title>
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

        .payment-container {
            max-width: 500px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .payment-header h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .payment-header .amount {
            font-size: 2rem;
            font-weight: bold;
            color: #27ae60;
        }

        .payment-methods {
            margin-bottom: 1.5rem;
        }

        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #3498db;
        }

        .payment-method.selected {
            border-color: #27ae60;
            background-color: #f0f9f0;
        }

        .payment-method input[type="radio"] {
            display: none;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
        }

        .payment-method .radio-icon {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-method.selected .radio-icon {
            border-color: #27ae60;
        }

        .payment-method.selected .radio-icon::after {
            content: '';
            width: 10px;
            height: 10px;
            background-color: #27ae60;
            border-radius: 50%;
        }

        .payment-method .method-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .payment-method .method-info h3 {
            font-size: 1rem;
            color: #2c3e50;
        }

        .payment-method .method-info p {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .esewa-details {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .esewa-details.show {
            display: block;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-size: 1.1rem;
        }

        .btn:hover {
            background-color: #219a52;
        }

        .btn-cod {
            background-color: #3498db;
        }

        .btn-cod:hover {
            background-color: #2980b9;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background-color: #fadbd8;
            border-radius: 4px;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .secure-badge svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }

        .order-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .order-info p {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .order-info p:last-child {
            margin-bottom: 0;
        }

        .order-info .label {
            color: #7f8c8d;
        }

        .order-info .value {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Book Haven</div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2>Complete Your Payment</h2>
                <div class="amount">NPR <?php echo number_format($total, 0); ?></div>
            </div>

            <div class="order-info">
                <p>
                    <span class="label">Order ID:</span>
                    <span class="value">#<?php echo $order_id; ?></span>
                </p>
                <p>
                    <span class="label">Customer:</span>
                    <span class="value"><?php echo htmlspecialchars($order['name']); ?></span>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="payment.php" id="paymentForm">
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectMethod('esewa')">
                        <input type="radio" name="payment_method" value="esewa" id="esewa" onchange="toggleDetails()">
                        <label for="esewa">
                            <span class="radio-icon"></span>
                            <span class="method-icon">💳</span>
                            <div class="method-info">
                                <h3>eSewa / Mobile Banking</h3>
                                <p>Pay using eSewa, IME Pay, Connect IPS, etc.</p>
                            </div>
                        </label>
                        <div class="esewa-details" id="esewaDetails">
                            <div class="form-group">
                                <label for="esewa_id">eSewa ID / Mobile Number</label>
                                <input type="text" id="esewa_id" name="esewa_id" placeholder="e.g., 98XXXXXXXX" value="<?php echo isset($_POST['esewa_id']) ? htmlspecialchars($_POST['esewa_id']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="esewa_pin">mPIN / Password</label>
                                <input type="password" id="esewa_pin" name="esewa_pin" placeholder="Enter 4-digit mPIN" maxlength="4">
                            </div>
                            <small style="color: #7f8c8d;">For testing, use any ID with 5+ characters and 4-digit PIN</small>
                        </div>
                    </div>

                    <div class="payment-method" onclick="selectMethod('cod')">
                        <input type="radio" name="payment_method" value="cod" id="cod" onchange="toggleDetails()">
                        <label for="cod">
                            <span class="radio-icon"></span>
                            <span class="method-icon">💵</span>
                            <div class="method-info">
                                <h3>Cash on Delivery</h3>
                                <p>Pay when you receive your order</p>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn" id="payBtn">Pay NPR <?php echo number_format($total, 0); ?></button>

                <div class="secure-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                    Secure Payment - 256-bit SSL Encrypted
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(method).checked = true;
            
            toggleDetails();
        }

        function toggleDetails() {
            const esewaSelected = document.getElementById('esewa').checked;
            const esewaDetails = document.getElementById('esewaDetails');
            const payBtn = document.getElementById('payBtn');
            
            if (esewaSelected) {
                esewaDetails.classList.add('show');
                payBtn.textContent = 'Pay NPR <?php echo number_format($total, 0); ?>';
                payBtn.className = 'btn';
            } else {
                esewaDetails.classList.remove('show');
                payBtn.textContent = 'Confirm Order';
                payBtn.className = 'btn btn-cod';
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial state
            const esewaMethod = document.querySelector('.payment-method:first-child');
            if (esewaMethod) {
                esewaMethod.classList.add('selected');
                document.getElementById('esewa').checked = true;
                toggleDetails();
            }
        });
    </script>
</body>
</html>
