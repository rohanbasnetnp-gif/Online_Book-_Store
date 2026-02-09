    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Successful - Book Haven</title>
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
                text-align: center;
                padding: 2rem;
            }

            .success-container {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                padding: 3rem;
                max-width: 600px;
                margin: 2rem auto;
            }

            .success-icon {
                font-size: 4rem;
                color: #27ae60;
                margin-bottom: 1rem;
            }

            h1 {
                color: #2c3e50;
                margin-bottom: 1rem;
            }

            p {
                margin-bottom: 2rem;
                color: #666;
            }

            .btn {
                background-color: #e74c3c;
                color: white;
                border: none;
                padding: 0.7rem 1.5rem;
                border-radius: 4px;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                transition: background-color 0.3s;
            }

            .btn:hover {
                background-color: #c0392b;
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been placed and you will receive a confirmation email shortly.</p>
            <a href="index.php" class="btn">Continue Shopping</a>
        </div>
    </body>
    </html>