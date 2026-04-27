<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Loan.php');

Auth::requireLogin();
$user = Auth::getCurrentUser();

// Redirect admins to admin area
if ($user['is_admin']) {
    header('Location: /LoaningSystem/admin/dashboard.php');
    exit;
}

// Get loan ID from URL
$loanId = intval($_GET['id'] ?? 0);
if ($loanId <= 0) {
    header('Location: /LoaningSystem/pages/dashboard.php');
    exit;
}

// Get loan details
$loan = Loan::getLoanDetails($loanId);
if (!$loan || $loan['user_id'] !== $user['id']) {
    header('HTTP/1.0 404 Not Found');
    exit('Loan not found');
}

// Get loan payments
$payments = Loan::getLoanPayments($loanId);
$totalPaid = array_sum(array_map(fn($p) => $p['amount_paid'], $payments));
$remainingBalance = ($loan['principal_amount'] + $loan['interest_amount']) - $totalPaid;

// Get amortization schedule
$schedule = Loan::getAmortizationSchedule($loanId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Details - <?php echo APP_NAME; ?></title>
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
            gap: 16px;
            margin-bottom: 40px;
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
            <a href="loan-details.php" class="user-nav-item" style="background: rgba(102, 126, 234, 0.2); border-color: rgba(102, 126, 234, 0.5); color: white;">
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
        
        <!-- Content -->
        <div class="user-content">
            <div class="user-header-section">
                <h1>Loan #<?php echo $loan['id']; ?> Details</h1>
                <p>View your loan information and payments</p>
            </div>
        
        <!-- Loan Status -->
        <h2 class="section-title">Loan Summary</h2>
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-label">Loan Status</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-<?php echo strtolower($loan['status']); ?>">
                        <?php echo $loan['status']; ?>
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Amount Borrowed</div>
                <div class="stat-number" style="color: #27ae60;">₱<?php echo number_format($loan['principal_amount'], 2); ?></div>
                <div class="stat-subtitle">Principal amount</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Total Repaid</div>
                <div class="stat-number" style="color: #f39c12;">₱<?php echo number_format($totalPaid, 2); ?></div>
                <div class="stat-subtitle">Payments made</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Remaining Balance</div>
                <div class="stat-number" style="color: #e74c3c;">₱<?php echo number_format(max(0, $remainingBalance), 2); ?></div>
                <div class="stat-subtitle">Amount due</div>
            </div>
        </div>
        
        <!-- Loan Overview -->
        <div class="card">
            <div class="card-header">
                <h3>Loan Overview</h3>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Loan Amount</h4>
                        <p style="font-size: 1.5rem; color: var(--secondary); font-weight: bold;">₱<?php echo number_format($loan['principal_amount'], 2); ?></p>
                        <p style="color: var(--gray); font-size: 0.9rem;">Principal amount requested</p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Interest</h4>
                        <p style="font-size: 1.5rem; color: var(--warning); font-weight: bold;">₱<?php echo number_format($loan['interest_amount'], 2); ?></p>
                        <p style="color: var(--gray); font-size: 0.9rem;">3% charged upfront</p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Net Amount Received</h4>
                        <p style="font-size: 1.5rem; color: var(--success); font-weight: bold;">₱<?php echo number_format($loan['net_amount_received'], 2); ?></p>
                        <p style="color: var(--gray); font-size: 0.9rem;">Amount after deducting interest</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <p><strong>Loan Term:</strong> <?php echo $loan['loan_term']; ?> months</p>
                        <p><strong>Monthly Payment:</strong> ₱<?php echo number_format($loan['monthly_payment'], 2); ?></p>
                        <p><strong>Total Amount Due:</strong> ₱<?php echo number_format($loan['principal_amount'] + $loan['interest_amount'], 2); ?></p>
                    </div>
                    
                    <div>
                        <p><strong>Loan Date:</strong> <?php echo date('F d, Y', strtotime($loan['loan_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($loan['due_date'])); ?></p>
                        <p><strong>Days Remaining:</strong> <?php echo max(0, ceil((strtotime($loan['due_date']) - time()) / (60 * 60 * 24))); ?> days</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h3>Payment History (<?php echo count($payments); ?> payments)</h3>
            </div>
            
            <?php if (!empty($payments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Amount Paid</th>
                            <th>Payment Method</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                                <td>₱<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body">
                    <p style="color: var(--gray); text-align: center;">No payments recorded yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Amortization Schedule -->
        <div class="card">
            <div class="card-header">
                <h3>Amortization Schedule</h3>
            </div>
            
            <?php if (!empty($schedule)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Due Date</th>
                            <th>Monthly Payment</th>
                            <th>Remaining Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $month): ?>
                            <tr>
                                <td><?php echo $month['month']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($month['due_date'])); ?></td>
                                <td>₱<?php echo number_format($month['monthly_payment'], 2); ?></td>
                                <td>₱<?php echo number_format($month['remaining_balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
</html>
