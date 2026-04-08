<?php
/**
 * Savings Management Class (Premium accounts only)
 */

class Savings {
    
    /**
     * Get user's total savings
     */
    public static function getTotalSavings($userId) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            SELECT SUM(CASE 
                WHEN transaction_type = 'Deposit' THEN amount 
                WHEN transaction_type = 'Withdrawal' THEN -amount 
                ELSE 0 END) as total 
            FROM savings 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'] ?? 0;
    }
    
    /**
     * Get savings history
     */
    public static function getSavingsHistory($userId, $limit = 10) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            SELECT * FROM savings 
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
     * Add savings
     */
    public static function addSavings($userId, $amount, $type = 'Deposit', $description = '') {
        global $mysqli;
        
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Amount must be greater than 0'];
        }
        
        $stmt = $mysqli->prepare("
            INSERT INTO savings (user_id, amount, transaction_type, description) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("idss", $userId, $amount, $type, $description);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Savings added successfully'];
        }
        
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Error: ' . $error];
    }
    
    /**
     * Withdraw savings
     */
    public static function withdrawSavings($userId, $amount, $description = '') {
        global $mysqli;
        
        // Check balance
        $currentBalance = self::getTotalSavings($userId);
        if ($currentBalance < $amount) {
            return ['success' => false, 'message' => 'Insufficient savings balance'];
        }
        
        return self::addSavings($userId, $amount, 'Withdrawal', $description);
    }
}
?>
