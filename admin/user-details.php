<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Loan.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get user ID from URL
$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /LoaningSystem/admin/users.php');
    exit;
}

// Get user details
$user = fetchRow("SELECT * FROM users WHERE id = ? AND is_admin = FALSE", "i", [$userId]);
if (!$user) {
    header('HTTP/1.0 404 Not Found');
    exit('User not found');
}

// Get bank and company details
$bank = fetchRow("SELECT * FROM bank_details WHERE user_id = ?", "i", [$userId]);
$company = fetchRow("SELECT * FROM company_details WHERE user_id = ?", "i", [$userId]);

// Get user's loans
$loans = Loan::getUserLoans($userId);
$totalLoans = count($loans);
$activeLoanCount = count(array_filter($loans, fn($l) => $l['status'] === LOAN_ACTIVE));
$totalBorrowed = array_sum(array_map(fn($l) => $l['principal_amount'], $loans));
$totalRepaid = array_sum(array_map(fn($l) => $l['status'] === LOAN_PAID ? ($l['principal_amount'] + $l['interest_amount']) : 0, $loans));

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, [STATUS_APPROVED, STATUS_SUSPENDED, STATUS_REJECTED])) {
        $result = executeQuery("UPDATE users SET status = ? WHERE id = ?", "si", [$newStatus, $userId]);
        if ($result) {
            // Log action
            executeQuery(
                "INSERT INTO admin_logs (admin_id, action, target_user_id, ip_address) VALUES (?, ?, ?, ?)",
                "isss",
                [$admin['id'], "User status changed to " . $newStatus, $userId, $_SERVER['REMOTE_ADDR']]
            );
            // Refresh user data
            $user = fetchRow("SELECT * FROM users WHERE id = ? AND is_admin = FALSE", "i", [$userId]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo APP_NAME; ?></title>
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
        <h1>User Details</h1>
        <p>View and manage user information</p>
    </div>
    
    <div class="container">
        <a href="users.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Users</a>
        
        <!-- User Information -->
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><?php echo htmlspecialchars($user['full_name']); ?> (@<?php echo htmlspecialchars($user['username']); ?>)</h3>
                    <span class="status-badge status-<?php echo strtolower($user['status']); ?>"><?php echo $user['status']; ?></span>
                </div>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Personal</h4>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></p>
                        <p><strong>Age:</strong> <?php echo $user['age']; ?></p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Account</h4>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($user['account_type']); ?></p>
                        <p><strong>TIN:</strong> <?php echo htmlspecialchars($user['tin_number']); ?></p>
                        <p><strong>Registered:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($user['status']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Loans</h4>
                        <p><strong>Total Loans:</strong> <?php echo $totalLoans; ?></p>
                        <p><strong>Active Loans:</strong> <?php echo $activeLoanCount; ?></p>
                        <p><strong>Total Borrowed:</strong> ₱<?php echo number_format($totalBorrowed, 2); ?></p>
                        <p><strong>Total Repaid:</strong> ₱<?php echo number_format($totalRepaid, 2); ?></p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Address</h4>
                        <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
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
                            <p><strong>Bank:</strong> <?php echo htmlspecialchars($bank['bank_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank['account_number']); ?></p>
                        </div>
                        <div>
                            <p><strong>Cardholder:</strong> <?php echo htmlspecialchars($bank['cardholder_name']); ?></p>
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
                            <p><strong>Company:</strong> <?php echo htmlspecialchars($company['company_name']); ?></p>
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($company['position']); ?></p>
                            <p><strong>Monthly Earnings:</strong> ₱<?php echo number_format($company['monthly_earnings'], 2); ?></p>
                        </div>
                        <div>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($company['company_phone']); ?></p>
                            <p><strong>HR Contact:</strong> <?php echo htmlspecialchars($company['hr_contact_number'] ?? 'Not provided'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- User Loans -->
        <div class="card">
            <div class="card-header">
                <h3>User Loans (<?php echo count($loans); ?> total)</h3>
            </div>
            
            <?php if (!empty($loans)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Principal</th>
                            <th>Term</th>
                            <th>Monthly Payment</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>#<?php echo $loan['id']; ?></td>
                                <td>₱<?php echo number_format($loan['principal_amount'], 2); ?></td>
                                <td><?php echo $loan['loan_term']; ?> mo</td>
                                <td>₱<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($loan['status']); ?>"><?php echo $loan['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                                <td><a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="btn btn-primary btn-sm">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No loans</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Account Management -->
        <div class="card">
            <div class="card-header">
                <h3>Account Management</h3>
            </div>
            
            <form method="POST" class="card-body">
                <p style="color: var(--gray); margin-bottom: 1.5rem;">Change user account status:</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Account Status</label>
                        <select id="status" name="status">
                            <option value="<?php echo STATUS_APPROVED; ?>" <?php echo $user['status'] === STATUS_APPROVED ? 'selected' : ''; ?>>Approved</option>
                            <option value="<?php echo STATUS_SUSPENDED; ?>" <?php echo $user['status'] === STATUS_SUSPENDED ? 'selected' : ''; ?>>Suspended</option>
                            <option value="<?php echo STATUS_REJECTED; ?>" <?php echo $user['status'] === STATUS_REJECTED ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Update user status?');">Update Status</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
