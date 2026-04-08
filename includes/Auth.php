<?php
/**
 * Authentication Handler
 */

session_start();

class Auth {
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current logged-in user
     */
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            global $mysqli;
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->num_rows > 0 ? $result->fetch_assoc() : null;
                $stmt->close();
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        if (self::isLoggedIn()) {
            return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
        }
        return false;
    }
    
    /**
     * Login user
     */
    public static function login($username, $password) {
        global $mysqli;
        
        // Direct query since fetchRow seems to have issues
        $stmt = $mysqli->prepare("SELECT id, password, status, is_admin FROM users WHERE username = ? OR email = ?");
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $mysqli->error];
        }
        
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Username or email not found'];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user['status'] === STATUS_SUSPENDED) {
            return ['success' => false, 'message' => 'Your account has been suspended'];
        }
        
        if ($user['status'] === STATUS_REJECTED) {
            return ['success' => false, 'message' => 'Your account registration was rejected'];
        }
        
        if ($user['status'] === STATUS_PENDING) {
            return ['success' => false, 'message' => 'Your account is still pending admin approval'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['login_time'] = time();
        
        // Log session
        $stmt = $mysqli->prepare("INSERT INTO session_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            $stmt->execute();
            $stmt->close();
        }
        
        return ['success' => true, 'message' => 'Login successful'];
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (self::isLoggedIn()) {
            // Update session log
            executeQuery(
                "UPDATE session_logs SET logout_time = NOW() WHERE user_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1",
                "i",
                [$_SESSION['user_id']]
            );
        }
        session_destroy();
        return true;
    }
    
    /**
     * Check session timeout
     */
    public static function checkSessionTimeout() {
        if (self::isLoggedIn()) {
            if (time() - $_SESSION['login_time'] > (SESSION_TIMEOUT * 60)) {
                self::logout();
                return false;
            }
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::checkSessionTimeout()) {
            header('Location: /LoaningSystem/pages/login.php');
            exit;
        }
    }
    
    /**
     * Require admin
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
        }
    }
}
?>
