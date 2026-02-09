<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre = $_POST['genre'];
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);

    // Handle cover image upload
    $cover_image = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['cover'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            header("Location: admin.php?error=invalid_file_type");
            exit();
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = uniqid() . '.' . $ext;
        $target = 'uploads/' . $new_name;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $cover_image = $target;
        } else {
            header("Location: admin.php?error=upload_failed");
            exit();
        }
    }

    // Basic validation
    if (empty($title) || empty($author) || empty($genre) || $price <= 0) {
        header("Location: admin.php?error=invalid_data");
        exit();
    }

    // Get next ID
    $result = $conn->query("SELECT MAX(id) FROM books");
    $max_id = $result->fetch_row()[0] ?? -1;
    $next_id = $max_id + 1;

    $stmt = $conn->prepare("INSERT INTO books (id, title, author, genre, price, description, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdss", $next_id, $title, $author, $genre, $price, $description, $cover_image);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php?success=book_added");
    exit();
} else {
    header("Location: admin.php");
    exit();
}
?>