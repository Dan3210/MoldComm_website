<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Get username from email (since we store username in the database)
        $stmt = $auth->getConnection()->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $result = $auth->login($user['username'], $password);
            if ($result['success']) {
                header('Location: index.php');
                exit;
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please enter both email and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Moldovan Community USA</title>
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
        }
        
        nav li {
            margin: 0 1rem;
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
            max-width: 500px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .login-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .login-title {
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
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 1rem;
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
        
        .btn-login {
            background-color: var(--accent-color);
            color: var(--dark-gray);
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: #e6b800;
        }
        
        .error {
            color: red;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #ffebee;
            border-radius: 3px;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
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
        <h1>Moldovan Community USA</h1>
        <p>Connect, Share, Support</p>
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
        <div class="login-container">
            <h2 class="login-title">Log In to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-login">Log In</button>
            </form>
            
            <div class="signup-link">
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2025 Moldovan Community USA | Privacy Policy | Terms of Service</p>
    </footer>
</body>
</html> 