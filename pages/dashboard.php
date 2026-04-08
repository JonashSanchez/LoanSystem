<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Loan.php');

Auth::requireLogin();
$user = Auth::getCurrentUser();

// Redirect admins to admin dashboard
if ($user['is_admin']) {
    header('Location: /LoaningSystem/admin/dashboard.php');
    exit;
}

// Check if user data was retrieved
if (!$user) {
    header('Location: /LoaningSystem/pages/login.php');
    exit;
}

// Check if account is disabled
if ($user['status'] === 'Disabled') {
    session_destroy();
    header('Location: /LoaningSystem/pages/login.php?error=Account+has+been+disabled+due+to+missed+payments.+Please+contact+support.');
    exit;
}

// Include Premium feature classes
require_once('../includes/Savings.php');
require_once('../includes/MoneyBack.php');

// Get user's loans
$loans = Loan::getUserLoans($user['id']);
if (!$loans) $loans = [];
$activeLoanCount = count(array_filter($loans, fn($l) => $l['status'] === LOAN_ACTIVE));
$totalBorrowed = array_sum(array_map(fn($l) => $l['principal_amount'], $loans));
$totalRepaid = array_sum(array_map(fn($l) => $l['status'] === LOAN_PAID ? ($l['principal_amount'] + $l['interest_amount']) : 0, $loans));

// Premium features (if applicable)
$savings = $user['account_type'] === 'Premium' ? Savings::getTotalSavings($user['id']) : 0;
$moneyBack = $user['account_type'] === 'Premium' ? MoneyBack::getTotalMoneyBack($user['id']) : 0;
$pendingMoneyBack = $user['account_type'] === 'Premium' ? MoneyBack::getPendingMoneyBack($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="#" class="logo"><?php echo APP_NAME; ?></a>
            <div class="nav-right">
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1>Your Dashboard</h1>
        <p>Manage your loans and account information</p>
    </div>
    
    <div class="container">
        <!-- Account Status Alert -->
        <?php if ($user['status'] === STATUS_PENDING): ?>
            <div class="alert alert-warning">
                <strong>⏳ Account Pending Approval</strong><br>
                Your account is currently pending admin approval. You will be able to apply for loans once approved.
            </div>
        <?php elseif ($user['status'] === STATUS_REJECTED): ?>
            <div class="alert alert-danger">
                <strong>❌ Account Rejected</strong><br>
                Your account registration was rejected. Please contact support for more information.
            </div>
        <?php elseif ($user['status'] === STATUS_SUSPENDED): ?>
            <div class="alert alert-danger">
                <strong>🚫 Account Suspended</strong><br>
                Your account has been suspended. Please contact support for more information.
            </div>
        <?php elseif ($user['status'] === STATUS_APPROVED): ?>
            <div class="alert alert-success">
                <strong>✓ Account Approved</strong><br>
                Your account is active. You can now apply for loans.
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Active Loans</div>
                <div class="stat-number"><?php echo $activeLoanCount; ?></div>
            </div>
            
            <div class="stat-box success">
                <div class="stat-label">Total Borrowed</div>
                <div class="stat-number">₱<?php echo number_format($totalBorrowed, 2); ?></div>
            </div>
            
            <div class="stat-box warning">
                <div class="stat-label">Total Repaid</div>
                <div class="stat-number">₱<?php echo number_format($totalRepaid, 2); ?></div>
            </div>
            
            <div class="stat-box danger">
                <div class="stat-label">Account Status</div>
                <div class="stat-number" style="font-size: 1.2rem;"><?php echo htmlspecialchars($user['status']); ?></div>
            </div>
        </div>
        
        <!-- Premium Account Features -->
        <?php if ($user['account_type'] === 'Premium'): ?>
            <div style="margin: 2rem 0;">
                <h2 style="color: var(--primary); margin-bottom: 1rem;">💎 Premium Account Features</h2>
                
                <div class="card-grid">
                    <div class="stat-box">
                        <div class="stat-label">Total Savings</div>
                        <div class="stat-number" style="color: #27ae60;">₱<?php echo number_format($savings, 2); ?></div>
                        <p style="color: var(--gray); margin-top: 0.5rem; font-size: 0.9rem;">Your savings account</p>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-label">Money Back</div>
                        <div class="stat-number" style="color: #f39c12;">₱<?php echo number_format($moneyBack, 2); ?></div>
                        <p style="color: var(--gray); margin-top: 0.5rem; font-size: 0.9rem;">Earned rewards</p>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-label">Pending Rewards</div>
                        <div class="stat-number" style="color: #e74c3c;">₱<?php echo number_format($pendingMoneyBack, 2); ?></div>
                        <p style="color: var(--gray); margin-top: 0.5rem; font-size: 0.9rem;">To be credited</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin: 1.5rem 0;">
                    <a href="premium-account.php" class="btn btn-secondary">Manage Savings & Rewards</a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Loan Application Button -->
        <?php if ($user['status'] === STATUS_APPROVED): ?>
            <div style="margin-bottom: 2rem; text-align: center;">
                <a href="apply-loan.php" class="btn btn-primary">Apply for New Loan</a>
            </div>
        <?php endif; ?>
        
        <!-- Loans Table -->
        <div class="card">
            <div class="card-header">
                <h3>Your Loans</h3>
            </div>
            
            <?php if (!empty($loans)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Principal Amount</th>
                            <th>Interest</th>
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
                                <td>₱<?php echo number_format($loan['interest_amount'], 2); ?> (<?php echo $loan['interest_rate']; ?>%)</td>
                                <td><?php echo $loan['loan_term']; ?> months</td>
                                <td>₱<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($loan['status']); ?>">
                                        <?php echo $loan['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                                <td>
                                    <a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="btn btn-secondary btn-sm">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body">
                    <p style="color: var(--gray); text-align: center;">No loans yet. <a href="apply-loan.php">Apply for your first loan</a></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Account Information -->
        <div class="card">
            <div class="card-header">
                <h3>Account Information</h3>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Personal Details</h4>
                        <p><strong>Account Type:</strong> <?php echo htmlspecialchars($user['account_type']); ?></p>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                        <p><strong>Age:</strong> <?php echo $user['age']; ?> years old</p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 1rem;">Login Information</h4>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-<?php echo strtolower($user['status']); ?>"><?php echo $user['status']; ?></span></p>
                        <a href="edit-profile.php" class="btn btn-primary btn-sm">Edit Profile</a>
                        <a href="change-password.php" class="btn btn-secondary btn-sm">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
