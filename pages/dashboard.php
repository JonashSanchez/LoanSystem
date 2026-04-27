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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 50%, var(--secondary) 100%);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }
        
        .user-sidebar {
            width: 280px;
            background: rgba(30, 33, 64, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 24px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            color: white;
            z-index: 1000;
        }
        
        .user-logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
            color: white;
        }
        
        .user-logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
        }
        
        .user-logo-text {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .user-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .user-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .user-nav-item:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .user-main {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .user-nav-top {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 0.5px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .user-nav-top .container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }
        
        .user-content {
            flex: 1;
            padding: 40px;
            overflow-x: hidden;
        }
        
        .user-header-section {
            margin-bottom: 40px;
        }
        
        .user-header-section h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        
        .user-header-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }
        
        .section-title {
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 24px;
            margin-top: 48px;
            letter-spacing: -0.5px;
            background: rgba(102, 126, 234, 0.3);
            padding: 16px 20px;
            border-left: 4px solid var(--secondary);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .stat-cards.cards-2col {
            grid-template-columns: repeat(2, 1fr);
        }
            gap: 12px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        
        .stat-label {
            color: #888;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 3px;
            letter-spacing: -0.5px;
            word-break: break-word;
        }
        
        .stat-subtitle {
            color: #aaa;
            font-size: 11px;
            word-break: break-word;
            line-height: 1.3;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--secondary);
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-align: center;
        }
        
        .stat-card:nth-child(2) {
            border-left-color: #764ba2;
        }
        
        .stat-card:nth-child(3) {
            border-left-color: #f093fb;
        }
        
        .stat-card:nth-child(4) {
            border-left-color: #00d4ff;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-label {
            color: #999;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -1px;
        }
        
        .stat-subtitle {
            color: #bbb;
            font-size: 13px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 0.5px solid;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert-warning { background: rgba(243, 156, 18, 0.1); border-color: #f39c12; color: #c27e1a; }
        .alert-danger { background: rgba(231, 76, 60, 0.1); border-color: #e74c3c; color: #c0392b; }
        .alert-success { background: rgba(39, 174, 96, 0.1); border-color: #27ae60; color: #1e8449; }
        
        @media (max-width: 768px) {
            .user-sidebar {
                width: 240px;
            }
            .user-main {
                margin-left: 240px;
            }
            .user-content {
                padding: 20px;
            }
            .stat-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            .stat-cards.cards-2col {
                grid-template-columns: 1fr;
            }
            .section-title {
                font-size: 22px;
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="user-sidebar">
        <a href="#" class="user-logo-section">
            <div class="user-logo-icon">💰</div>
            <div class="user-logo-text"><?php echo APP_NAME; ?></div>
        </a>
        
        <div class="user-nav">
            <a href="dashboard.php" class="user-nav-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="apply-loan.php" class="user-nav-item">
                <span>💵</span>
                <span>Apply for Loan</span>
            </a>
            <a href="loan-details.php" class="user-nav-item">
                <span>📋</span>
                <span>My Loans</span>
            </a>
            <?php if ($user['account_type'] === 'Premium'): ?>
            <a href="premium-account.php" class="user-nav-item">
                <span>💎</span>
                <span>Premium Account</span>
            </a>
            <?php endif; ?>
            <a href="register.php" class="user-nav-item">
                <span>⚙️</span>
                <span>Account Settings</span>
            </a>
            <a href="logout.php" class="user-nav-item" style="margin-top: auto;">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="user-main">
        <!-- Top Navigation -->
        <div class="user-nav-top">
            <div class="container" style="display: flex; justify-content: flex-end;">
                <span style="color: #666; font-size: 14px;">Welcome, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="user-content">
            <div class="user-header-section">
                <h1>Your Dashboard</h1>
                <p>Manage your loans and account information</p>
            </div>
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
        
            <!-- Loan Overview Section -->
            <h2 class="section-title">Loan Overview</h2>
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-label">Active Loans</div>
                    <div class="stat-number" style="color: var(--secondary);"><?php echo $activeLoanCount; ?></div>
                    <div class="stat-subtitle">Currently active</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Borrowed</div>
                    <div class="stat-number" style="color: var(--success);">₱<?php echo number_format($totalBorrowed, 2); ?></div>
                    <div class="stat-subtitle">All-time borrowed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Repaid</div>
                    <div class="stat-number" style="color: var(--info);">₱<?php echo number_format($totalRepaid, 2); ?></div>
                    <div class="stat-subtitle">Completed payments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Account Status</div>
                    <div class="stat-number" style="color: var(--accent);">
                        <?php echo htmlspecialchars($user['status']); ?>
                    </div>
                    <div class="stat-subtitle">Your account status</div>
                </div>
            </div>
        
            <!-- Premium Account Features -->
            <?php if ($user['account_type'] === 'Premium'): ?>
                <h2 class="section-title">💎 Premium Account Features</h2>
                <div class="stat-cards cards-2col">
                    <div class="stat-card">
                        <div class="stat-label">Total Savings</div>
                        <div class="stat-number" style="color: #27ae60;">₱<?php echo number_format($savings, 2); ?></div>
                        <div class="stat-subtitle">Your savings account</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Money Back Earned</div>
                        <div class="stat-number" style="color: #f39c12;">₱<?php echo number_format($moneyBack, 2); ?></div>
                        <div class="stat-subtitle">Earned rewards</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Pending Rewards</div>
                        <div class="stat-number" style="color: #e74c3c;">₱<?php echo number_format($pendingMoneyBack, 2); ?></div>
                        <div class="stat-subtitle">To be credited</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-bottom: 40px;">
                    <a href="premium-account.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 14px;">Manage Savings & Rewards →</a>
                </div>
            <?php endif; ?>
            
            <!-- Loan Application Button -->
            <?php if ($user['status'] === STATUS_APPROVED): ?>
                <div style="text-align: center; margin-bottom: 40px;">
                    <a href="apply-loan.php" class="btn btn-primary" style="padding: 10px 20px; font-size: 14px;">Apply for New Loan →</a>
                </div>
            <?php endif; ?>
            
            <!-- Recent Loans -->
            <h2 class="section-title">Your Loans</h2>
            <?php if (!empty($loans)): ?>
                <div style="background: rgba(255, 255, 255, 0.95); border: 0.5px solid rgba(255, 255, 255, 0.2); border-radius: 12px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 1px solid #eee;">
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: #333; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Loan ID</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: #333; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Amount</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: #333; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Term</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: #333; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 700; color: #333; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                <tr style="border-bottom: 0.5px solid #f0f0f0;">
                                    <td style="padding: 14px 16px; color: #666; font-size: 13px;"><strong>#<?php echo $loan['id']; ?></strong></td>
                                    <td style="padding: 14px 16px; color: #666; font-size: 13px;">₱<?php echo number_format($loan['principal_amount'], 2); ?></td>
                                    <td style="padding: 14px 16px; color: #666; font-size: 13px;"><?php echo $loan['loan_term']; ?> months</td>
                                    <td style="padding: 14px 16px; color: #666; font-size: 13px;"><span style="background: #f0f2f8; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo $loan['status']; ?></span></td>
                                    <td style="padding: 14px 16px; color: #666; font-size: 13px;">
                                        <a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 11px;">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: rgba(255, 255, 255, 0.95); border: 0.5px solid rgba(255, 255, 255, 0.2); border-radius: 12px; padding: 40px; text-align: center;">
                    <p style="color: #999; font-size: 14px;">No loans yet. <a href="apply-loan.php" style="color: var(--secondary); font-weight: 700;">Apply for your first loan</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
