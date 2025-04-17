<?php
require_once 'config.php';

class Auth {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function register($username, $email, $password) {
        try {
            // Check if username or email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);

            // Insert new user
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash]);

            return ['success' => true, 'message' => 'Registration successful'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($username, $password) {
        try {
            // Get user by username
            $stmt = $this->conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Generate session token
            $session_token = bin2hex(random_bytes(SESSION_TOKEN_LENGTH));
            $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

            // Store session
            $stmt = $this->conn->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $session_token, $expires_at]);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            // Set session cookie
            setcookie('session_token', $session_token, time() + COOKIE_LIFETIME, '/', '', true, true);

            return ['success' => true, 'message' => 'Login successful'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout() {
        if (isset($_COOKIE['session_token'])) {
            $session_token = $_COOKIE['session_token'];
            
            // Delete session from database
            $stmt = $this->conn->prepare("DELETE FROM sessions WHERE session_token = ?");
            $stmt->execute([$session_token]);
            
            // Clear cookie
            setcookie('session_token', '', time() - 3600, '/', '', true, true);
        }
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (!isset($_COOKIE['session_token'])) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT user_id 
                FROM sessions 
                WHERE session_token = ? 
                AND expires_at > NOW() 
                AND user_id = ?
            ");
            $stmt->execute([$_COOKIE['session_token'], $_SESSION['user_id']]);
            return $stmt->fetch() !== false;
        } catch(PDOException $e) {
            return false;
        }
    }
} 