<?php
session_start();
include 'config.php';

// Add logging
error_log("add_to_cart.php: Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("add_to_cart.php: REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("add_to_cart.php: POST book_id: " . (isset($_POST['book_id']) ? $_POST['book_id'] : 'not set'));

if (!isset($_SESSION['user_id'])) {
    error_log("add_to_cart.php: User not logged in, redirecting to login.php");
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    error_log("add_to_cart.php: Processing book_id: $book_id, user_id: $user_id");

    // Check if book exists
    $stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
    if (!$stmt) {
        error_log("add_to_cart.php: Prepare failed for books check: " . $conn->error);
    } else {
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        error_log("add_to_cart.php: Books check result rows: " . $result->num_rows);

        if ($result->num_rows > 0) {
            // Check if item already in cart
            $stmt2 = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
            if (!$stmt2) {
                error_log("add_to_cart.php: Prepare failed for cart check: " . $conn->error);
            } else {
                $stmt2->bind_param("ii", $user_id, $book_id);
                $stmt2->execute();
                $cart_result = $stmt2->get_result();
                error_log("add_to_cart.php: Cart check result rows: " . $cart_result->num_rows);

                if ($cart_result->num_rows > 0) {
                    // Update quantity
                    $cart_item = $cart_result->fetch_assoc();
                    $new_quantity = $cart_item['quantity'] + 1;
                    $stmt3 = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    if (!$stmt3) {
                        error_log("add_to_cart.php: Prepare failed for update: " . $conn->error);
                    } else {
                        $stmt3->bind_param("ii", $new_quantity, $cart_item['id']);
                        $stmt3->execute();
                        error_log("add_to_cart.php: Updated cart item quantity to: $new_quantity");
                    }
                } else {
                    // Add new item to cart
                    $stmt4 = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)");
                    if (!$stmt4) {
                        error_log("add_to_cart.php: Prepare failed for insert: " . $conn->error);
                    } else {
                        $stmt4->bind_param("ii", $user_id, $book_id);
                        $stmt4->execute();
                        error_log("add_to_cart.php: Inserted new cart item");
                    }
                }
            }
        } else {
            error_log("add_to_cart.php: Book not found");
        }
    }
} else {
    error_log("add_to_cart.php: Invalid request method or missing book_id");
}

header("Location: index.php");
exit();
?>