<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create orders table if not exists
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
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($orders_sql);

// Create order_items table if not exists
$order_items_sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
)";
$conn->query($order_items_sql);

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
    $book_id = (int)$_POST['book_id'];
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php");
    exit();
}

// Handle delete all books
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all_books'])) {
    $conn->query("DELETE FROM books");
    header("Location: admin.php");
    exit();
}

// Get all books
$query = "SELECT * FROM books ORDER BY id DESC";
$result = $conn->query($query);
$books = $result->fetch_all(MYSQLI_ASSOC);

// Get all users
$user_query = "SELECT id, name, email, role FROM users ORDER BY id DESC";
$user_result = $conn->query($user_query);
$users = $user_result->fetch_all(MYSQLI_ASSOC);

// Get all orders
$order_query = "SELECT o.id, o.name, o.email, o.phone, o.address, o.total, o.order_date, o.status, IFNULL(o.payment_status, 'pending') as payment_status, IFNULL(o.payment_method, 'cod') as payment_method, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC";
$order_result = $conn->query($order_query);
$orders = $order_result ? $order_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Book Haven</title>
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

        .admin-container {
            margin: 2rem auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .admin-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            color: #2c3e50;
        }

        .admin-nav {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .admin-nav a {
            padding: 1rem;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .admin-nav a.active {
            color: #e74c3c;
            border-bottom-color: #e74c3c;
        }

        .admin-nav a:hover {
            color: #e74c3c;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .book-actions {
            display: flex;
            gap: 0.5rem;
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
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
            }

            .book-actions {
                flex-direction: column;
            }

            table {
                font-size: 0.9rem;
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Panel</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>

            <div class="admin-nav">
                <a href="#books" class="tab-link active" data-tab="books">Manage Books</a>
                <a href="#users" class="tab-link" data-tab="users">Manage Users</a>
                <a href="#orders" class="tab-link" data-tab="orders">Manage Orders</a>
                <a href="#add-book" class="tab-link" data-tab="add-book">Add New Book</a>
            </div>

            <!-- Books Management Section -->
            <div id="books" class="section active">
                <h2>Books Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                            <th>Price</th>
                            <th>Cover</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo $book['id']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['genre']); ?></td>
                            <td>NPR <?php echo number_format($book['price'], 0); ?></td>
                            <td><?php if (!empty($book['cover_image'])): ?><img src="<?php echo htmlspecialchars($book['cover_image']); ?>" width="50" height="70" alt="Cover"><?php endif; ?></td>
                            <td class="book-actions">
                                <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-secondary">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="delete_book" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this book?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="POST" style="margin-top: 1rem;">
                    <button type="submit" name="delete_all_books" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all books?')">Delete All Books</button>
                </form>
            </div>

            <!-- Users Management Section -->
            <div id="users" class="section">
                <h2>Users Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role'] ?? 'user'); ?></td>
                            <td class="book-actions">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Orders Management Section -->
            <div id="orders" class="section">
                <h2>Orders Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td>NPR <?php echo number_format($order['total'], 0); ?></td>
                            <td><?php echo $order['order_date']; ?></td>
                            <td>
                                <?php if (($order['payment_status'] ?? 'pending') == 'completed'): ?>
                                    <span style="color: green;">✓ Paid (<?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'Online')); ?>)</span>
                                <?php else: ?>
                                    <span style="color: orange;">⏳ COD</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td class="book-actions">
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Book Section -->
            <div id="add-book" class="section">
                <h2>Add New Book</h2>
                <form method="POST" action="add_book.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" required>
                    </div>
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <select id="genre" name="genre" required>
                            <option value="">Select Genre</option>
                            <option value="romance">Romance</option>
                            <option value="action">Action & Adventure</option>
                            <option value="horror">Horror</option>
                            <option value="mystery">Mystery</option>
                            <option value="fantasy">Fantasy</option>
                            <option value="scifi">Science Fiction</option>
                            <option value="biography">Biography</option>
                            <option value="history">History</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (NPR)</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="cover">Cover Photo</label>
                        <input type="file" id="cover" name="cover" accept="image/*">
                    </div>
                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');

                // Hide all sections
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });

                // Remove active class from all tabs
                document.querySelectorAll('.tab-link').forEach(tab => {
                    tab.classList.remove('active');
                });

                // Show selected section and activate tab
                document.getElementById(tabId).classList.add('active');
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>