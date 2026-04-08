<?php
/**
 * Money Back Earnings Class (Premium accounts only)
 */

class MoneyBack {
    
    /**
     * Get user's total money back earned
     */
    public static function getTotalMoneyBack($userId) {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT SUM(amount) as total FROM money_back WHERE user_id = ? AND status = 'Credited'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'] ?? 0;
    }
    
    /**
     * Get pending money back
     */
    public static function getPendingMoneyBack($userId) {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT SUM(amount) as total FROM money_back WHERE user_id = ? AND status = 'Pending'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'] ?? 0;
    }
    
    /**
     * Get money back history
     */
    public static function getMoneyBackHistory($userId, $limit = 10) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            SELECT * FROM money_back 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    }
    
    /**
     * Add money back entry
     */
    public static function addMoneyBack($userId, $amount, $referenceType, $referenceId = null, $status = 'Pending') {
        global $mysqli;
        
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Amount must be greater than 0'];
        }
        
        $stmt = $mysqli->prepare("
            INSERT INTO money_back (user_id, amount, reference_type, reference_id, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("idssi", $userId, $amount, $referenceType, $referenceId, $status);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Money back credited'];
        }
        
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error: ' . $error];
    }
    
    /**
     * Credit pending money back to savings
     */
    public static function creditMoneyBack($userId) {
        global $mysqli;
        
        // Get pending amount
        $pending = self::getPendingMoneyBack($userId);
        
        if ($pending <= 0) {
            return ['success' => false, 'message' => 'No pending money back to credit'];
        }
        
        // Update status
        $stmt = $mysqli->prepare("
            UPDATE money_back 
            SET status = 'Credited', credited_at = NOW() 
            WHERE user_id = ? AND status = 'Pending'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Add to savings
        require_once 'Savings.php';
        Savings::addSavings($userId, $pending, 'Deposit', 'Money back credit');
        
        return ['success' => true, 'message' => 'Money back credited to savings'];
    }
}
?>
