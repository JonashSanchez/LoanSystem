<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Validator.php');
require_once('../includes/Registration.php');

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header('Location: /LoaningSystem/pages/dashboard.php');
    exit;
}

$errors = [];
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect all form data
    $data = [
        'account_type' => $_POST['account_type'] ?? '',
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'email' => trim($_POST['email'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'birthday' => $_POST['birthday'] ?? '',
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'tin_number' => trim($_POST['tin_number'] ?? ''),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'account_number' => trim($_POST['account_number'] ?? ''),
        'cardholder_name' => trim($_POST['cardholder_name'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_address' => trim($_POST['company_address'] ?? ''),
        'company_phone' => trim($_POST['company_phone'] ?? ''),
        'hr_contact_number' => trim($_POST['hr_contact_number'] ?? ''),
        'position' => trim($_POST['position'] ?? ''),
        'monthly_earnings' => $_POST['monthly_earnings'] ?? '',
    ];
    
    // Register user
    $result = Registration::register($data);
    
    if ($result['success']) {
        $userId = $result['user_id'];
        $success = $result['message'];
        
        // Handle file uploads
        $documents = ['Proof_of_Billing', 'Valid_ID', 'COE'];
        $uploadErrors = [];
        
        foreach ($documents as $docType) {
            $fileKey = strtolower(str_replace('_', '', $docType));
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = Registration::uploadDocument($userId, $docType, $_FILES[$fileKey]);
                if (!$uploadResult['success']) {
                    $uploadErrors[] = $uploadResult['message'];
                }
            }
        }
        
        if (!empty($uploadErrors)) {
            $success .= '<br><br><strong>Document Upload Issues:</strong><br>' . implode('<br>', $uploadErrors);
        }
    } else {
        $errors[] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
    <style>
        .register-form {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            max-width: 900px;
            margin: 0 auto;
        }
        
        body {
            padding-top: 30px;
            padding-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h1 style="color: var(--primary); margin-bottom: 0.5rem;">Create New Account</h1>
            <p style="color: var(--gray); margin-bottom: 2rem;">Please fill in all required fields marked with <span class="required">*</span></p>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <strong>Registration Error:</strong><br>
                    <?php foreach ($errors as $error): ?>
                        - <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong><br>
                    <?php echo $success; ?><br><br>
                    <a href="login.php" class="btn btn-primary btn-sm">Proceed to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <!-- Account Type Section -->
                    <div class="form-section">
                        <h3>Account Type</h3>
                        <div class="form-group">
                            <label for="account_type">Account Type <span class="required">*</span></label>
                            <select id="account_type" name="account_type" required>
                                <option value="">-- Select Account Type --</option>
                                <option value="Basic">Basic (Unlimited slots)</option>
                                <option value="Premium">Premium (Max 50 members)</option>
                            </select>
                            <div class="help-text">Basic accounts have unlimited registration slots. Premium accounts are limited to 50 members.</div>
                        </div>
                    </div>
                    
                    <!-- Login Credentials Section -->
                    <div class="form-section">
                        <h3>Login Credentials</h3>
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" required>
                            <div class="help-text">Minimum 6 characters. Can include letters, numbers, dots, underscores, hyphens, and @ symbol.</div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="password" name="password" required style="padding-right: 40px;">
                                    <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; color: #7f8c8d;">👁️</button>
                                </div>
                                <div class="help-text">Min 8 characters, uppercase, lowercase, number, special char (!@#$%^&*)</div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="confirm_password" name="confirm_password" required style="padding-right: 40px;">
                                    <button type="button" id="toggleConfirmPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; color: #7f8c8d;">👁️</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required>
                                <div class="help-text">Must be a valid email address</div>
                            </div>
                            <div class="form-group">
                                <label for="contact_number">Contact Number <span class="required">*</span></label>
                                <input type="tel" id="contact_number" name="contact_number" required>
                                <div class="help-text">Philippine numbers only (09xxxxxxxxx, +639xxxxxxxxx)</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="birthday">Birthday <span class="required">*</span></label>
                                <input type="date" id="birthday" name="birthday" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address <span class="required">*</span></label>
                            <textarea id="address" name="address" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="tin_number">TIN Number <span class="required">*</span></label>
                            <input type="text" id="tin_number" name="tin_number" placeholder="XXX-XXX-XXX-XXXXX" required>
                            <div class="help-text">Tax Identification Number format: XXX-XXX-XXX-XXXXX</div>
                        </div>
                    </div>
                    
                    <!-- Bank Details Section -->
                    <div class="form-section">
                        <h3>Bank Details</h3>
                        <div class="form-group">
                            <label for="bank_name">Bank Name <span class="required">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="account_number">Account Number <span class="required">*</span></label>
                                <input type="text" id="account_number" name="account_number" required>
                            </div>
                            <div class="form-group">
                                <label for="cardholder_name">Cardholder's Name <span class="required">*</span></label>
                                <input type="text" id="cardholder_name" name="cardholder_name" required>
                                <div class="help-text">⚠ Please ensure the cardholder's name is correct to avoid transaction interruptions</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Company Details Section -->
                    <div class="form-section">
                        <h3>Company Details</h3>
                        <div class="form-group">
                            <label for="company_name">Company Name <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_address">Company Address <span class="required">*</span></label>
                            <textarea id="company_address" name="company_address" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_phone">Company Phone <span class="required">*</span></label>
                                <input type="tel" id="company_phone" name="company_phone" required>
                            </div>
                            <div class="form-group">
                                <label for="hr_contact_number">HR Contact Number</label>
                                <input type="tel" id="hr_contact_number" name="hr_contact_number">
                                <div class="help-text">📞 A number directed to their HR to confirm employment</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Position <span class="required">*</span></label>
                                <input type="text" id="position" name="position" required>
                            </div>
                            <div class="form-group">
                                <label for="monthly_earnings">Monthly Earnings <span class="required">*</span></label>
                                <input type="number" id="monthly_earnings" name="monthly_earnings" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Uploads Section -->
                    <div class="form-section">
                        <h3>Required Documents</h3>
                        <div class="form-group">
                            <label for="proofofbilling">Proof of Billing <span class="required">*</span></label>
                            <input type="file" id="proofofbilling" name="proofofbilling" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="validid">Valid ID (Primary) <span class="required">*</span></label>
                            <input type="file" id="validid" name="validid" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="coe">Certificate of Employment (COE) <span class="required">*</span></label>
                            <input type="file" id="coe" name="coe" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> Your account will be in <strong>Pending</strong> status after registration. An admin will review and approve your application. You will receive a notification once your account is approved.
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary btn-block">Register Account</button>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <p style="color: var(--gray);">Already have an account? <a href="login.php" style="color: var(--secondary); font-weight: 600;">Login here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePassword');
        const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        if (toggleBtn && passwordField) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleBtn.textContent = '🙈';
                } else {
                    passwordField.type = 'password';
                    toggleBtn.textContent = '👁️';
                }
            });
        }
        
        if (toggleConfirmBtn && confirmPasswordField) {
            toggleConfirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirmPasswordField.type === 'password') {
                    confirmPasswordField.type = 'text';
                    toggleConfirmBtn.textContent = '🙈';
                } else {
                    confirmPasswordField.type = 'password';
                    toggleConfirmBtn.textContent = '👁️';
                }
            });
        }
    </script>
</body>
</html>
