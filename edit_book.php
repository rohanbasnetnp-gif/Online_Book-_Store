<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre = $_POST['genre'];
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, genre = ?, price = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssdsd", $title, $author, $genre, $price, $description, $book_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
    exit();
}

// Get book details
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Book Haven</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            width: 100%;
            background-color: #e74c3c;
            padding: 0.8rem;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 1rem;
        }

        .btn:hover {
            background-color: #c0392b;
        }

        .btn-secondary {
            background-color: #95a5a6;
            margin-top: 0.5rem;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Book</h1>

        <form method="POST">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="author">Author</label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            </div>

            <div class="form-group">
                <label for="genre">Genre</label>
                <select id="genre" name="genre" required>
                    <option value="romance" <?php echo $book['genre'] == 'romance' ? 'selected' : ''; ?>>Romance</option>
                    <option value="action" <?php echo $book['genre'] == 'action' ? 'selected' : ''; ?>>Action & Adventure</option>
                    <option value="horror" <?php echo $book['genre'] == 'horror' ? 'selected' : ''; ?>>Horror</option>
                    <option value="mystery" <?php echo $book['genre'] == 'mystery' ? 'selected' : ''; ?>>Mystery</option>
                    <option value="fantasy" <?php echo $book['genre'] == 'fantasy' ? 'selected' : ''; ?>>Fantasy</option>
                    <option value="scifi" <?php echo $book['genre'] == 'scifi' ? 'selected' : ''; ?>>Science Fiction</option>
                    <option value="biography" <?php echo $book['genre'] == 'biography' ? 'selected' : ''; ?>>Biography</option>
                    <option value="history" <?php echo $book['genre'] == 'history' ? 'selected' : ''; ?>>History</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price (NPR)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo $book['price']; ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($book['description']); ?></textarea>
            </div>

            <button type="submit" class="btn">Update Book</button>
            <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>