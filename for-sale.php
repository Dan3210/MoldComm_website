<?php
require_once 'config.php';
require_once 'auth.php';

// Initialize Auth class
$auth = new Auth();

// Get posts from database
try {
    $stmt = $auth->getConnection()->prepare(
        "SELECT p.*, u.username 
         FROM posts p 
         JOIN users u ON p.user_id = u.id 
         WHERE p.category = 'for-sale' 
         ORDER BY p.created_at DESC"
    );
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'An error occurred while fetching posts.';
    $posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For Sale - Moldovan Community USA</title>
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #003366;
            --accent-color: #ffcc00;
            --light-gray: #f4f4f4;
            --dark-gray: #333333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            line-height: 1.6;
            color: var(--dark-gray);
            background-color: var(--light-gray);
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: center;
            border-bottom: 5px solid var(--accent-color);
        }
        
        nav {
            background-color: var(--secondary-color);
            padding: 0.5rem;
        }
        
        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            flex-wrap: wrap;
        }
        
        nav li {
            margin: 0.5rem 1rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 0.5rem;
        }
        
        nav a:hover {
            color: var(--accent-color);
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .posts-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .post-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.2s;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
        }
        
        .post-title {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .post-price {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .post-description {
            color: var(--dark-gray);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .post-meta {
            font-size: 0.9rem;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-post {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: var(--accent-color);
            color: var(--dark-gray);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .btn-post:hover {
            background-color: #e6b800;
        }
        
        .no-posts {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 5px;
            grid-column: 1 / -1;
        }
        
        footer {
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .posts-container {
                grid-template-columns: 1fr;
            }
            
            .btn-post {
                bottom: 1rem;
                right: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Items For Sale</h1>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="for-sale.php">For Sale</a></li>
            <li><a href="housing.php">Housing</a></li>
            <li><a href="jobs.php">Jobs</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
    
    <main>
        <div class="posts-container">
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <h2>No items for sale at the moment</h2>
                    <p>Be the first to post an item for sale!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                        <?php if (!empty($post['price'])): ?>
                            <div class="post-price"><?php echo htmlspecialchars($post['price']); ?></div>
                        <?php endif; ?>
                        <p class="post-description"><?php echo htmlspecialchars($post['description']); ?></p>
                        <div class="post-meta">
                            <span>Posted by: <?php echo htmlspecialchars($post['username']); ?></span>
                            <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="create-post.php" class="btn btn-post">Create Listing</a>
    </main>
    
    <footer>
        <p>&copy; 2024 Moldovan Community USA. All rights reserved.</p>
    </footer>
</body>
</html> 