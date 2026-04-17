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
$fieldErrors = [];
$success = '';
$formData = [
    'account_type' => '',
    'username' => '',
    'password' => '',
    'confirm_password' => '',
    'email' => '',
    'full_name' => '',
    'address' => '',
    'gender' => '',
    'birthday' => '',
    'contact_number' => '',
    'tin_number' => '',
    'bank_name' => '',
    'account_number' => '',
    'cardholder_name' => '',
    'company_name' => '',
    'company_address' => '',
    'company_phone' => '',
    'hr_contact_number' => '',
    'position' => '',
    'monthly_earnings' => '',
];

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
    
    // Keep form data for repopulation on error
    $formData = $data;
    
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
        
        // Clear form data on success
        $formData = array_fill_keys(array_keys($formData), '');
    } else {
        // Check if we have field-specific errors
        if (isset($result['fieldErrors']) && is_array($result['fieldErrors'])) {
            $fieldErrors = $result['fieldErrors'];
        } else {
            // Fallback to generic error
            $errors[] = $result['message'];
        }
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
        body {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 50%, var(--secondary) 100%) !important;
            min-height: 100vh;
            padding: 30px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-wrapper {
            width: 100%;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
        }
        
        .register-form {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 0.5px solid var(--border);
            overflow: hidden;
        }
        
        .register-form > :first-child {
            padding: 40px 44px;
            background: white;
        }
        
        .register-form h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .register-form > p {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 24px;
        }
        
        .form-section {
            background: var(--lighter);
            padding: 22px 24px;
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            border-left: 3px solid var(--secondary);
        }
        
        .form-section h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        
        .btn-group {
            padding: 0 44px 40px 44px;
            background: white;
            border-top: 0.5px solid var(--border);
            margin: 0;
        }
        
        .register-form > :last-child {
            padding: 40px 44px;
            background: white;
            border-top: 0.5px solid var(--border);
        }
        
        .register-form > .alert {
            margin: 20px 44px 0;
            border-radius: 0;
        }
        
        .field-error {
            color: #c0392b;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
            font-weight: 600;
        }
    </style>
    
    <?php
    // Helper function to display field error
    function showFieldError($fieldName) {
        global $fieldErrors;
        if (isset($fieldErrors[$fieldName])) {
            echo '<span class="field-error">⚠ ' . htmlspecialchars($fieldErrors[$fieldName]) . '</span>';
        }
    }
    
    // Helper function to get field value
    function getFieldValue($fieldName) {
        global $formData;
        return htmlspecialchars($formData[$fieldName] ?? '');
    }
    ?>
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="register-form">
                <div>
                    <h1>Create Account</h1>
                    <p>Complete all required fields marked with <span class="required">*</span></p>
                </div>
                
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <strong>⚠ Registration Error:</strong><br>
                        <?php foreach ($errors as $error): ?>
                            - <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div style="padding: 40px 44px; background: white;">
                        <div class="alert alert-success">
                            <strong>✅ Success!</strong><br>
                            <?php echo $success; ?><br><br>
                            <a href="login.php" class="btn btn-primary btn-sm">🚀 Proceed to Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 0 44px 20px 44px; background: white;">
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
                            <input type="text" id="username" name="username" value="<?php echo getFieldValue('username'); ?>" required>
                            <?php showFieldError('username'); ?>
                            <div class="help-text">Minimum 6 characters. Can include letters, numbers, dots, underscores, hyphens, and @ symbol.</div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="password" name="password" value="<?php echo getFieldValue('password'); ?>" required style="padding-right: 40px;">
                                    <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; color: #7f8c8d;">👁️</button>
                                </div>
                                <?php showFieldError('password'); ?>
                                <div class="help-text">Min 8 characters, uppercase, lowercase, number, special char (!@#$%^&*)</div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="confirm_password" name="confirm_password" value="<?php echo getFieldValue('confirm_password'); ?>" required style="padding-right: 40px;">
                                    <button type="button" id="toggleConfirmPassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; color: #7f8c8d;">👁️</button>
                                </div>
                                <?php showFieldError('confirm_password'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo getFieldValue('full_name'); ?>" required>
                            <?php showFieldError('full_name'); ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo getFieldValue('email'); ?>" required>
                                <?php showFieldError('email'); ?>
                                <div class="help-text">Must be a valid email address</div>
                            </div>
                            <div class="form-group">
                                <label for="contact_number">Contact Number <span class="required">*</span></label>
                                <input type="tel" id="contact_number" name="contact_number" value="<?php echo getFieldValue('contact_number'); ?>" required>
                                <?php showFieldError('contact_number'); ?>
                                <div class="help-text">Philippine numbers only (09xxxxxxxxx, +639xxxxxxxxx)</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="birthday">Birthday <span class="required">*</span></label>
                                <input type="date" id="birthday" name="birthday" value="<?php echo getFieldValue('birthday'); ?>" required>
                                <?php showFieldError('birthday'); ?>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?php echo $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address <span class="required">*</span></label>
                            <textarea id="address" name="address" required><?php echo getFieldValue('address'); ?></textarea>
                            <?php showFieldError('address'); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="tin_number">TIN Number <span class="required">*</span></label>
                            <input type="text" id="tin_number" name="tin_number" value="<?php echo getFieldValue('tin_number'); ?>" placeholder="XXX-XXX-XXX-XXXXX" required>
                            <?php showFieldError('tin_number'); ?>
                            <div class="help-text">Tax Identification Number format: XXX-XXX-XXX-XXXXX</div>
                        </div>
                    </div>
                    
                    <!-- Bank Details Section -->
                    <div class="form-section">
                        <h3>Bank Details</h3>
                        <div class="form-group">
                            <label for="bank_name">Bank Name <span class="required">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" value="<?php echo getFieldValue('bank_name'); ?>" required>
                            <?php showFieldError('bank_name'); ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="account_number">Account Number <span class="required">*</span></label>
                                <input type="text" id="account_number" name="account_number" value="<?php echo getFieldValue('account_number'); ?>" required>
                                <?php showFieldError('account_number'); ?>
                            </div>
                            <div class="form-group">
                                <label for="cardholder_name">Cardholder's Name <span class="required">*</span></label>
                                <input type="text" id="cardholder_name" name="cardholder_name" value="<?php echo getFieldValue('cardholder_name'); ?>" required>
                                <?php showFieldError('cardholder_name'); ?>
                                <div class="help-text">⚠ Please ensure the cardholder's name is correct to avoid transaction interruptions</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Company Details Section -->
                    <div class="form-section">
                        <h3>Company Details</h3>
                        <div class="form-group">
                            <label for="company_name">Company Name <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo getFieldValue('company_name'); ?>" required>
                            <?php showFieldError('company_name'); ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_address">Company Address <span class="required">*</span></label>
                            <textarea id="company_address" name="company_address" required><?php echo getFieldValue('company_address'); ?></textarea>
                            <?php showFieldError('company_address'); ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_phone">Company Phone <span class="required">*</span></label>
                                <input type="tel" id="company_phone" name="company_phone" value="<?php echo getFieldValue('company_phone'); ?>" required>
                                <?php showFieldError('company_phone'); ?>
                            </div>
                            <div class="form-group">
                                <label for="hr_contact_number">HR Contact Number</label>
                                <input type="tel" id="hr_contact_number" name="hr_contact_number" value="<?php echo getFieldValue('hr_contact_number'); ?>">
                                <?php showFieldError('hr_contact_number'); ?>
                                <div class="help-text">📞 A number directed to their HR to confirm employment</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Position <span class="required">*</span></label>
                                <input type="text" id="position" name="position" value="<?php echo getFieldValue('position'); ?>" required>
                                <?php showFieldError('position'); ?>
                            </div>
                            <div class="form-group">
                                <label for="monthly_earnings">Monthly Earnings <span class="required">*</span></label>
                                <input type="number" id="monthly_earnings" name="monthly_earnings" value="<?php echo getFieldValue('monthly_earnings'); ?>" step="0.01" required>
                                <?php showFieldError('monthly_earnings'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Uploads Section -->
                    <div class="form-section">
                        <h3>Required Documents</h3>
                        <div class="form-group">
                            <label for="proofofbilling">Proof of Billing <span class="required">*</span></label>
                            <input type="file" id="proofofbilling" name="proofofbilling" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <?php showFieldError('proofofbilling'); ?>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="validid">Valid ID (Primary) <span class="required">*</span></label>
                            <input type="file" id="validid" name="validid" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <?php showFieldError('validid'); ?>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="coe">Certificate of Employment (COE) <span class="required">*</span></label>
                            <input type="file" id="coe" name="coe" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <?php showFieldError('coe'); ?>
                            <div class="help-text">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" style="margin: 0 44px 0 44px;">
                        <strong>ℹ Note:</strong> Your account will be <strong>Pending</strong> after registration. An admin will review and approve your application.
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary btn-block">📝 Register Account</button>
                    </div>
                    
                    <div style="padding: 0 44px 20px 44px; background: white; border-top: 0.5px solid var(--border); text-align: center;">
                        <p style="font-size: 13px; color: var(--gray); margin: 0;">Already have an account?</p>
                        <a href="login.php" style="display: inline-block; margin-top: 8px; color: var(--secondary); font-weight: 700; text-decoration: none; transition: all 0.2s;">
                            Sign In →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
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
