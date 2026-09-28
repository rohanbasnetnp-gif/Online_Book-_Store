<?php
include 'config.php';

// Create orders table
$orders_sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_date TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($orders_sql) === TRUE) {
    echo "Orders table created successfully<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Add payment columns if they don't exist (for existing databases)
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result && $result->num_rows > 0;
}

if (!columnExists($conn, 'orders', 'payment_method')) {
    $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL");
}
if (!columnExists($conn, 'orders', 'payment_status')) {
    $conn->query("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending'");
}
if (!columnExists($conn, 'orders', 'transaction_id')) {
    $conn->query("ALTER TABLE orders ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL");
}
if (!columnExists($conn, 'orders', 'payment_date')) {
    $conn->query("ALTER TABLE orders ADD COLUMN payment_date TIMESTAMP NULL DEFAULT NULL");
}
echo "Payment columns added successfully<br>";

// Create order_items table
$order_items_sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
)";

if ($conn->query($order_items_sql) === TRUE) {
    echo "Order items table created successfully<br>";
} else {
    echo "Error creating order items table: " . $conn->error . "<br>";
}

echo "Setup complete!";
?>