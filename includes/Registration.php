<?php
/**
 * User Registration Class
 */

class Registration {
    
    /**
     * Register a new user
     */
    public static function register($data) {
        // Validate required fields
        $requiredFields = [
            'account_type', 'username', 'password', 'confirm_password',
            'email', 'full_name', 'address', 'birthday', 'contact_number',
            'tin_number', 'bank_name', 'account_number', 'cardholder_name',
            'company_name', 'company_address', 'company_phone', 'position', 'monthly_earnings'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required'];
            }
        }
        
        // Validate username
        $usernameValidator = Validator::validateUsername($data['username']);
        if ($usernameValidator !== true) {
            return ['success' => false, 'message' => $usernameValidator];
        }
        
        // Validate password
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        $passwordValidator = Validator::validatePassword($data['password']);
        if ($passwordValidator !== true) {
            return ['success' => false, 'message' => $passwordValidator];
        }
        
        // Validate email
        $emailValidator = Validator::validateEmail($data['email']);
        if ($emailValidator !== true) {
            return ['success' => false, 'message' => $emailValidator];
        }
        
        // Validate contact number
        $contactValidator = Validator::validateContactNumber($data['contact_number']);
        if ($contactValidator !== true) {
            return ['success' => false, 'message' => $contactValidator];
        }
        
        // Validate TIN
        $tinValidator = Validator::validateTinNumber($data['tin_number']);
        if ($tinValidator !== true) {
            return ['success' => false, 'message' => $tinValidator];
        }
        
        // Validate bank account
        $accountValidator = Validator::validateBankAccountNumber($data['account_number']);
        if ($accountValidator !== true) {
            return ['success' => false, 'message' => $accountValidator];
        }
        
        // Validate DOB and calculate age
        $dobValidator = Validator::validateDateOfBirth($data['birthday']);
        if (!$dobValidator['valid']) {
            return ['success' => false, 'message' => $dobValidator['message']];
        }
        $age = $dobValidator['age'];
        
        // Validate monthly earnings
        $earningsValidator = Validator::validateMonthlyEarnings($data['monthly_earnings']);
        if ($earningsValidator !== true) {
            return ['success' => false, 'message' => $earningsValidator];
        }
        
        // Check account type limits for Premium
        if ($data['account_type'] === 'Premium') {
            $premiumCount = fetchRow(
                "SELECT COUNT(*) as count FROM users WHERE account_type = 'Premium' AND status != 'Rejected'",
                "", []
            );
            if ($premiumCount['count'] >= MAX_PREMIUM_MEMBERS) {
                return ['success' => false, 'message' => 'Premium account slots are full. Please choose Basic account.'];
            }
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Begin transaction
        global $mysqli;
        $mysqli->begin_transaction();
        
        try {
            // Insert user
            $insertUser = executeQuery(
                "INSERT INTO users (account_type, username, password, email, full_name, address, gender, birthday, age, contact_number, tin_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssssssssdiss",
                [
                    $data['account_type'],
                    $data['username'],
                    $hashedPassword,
                    $data['email'],
                    $data['full_name'],
                    $data['address'],
                    $data['gender'] ?? null,
                    $data['birthday'],
                    $age,
                    $data['contact_number'],
                    $data['tin_number'],
                    STATUS_PENDING
                ]
            );
            
            if (!$insertUser) {
                throw new Exception("Error inserting user");
            }
            
            $userId = $insertUser->insert_id;
            
            // Insert bank details
            $insertBank = executeQuery(
                "INSERT INTO bank_details (user_id, bank_name, account_number, cardholder_name) VALUES (?, ?, ?, ?)",
                "isss",
                [$userId, $data['bank_name'], $data['account_number'], $data['cardholder_name']]
            );
            
            if (!$insertBank) {
                throw new Exception("Error inserting bank details");
            }
            
            // Insert company details
            $insertCompany = executeQuery(
                "INSERT INTO company_details (user_id, company_name, company_address, company_phone, position, monthly_earnings, hr_contact_number) VALUES (?, ?, ?, ?, ?, ?, ?)",
                "issssd s",
                [
                    $userId,
                    $data['company_name'],
                    $data['company_address'],
                    $data['company_phone'],
                    $data['position'],
                    $data['monthly_earnings'],
                    $data['hr_contact_number'] ?? null
                ]
            );
            
            if (!$insertCompany) {
                throw new Exception("Error inserting company details");
            }
            
            // Commit transaction
            $mysqli->commit();
            
            return [
                'success' => true,
                'message' => 'Registration successful! Your account is pending admin approval.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            $mysqli->rollback();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get registration details for admin review
     */
    public static function getPendingRegistrations($limit = 20, $offset = 0) {
        return fetchAll(
            "SELECT u.*, COUNT(d.id) as documents_count FROM users u LEFT JOIN documents d ON u.id = d.user_id WHERE u.status = ? GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
            "sii",
            [STATUS_PENDING, $limit, $offset]
        );
    }
    
    /**
     * Get total pending registrations count
     */
    public static function getPendingCount() {
        $result = fetchRow("SELECT COUNT(*) as count FROM users WHERE status = ?", "s", [STATUS_PENDING]);
        return $result['count'];
    }
    
    /**
     * Upload registration documents
     */
    public static function uploadDocument($userId, $documentType, $file) {
        // Validate file
        $validator = Validator::validateUploadFile($file);
        if ($validator !== true) {
            return ['success' => false, 'message' => $validator];
        }
        
        // Create upload directory if not exists
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        
        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $userId . "_" . $documentType . "_" . time() . "." . $ext;
        $filePath = UPLOAD_DIR . $filename;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Error uploading file'];
        }
        
        // Insert document record
        $result = executeQuery(
            "INSERT INTO documents (user_id, document_type, file_path) VALUES (?, ?, ?)",
            "iss",
            [$userId, $documentType, 'uploads/' . $filename]
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Document uploaded successfully'];
        }
        
        return ['success' => false, 'message' => 'Error saving document record'];
    }
    
    /**
     * Get user documents
     */
    public static function getUserDocuments($userId) {
        return fetchAll("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC", "i", [$userId]);
    }
}
?>
