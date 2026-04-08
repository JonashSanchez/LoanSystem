<?php
/**
 * Validation Class
 */

class Validator {
    
    /**
     * Validate username
     */
    public static function validateUsername($username) {
        if (strlen($username) < MIN_USERNAME_LENGTH) {
            return "Username must be at least " . MIN_USERNAME_LENGTH . " characters long";
        }
        if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $username)) {
            return "Username can only contain letters, numbers, dots, underscores, hyphens, and @ symbol";
        }
        
        $existing = fetchRow("SELECT id FROM users WHERE username = ?", "s", [$username]);
        if ($existing) {
            return "Username already exists";
        }
        
        return true;
    }
    
    /**
     * Validate password
     */
    public static function validatePassword($password) {
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            return "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*]/', $password)) {
            return "Password must contain at least one special character (!@#$%^&*)";
        }
        return true;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email address";
        }
        
        // Check if email is blocked
        $blocked = fetchRow("SELECT id FROM blocked_emails WHERE email = ?", "s", [$email]);
        if ($blocked) {
            return "This email address is blocked and cannot be used";
        }
        
        // Check if email already registered
        $existing = fetchRow("SELECT id FROM users WHERE email = ?", "s", [$email]);
        if ($existing) {
            return "Email already registered";
        }
        
        return true;
    }
    
    /**
     * Validate contact number (PH only)
     */
    public static function validateContactNumber($number) {
        // Philippine contact number validation
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        // Accept formats: 09xxxxxxxxx, +639xxxxxxxxx, 639xxxxxxxxx, 09123456789
        if (preg_match('/^(09|\+639|639)\d{9}$/', $cleaned)) {
            return true;
        }
        
        return "Invalid Philippine contact number";
    }
    
    /**
     * Validate TIN number (Philippine Tax Identification Number)
     */
    public static function validateTinNumber($tin) {
        $cleaned = preg_replace('/[^0-9-]/', '', $tin);
        // TIN format: XXX-XXX-XXX-XXXXX (12 digits)
        if (preg_match('/^\d{3}-\d{3}-\d{3}-\d{5}$/', $cleaned) || preg_match('/^\d{12}$/', $cleaned)) {
            return true;
        }
        return "Invalid TIN number format (use XXX-XXX-XXX-XXXXX)";
    }
    
    /**
     * Validate bank account number
     */
    public static function validateBankAccountNumber($account) {
        $cleaned = preg_replace('/[^0-9]/', '', $account);
        if (strlen($cleaned) >= 10 && strlen($cleaned) <= 20) {
            return true;
        }
        return "Bank account number must be 10-20 digits";
    }
    
    /**
     * Validate date of birth and calculate age
     */
    public static function validateDateOfBirth($dob) {
        $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dobDate) {
            return ['valid' => false, 'message' => 'Invalid date format'];
        }
        
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
        
        if ($age < 18) {
            return ['valid' => false, 'message' => 'You must be at least 18 years old'];
        }
        
        if ($age > 100) {
            return ['valid' => false, 'message' => 'Invalid date of birth'];
        }
        
        return ['valid' => true, 'age' => $age];
    }
    
    /**
     * Validate monthly earnings
     */
    public static function validateMonthlyEarnings($earnings) {
        $earnings = floatval($earnings);
        if ($earnings <= 0) {
            return "Monthly earnings must be greater than 0";
        }
        return true;
    }
    
    /**
     * Validate upload file
     */
    public static function validateUploadFile($file) {
        if (!isset($file['error'])) {
            return "Invalid file upload";
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "File upload error: " . $file['error'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return "File size exceeds maximum limit of 5MB";
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            return "File type not allowed. Allowed: " . implode(', ', ALLOWED_EXTENSIONS);
        }
        
        return true;
    }
    
    /**
     * Validate loan amount
     */
    public static function validateLoanAmount($amount) {
        $amount = floatval($amount);
        if ($amount <= 0 || $amount > MAX_LOAN_AMOUNT) {
            return "Loan amount must be between 1 and " . MAX_LOAN_AMOUNT;
        }
        return true;
    }
    
    /**
     * Validate loan term
     */
    public static function validateLoanTerm($term) {
        if (!in_array($term, LOAN_TERMS)) {
            return "Invalid loan term. Allowed terms: " . implode(', ', LOAN_TERMS) . " months";
        }
        return true;
    }
}
?>
