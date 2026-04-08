<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Registration.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get registration ID from URL
$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /LoaningSystem/admin/registrations.php');
    exit;
}

// Get user details
$user = fetchRow("SELECT * FROM users WHERE id = ? AND status = ?", "is", [$userId, STATUS_PENDING]);
if (!$user) {
    header('HTTP/1.0 404 Not Found');
    exit('User not found');
}

// Get bank details
$bank = fetchRow("SELECT * FROM bank_details WHERE user_id = ?", "i", [$userId]);

// Get company details
$company = fetchRow("SELECT * FROM company_details WHERE user_id = ?", "i", [$userId]);

// Get documents
$documents = Registration::getUserDocuments($userId);

// Handle approval/rejection
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (in_array($action, [STATUS_APPROVED, STATUS_REJECTED])) {
        $result = executeQuery("UPDATE users SET status = ? WHERE id = ?", "si", [$action, $userId]);
        if ($result) {
            // Log action
            executeQuery(
                "INSERT INTO admin_logs (admin_id, action, target_user_id, ip_address, details) VALUES (?, ?, ?, ?, ?)",
                "issss",
                [$admin['id'], "User " . strtolower($action), $userId, $_SERVER['REMOTE_ADDR'], "Registration review"]
            );
            header('Location: /LoaningSystem/admin/registrations.php?message=' . urlencode('User ' . strtolower($action) . ' successfully'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Registration - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="#" class="logo"><?php echo APP_NAME; ?> - ADMIN</a>
            <div class="nav-right">
                <span style="color: white;">Admin: <?php echo htmlspecialchars($admin['full_name']); ?></span>
                <a href="/LoaningSystem/pages/logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1>Review Registration</h1>
        <p>Review user information before approving or rejecting</p>
    </div>
    
    <div class="container">
        <a href="registrations.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Registrations</a>
        
        <!-- User Information -->
        <div class="card">
            <div class="card-header">
                <h3>User Information</h3>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Personal Details</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></p>
                        <p><strong>Age:</strong> <?php echo $user['age']; ?> years</p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                        <p><strong>TIN Number:</strong> <?php echo htmlspecialchars($user['tin_number']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Account Details</h4>
                        <p><strong>Account Type:</strong> <?php echo htmlspecialchars($user['account_type']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Registered:</strong> <?php echo date('F d, Y h:i A', strtotime($user['created_at'])); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-pending">Pending</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bank Details -->
        <?php if ($bank): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Bank Details</h3>
                </div>
                
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank['bank_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank['account_number']); ?></p>
                        </div>
                        <div>
                            <p><strong>Cardholder's Name:</strong> <?php echo htmlspecialchars($bank['cardholder_name']); ?></p>
                            <p style="color: var(--danger); font-size: 0.9rem;">⚠ Verify if cardholder's name matches the registered user</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Company Details -->
        <?php if ($company): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Company Details</h3>
                </div>
                
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <p><strong>Company Name:</strong> <?php echo htmlspecialchars($company['company_name']); ?></p>
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($company['position']); ?></p>
                            <p><strong>Monthly Earnings:</strong> ₱<?php echo number_format($company['monthly_earnings'], 2); ?></p>
                        </div>
                        <div>
                            <p><strong>Company Address:</strong> <?php echo htmlspecialchars($company['company_address']); ?></p>
                            <p><strong>Company Phone:</strong> <?php echo htmlspecialchars($company['company_phone']); ?></p>
                            <p><strong>HR Contact:</strong> <?php echo htmlspecialchars($company['hr_contact_number'] ?? 'Not provided'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Documents -->
        <div class="card">
            <div class="card-header">
                <h3>Uploaded Documents (<?php echo count($documents); ?>/3)</h3>
            </div>
            
            <?php if (!empty($documents)): ?>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($documents as $doc): ?>
                            <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 6px; text-align: center;">
                                <p style="margin-bottom: 1rem;"><strong><?php echo str_replace('_', ' ', $doc['document_type']); ?></strong></p>
                                <p style="color: var(--gray); font-size: 0.9rem;">Uploaded: <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></p>
                                <a href="/LoaningSystem/public/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">View Document</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <p style="color: var(--danger); text-align: center;">Missing documents! User has not uploaded required files.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Form -->
        <div class="card">
            <div class="card-header">
                <h3>Registration Decision</h3>
            </div>
            
            <form method="POST">
                <div class="card-body">
                    <p style="color: var(--gray); margin-bottom: 1.5rem;">Select an action to approve or reject this registration:</p>
                    
                    <div class="btn-group">
                        <button type="submit" name="action" value="<?php echo STATUS_APPROVED; ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to APPROVE this registration?');">✓ Approve Registration</button>
                        <button type="submit" name="action" value="<?php echo STATUS_REJECTED; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to REJECT this registration?');">✕ Reject Registration</button>
                        <a href="registrations.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
