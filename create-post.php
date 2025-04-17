<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Initialize Auth class
$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? '';
    
    if (empty($title) || empty($description) || empty($category)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $stmt = $auth->getConnection()->prepare(
                "INSERT INTO posts (user_id, title, description, category, price, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            $userId = $_SESSION['user_id'];
            $stmt->execute([$userId, $title, $description, $category, $price]);
            
            $success = 'Post created successfully!';
            // Redirect to the appropriate category page
            header("Location: {$category}.php");
            exit;
        } catch (PDOException $e) {
            $error = 'An error occurred while creating your post. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Moldovan Community USA</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .post-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .post-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
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
        
        .btn-submit {
            background-color: var(--accent-color);
            color: var(--dark-gray);
            width: 100%;
        }
        
        .btn-submit:hover {
            background-color: #e6b800;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 0.5rem;
            border-radius: 3px;
            margin-bottom: 1rem;
        }
        
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 0.5rem;
            border-radius: 3px;
            margin-bottom: 1rem;
        }
        
        footer {
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Create a New Post</h1>
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
        <div class="post-container">
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="create-post.php">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="for-sale">For Sale</option>
                        <option value="housing">Housing</option>
                        <option value="jobs">Jobs</option>
                        <option value="services">Services</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (optional)</label>
                    <input type="text" id="price" name="price" placeholder="Enter price or 'Negotiable'">
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-submit">Create Post</button>
            </form>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2024 Moldovan Community USA. All rights reserved.</p>
    </footer>
</body>
</html> 