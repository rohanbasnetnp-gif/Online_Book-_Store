<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookstore');
define('DB_PORT', '3306');

// Payment Gateway Configuration (Simulated eSewa-like)
define('PAYMENT_ENABLED', true);
define('PAYMENT_MODE', 'sandbox'); // sandbox or live
define('MERCHANT_ID', 'TEST_MERCHANT_123');
define('PAYMENT_CALLBACK_URL', 'http://localhost/store/payment_verify.php');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>