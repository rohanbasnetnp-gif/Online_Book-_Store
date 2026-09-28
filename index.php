<?php
session_start();
include 'config.php';

$is_admin = isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? 'user') === 'admin';

// Get cart item count for logged-in users
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_count = "SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query_count);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_count = $stmt->get_result();
    $cart_data = $result_count->fetch_assoc();
    $cart_count = $cart_data['total_items'] ?? 0;
    $stmt->close();
}

// Get filter parameters
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';

// Build query
$query = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = '';

if (!empty($genre_filter)) {
    $query .= " AND genre = ?";
    $params[] = $genre_filter;
    $types .= 's';
}

if (!empty($price_filter)) {
    switch ($price_filter) {
        case '0-500':
            $query .= " AND price < 500";
            break;
        case '500-1000':
            $query .= " AND price >= 500 AND price <= 1000";
            break;
        case '1000-1500':
            $query .= " AND price > 1000 AND price <= 1500";
            break;
        case '1500+':
            $query .= " AND price > 1500";
            break;
    }
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY id DESC";
        break;
    default:
        $query .= " ORDER BY title ASC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Haven - Online Book Store</title>
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

        .cart-link {
            position: relative;
            display: inline-block;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 1.5rem;
        }

        .btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 0.7rem 1.5rem;
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

        .main-content {
            display: flex;
            margin: 2rem 0;
        }

        .filters {
            width: 25%;
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-right: 2rem;
        }

        .filters h2 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-group h3 {
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
        }

        .filter-options label {
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .filter-options input {
            margin-right: 0.5rem;
        }

        .books {
            width: 75%;
        }

        .books-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .book-image {
            height: 250px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 0.9rem;
        }

        .book-info {
            padding: 1rem;
        }

        .book-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .book-author {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .book-price {
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .book-actions {
            display: flex;
            justify-content: space-between;
        }

        footer {
            background-color: #2c3e50;
            color: white;
            padding: 2rem 0;
            margin-top: 2rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            color: white;
        }

        .footer-section {
            width: 30%;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #e74c3c;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .filters, .books {
                width: 100%;
            }

            .filters {
                margin-right: 0;
                margin-bottom: 2rem;
            }

            .footer-content {
                flex-direction: column;
            }

            .footer-section {
                width: 100%;
                margin-bottom: 1.5rem;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal-book-image {
            text-align: center;
            margin-bottom: 20px;
        }

        .modal-book-image img {
            max-width: 200px;
            height: auto;
            border-radius: 4px;
        }

        #modalTitle {
            margin-bottom: 10px;
        }

        #modalAuthor, #modalGenre, #modalPrice {
            margin-bottom: 5px;
            color: #666;
            font-weight: bold;
        }

        #modalDescription {
            line-height: 1.6;
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
                        <?php if (!$is_admin): ?>
                        <li><a href="index.php">Books</a></li>
                        <li><a href="#">Best Sellers</a></li>
                        <li><a href="#">New Releases</a></li>
                        <li><a href="#">Contact</a></li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($is_admin): ?>
                                <li><a href="admin.php">Admin Panel</a></li>
                            <?php endif; ?>
                            <?php if (!$is_admin): ?>
                            <li><a href="cart.php" class="cart-link">Cart<?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?></a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Discover Your Next Favorite Book</h1>
            <p>Explore our vast collection of books across all genres. Find your next adventure, romance, or mystery today!</p>
            <a href="#books" class="btn">Browse Books</a>
        </div>
    </section>

    <div class="container">
        <div class="main-content">
            <aside class="filters">
                <h2>Filter Books</h2>

                <form method="GET" action="index.php">
                    <div class="filter-group">
                        <h3>Genre</h3>
                        <div class="filter-options">
                            <label><input type="radio" name="genre" value="" <?php echo empty($genre_filter) ? 'checked' : ''; ?>> All Genres</label>
                            <label><input type="radio" name="genre" value="romance" <?php echo $genre_filter == 'romance' ? 'checked' : ''; ?>> Romance</label>
                            <label><input type="radio" name="genre" value="action" <?php echo $genre_filter == 'action' ? 'checked' : ''; ?>> Action & Adventure</label>
                            <label><input type="radio" name="genre" value="horror" <?php echo $genre_filter == 'horror' ? 'checked' : ''; ?>> Horror</label>
                            <label><input type="radio" name="genre" value="mystery" <?php echo $genre_filter == 'mystery' ? 'checked' : ''; ?>> Mystery</label>
                            <label><input type="radio" name="genre" value="fantasy" <?php echo $genre_filter == 'fantasy' ? 'checked' : ''; ?>> Fantasy</label>
                            <label><input type="radio" name="genre" value="scifi" <?php echo $genre_filter == 'scifi' ? 'checked' : ''; ?>> Science Fiction</label>
                            <label><input type="radio" name="genre" value="biography" <?php echo $genre_filter == 'biography' ? 'checked' : ''; ?>> Biography</label>
                            <label><input type="radio" name="genre" value="history" <?php echo $genre_filter == 'history' ? 'checked' : ''; ?>> History</label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>Price Range</h3>
                        <div class="filter-options">
                            <label><input type="radio" name="price" value="" <?php echo empty($price_filter) ? 'checked' : ''; ?>> All Prices</label>
                            <label><input type="radio" name="price" value="0-500" <?php echo $price_filter == '0-500' ? 'checked' : ''; ?>> Under NPR 500</label>
                            <label><input type="radio" name="price" value="500-1000" <?php echo $price_filter == '500-1000' ? 'checked' : ''; ?>> NPR 500 - 1000</label>
                            <label><input type="radio" name="price" value="1000-1500" <?php echo $price_filter == '1000-1500' ? 'checked' : ''; ?>> NPR 1000 - 1500</label>
                            <label><input type="radio" name="price" value="1500+" <?php echo $price_filter == '1500+' ? 'checked' : ''; ?>> Above NPR 1500</label>
                        </div>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Apply Filters</button>
                </form>
            </aside>

            <section class="books" id="books">
                <div class="books-header">
                    <h2>All Books</h2>
                    <div>
                        <form method="GET" action="index.php" style="display: inline;">
                            <input type="hidden" name="genre" value="<?php echo $genre_filter; ?>">
                            <input type="hidden" name="price" value="<?php echo $price_filter; ?>">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Sort by: Title</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Sort by: Price Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Sort by: Price High to Low</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Sort by: Newest</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card" data-genre="<?php echo $book['genre']; ?>" data-price="<?php echo $book['price']; ?>" data-title="<?php echo htmlspecialchars($book['title']); ?>" data-author="<?php echo htmlspecialchars($book['author']); ?>" data-description="<?php echo htmlspecialchars($book['description']); ?>" data-cover="<?php echo htmlspecialchars($book['cover_image']); ?>" onclick="showBookDetails(this)">
                            <div class="book-image">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    Book Cover Image
                                <?php endif; ?>
                            </div>
                            <div class="book-info">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                                <div class="book-price">NPR <?php echo number_format($book['price'], 0); ?></div>
                                <div class="book-actions">
                                    <?php if (!$is_admin): ?>
                                    <form method="POST" action="add_to_cart.php" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn">Add to Cart</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Book Haven</h3>
                    <p>Book Haven is your premier online destination for books of all genres. We're passionate about connecting readers with their next favorite book.</p>
                </div>

                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php">All Books</a></li>
                        <li><a href="#">Best Sellers</a></li>
                        <li><a href="#">New Releases</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>Email: info@bookhaven.com</p>
                    <p>Phone: 9803789471</p>
                    <p>Address: Kathmandu, Nepal</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2023 Book Haven. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Book Details Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-book-image">
                <img id="modalCover" src="" alt="Book Cover">
            </div>
            <h2 id="modalTitle"></h2>
            <p id="modalAuthor"></p>
            <p id="modalGenre"></p>
            <p id="modalPrice"></p>
            <p id="modalDescription"></p>
        </div>
    </div>

    <script>
        function showBookDetails(element) {
            const modal = document.getElementById('bookModal');
            const title = document.getElementById('modalTitle');
            const author = document.getElementById('modalAuthor');
            const genre = document.getElementById('modalGenre');
            const price = document.getElementById('modalPrice');
            const description = document.getElementById('modalDescription');
            const cover = document.getElementById('modalCover');

            title.textContent = element.dataset.title;
            author.textContent = 'by ' + element.dataset.author;
            genre.textContent = 'Genre: ' + element.dataset.genre;
            price.textContent = 'Price: NPR ' + element.dataset.price;
            description.textContent = element.dataset.description;
            cover.src = element.dataset.cover || '';

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('bookModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>