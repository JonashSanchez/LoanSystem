<?php
/**
 * Application Constants
 */

// Loan Settings
define('MAX_LOAN_AMOUNT', 10000);
define('LOAN_INTEREST_RATE', 3.00);
define('LOAN_TERMS', [1, 3, 6, 12]); // months

// Registration Settings
define('MAX_BASIC_MEMBERS', null); // unlimited
define('MAX_PREMIUM_MEMBERS', 50);

// Validation Rules
define('MIN_USERNAME_LENGTH', 6);
define('MIN_PASSWORD_LENGTH', 8);
define('APP_NAME', 'Loaning System');
define('APP_VERSION', '1.0.0');

// Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Status Constants
define('STATUS_PENDING', 'Pending');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');
define('STATUS_SUSPENDED', 'Suspended');

// Loan Status
define('LOAN_ACTIVE', 'Active');
define('LOAN_PAID', 'Paid');
define('LOAN_DEFAULTED', 'Defaulted');
define('LOAN_CANCELLED', 'Cancelled');

// Session timeout (in minutes)
define('SESSION_TIMEOUT', 30);
?>
