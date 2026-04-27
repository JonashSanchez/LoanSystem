<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Savings.php');
require_once('../includes/MoneyBack.php');

Auth::requireLogin();
$user = Auth::getCurrentUser();

// Only allow Premium accounts
if ($user['account_type'] !== 'Premium') {
    header('Location: /LoaningSystem/pages/dashboard.php');
    exit;
}

// Redirect admins to admin dashboard
if ($user['is_admin']) {
    header('Location: /LoaningSystem/admin/dashboard.php');
    exit;
}

// Check if account is disabled
if ($user['status'] === 'Disabled') {
    session_destroy();
    header('Location: /LoaningSystem/pages/login.php?error=Account+disabled');
    exit;
}

$message = '';
$error = '';

// Handle credit money back
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'credit_money_back') {
        $result = MoneyBack::creditMoneyBack($user['id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get account data
$totalSavings = Savings::getTotalSavings($user['id']);
$totalMoneyBack = MoneyBack::getTotalMoneyBack($user['id']);
$pendingMoneyBack = MoneyBack::getPendingMoneyBack($user['id']);
$savingsHistory = Savings::getSavingsHistory($user['id'], 10);
$moneyBackHistory = MoneyBack::getMoneyBackHistory($user['id'], 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Account - <?php echo APP_NAME; ?></title>
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
            <a href="premium-account.php" class="user-nav-item" style="background: rgba(102, 126, 234, 0.2); border-color: rgba(102, 126, 234, 0.5); color: white;">
                <span>💎</span>
                <span>Premium Account</span>
            </a>
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
                <h1>💎 Premium Account</h1>
                <p>Manage your savings and money back rewards</p>
            </div>
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Account Overview -->
        <h2 class="section-title">Account Overview</h2>
        <div class="stat-cards cards-2col">
            <div class="stat-card">
                <div class="stat-label">Total Savings</div>
                <div class="stat-number" style="color: #27ae60;">₱<?php echo number_format($totalSavings, 2); ?></div>
                <div class="stat-subtitle">Accumulated savings</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Money Back Earned</div>
                <div class="stat-number" style="color: #f39c12;">₱<?php echo number_format($totalMoneyBack, 2); ?></div>
                <div class="stat-subtitle">Total rewards</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Pending Rewards</div>
                <div class="stat-number" style="color: #e74c3c;">₱<?php echo number_format($pendingMoneyBack, 2); ?></div>
                <div class="stat-subtitle">To be credited</div>
            </div>
        </div>
        
        <!-- Credit Money Back Button -->
        <?php if ($pendingMoneyBack > 0): ?>
            <div style="text-align: center; margin: 2rem 0;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="credit_money_back">
                    <button type="submit" class="btn btn-success">Credit ₱<?php echo number_format($pendingMoneyBack, 2); ?> to Savings</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Savings History -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3>Savings History</h3>
            </div>
            
            <?php if (!empty($savingsHistory)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savingsHistory as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 3px; background: <?php echo $transaction['transaction_type'] === 'Deposit' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $transaction['transaction_type'] === 'Deposit' ? '#155724' : '#721c24'; ?>;">
                                        <?php echo $transaction['transaction_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: <?php echo $transaction['transaction_type'] === 'Deposit' ? '#27ae60' : '#e74c3c'; ?>;">
                                        <?php echo ($transaction['transaction_type'] === 'Deposit' ? '+' : '-') . '₱' . number_format($transaction['amount'], 2); ?>
                                    </strong>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body">
                    <p style="color: var(--gray); text-align: center;">No savings transactions yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Money Back History -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3>Money Back Rewards History</h3>
            </div>
            
            <?php if (!empty($moneyBackHistory)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moneyBackHistory as $reward): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($reward['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($reward['reference_type']); ?></td>
                                <td><strong style="color: #f39c12;">₱<?php echo number_format($reward['amount'], 2); ?></strong></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 3px; background: <?php echo $reward['status'] === 'Credited' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $reward['status'] === 'Credited' ? '#155724' : '#856404'; ?>;">
                                        <?php echo $reward['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body">
                    <p style="color: var(--gray); text-align: center;">No money back rewards yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Back Button -->
        <div style="text-align: center; margin: 2rem 0;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        </div>
    </div>
</body>
</html>
