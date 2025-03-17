<?php
session_start();
require_once 'auth_functions.php';

class Auth {
    private $conn;
    private $secret_key = 'your-secret-key-here';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function register($username, $email, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = generateToken();
        
        $query = "INSERT INTO users (username, email, password, verification_token, is_verified) VALUES (:username, :email, :password, :token, 0)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":token", $verification_token);
        
        if($stmt->execute()) {
            sendVerificationEmail($email, $verification_token);
            return true;
        }
        return false;
    }
    
    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            if(!$user['is_verified']) {
                throw new Exception("Please verify your email address first.");
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['jwt'] = generateJWT($user['id'], $this->secret_key);
            generateCSRFToken();
            return true;
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
    
    public function logout() {
        session_destroy();
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['is_admin']);
    }
    
    public function getCurrentUser() {
        if(!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT id, username, email, is_admin, is_verified FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function verifyEmail($token) {
        $query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }
    
    public function initiatePasswordReset($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($user = $stmt->fetch()) {
            $reset_token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $query = "UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $reset_token);
            $stmt->bindParam(":expires", $expires);
            $stmt->bindParam(":id", $user['id']);
            
            if($stmt->execute()) {
                return sendPasswordResetEmail($email, $reset_token);
            }
        }
        return false;
    }
    
    public function resetPassword($token, $new_password) {
        $query = "SELECT id FROM users WHERE reset_token = :token AND reset_expires > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if($user = $stmt->fetch()) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":id", $user['id']);
            
            return $stmt->execute();
        }
        return false;
    }
    
    public function validateJWT($token) {
        return verifyJWT($token, $this->secret_key);
    }
}