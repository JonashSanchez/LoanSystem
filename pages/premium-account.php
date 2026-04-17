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
        .page-header h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .card-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        .stat-box {
            border-top: 4px solid var(--secondary);
        }
        
        .card-header {
            background: var(--lighter);
        }
    </style>
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
        <h1>💎 Premium Account Management</h1>
        <p>Manage your savings and money back rewards</p>
    </div>
    
    <div class="container">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Account Overview -->
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Total Savings</div>
                <div class="stat-number" style="color: #27ae60;">₱<?php echo number_format($totalSavings, 2); ?></div>
            </div>
            
            <div class="stat-box">
                <div class="stat-label">Money Back Earned</div>
                <div class="stat-number" style="color: #f39c12;">₱<?php echo number_format($totalMoneyBack, 2); ?></div>
            </div>
            
            <div class="stat-box">
                <div class="stat-label">Pending Rewards</div>
                <div class="stat-number" style="color: #e74c3c;">₱<?php echo number_format($pendingMoneyBack, 2); ?></div>
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
</body>
</html>
