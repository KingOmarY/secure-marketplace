<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Marketplace - UTHM</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php">UTHM Marketplace</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="products.php">Products</a>
            <a href="announcements.php">Announcements</a>
            <?php if ($_SESSION['role'] == 'seller'): ?>
                <a href="post_product.php">Post Product</a>
                <a href="my_products.php">My Products</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'user'): ?>
                <a href="my_likes.php">My Likes</a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="admin_users.php">Manage Users</a>
                <a href="admin_products.php">Manage Products</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
            <span><?php echo htmlspecialchars($_SESSION['user_id']); ?> (<?php echo $_SESSION['role']; ?>)</span>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
    <div class="container">
