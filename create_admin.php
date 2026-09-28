<?php
include 'config.php';

// Create admin user
$name = 'Admin';
$email = 'admin@bookhaven.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'admin';

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    if ($stmt->execute()) {
        echo "Admin user created successfully! Email: admin@bookhaven.com, Password: admin123";
    } else {
        echo "Failed to create admin user.";
    }
} else {
    echo "Admin user already exists.";
}

$stmt->close();
$conn->close();
?>