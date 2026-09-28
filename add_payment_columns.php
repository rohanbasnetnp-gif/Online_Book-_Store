<?php
include 'config.php';

echo "<h2>Adding Payment Columns to Orders Table</h2>";

// Function to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($result && $result->num_rows > 0) {
        echo "<p>Column '$column' already exists. Skipping.</p>";
        return true;
    }
    
    // Add column
    $sql = "ALTER TABLE $table ADD COLUMN $column $definition";
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>Successfully added column: $column</p>";
        return true;
    } else {
        echo "<p style='color:red;'>Error adding column $column: " . $conn->error . "</p>";
        return false;
    }
}

// Add payment columns
$columns = [
    'payment_method' => 'VARCHAR(50) DEFAULT NULL',
    'payment_status' => "VARCHAR(50) DEFAULT 'pending'",
    'transaction_id' => 'VARCHAR(100) DEFAULT NULL',
    'payment_date' => 'TIMESTAMP NULL DEFAULT NULL'
];

$allSuccess = true;
foreach ($columns as $column => $definition) {
    if (!addColumnIfNotExists($conn, 'orders', $column, $definition)) {
        $allSuccess = false;
    }
}

if ($allSuccess) {
    echo "<h3 style='color:green;'>All payment columns added successfully!</h3>";
} else {
    echo "<h3 style='color:orange;'>Some columns may already exist. Check the messages above.</h3>";
}

echo "<p><a href='index.php'>Go to Home</a></p>";
?>
