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
        <h1>Loan #<?php echo $loan['id']; ?> Details</h1>
        <p>View your loan information and payments</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
        <!-- Loan Status -->
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Loan Status</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-<?php echo strtolower($loan['status']); ?>">
                        <?php echo $loan['status']; ?>
                    </span>
                </div>
            </div>
            
            <div class="stat-box success">
                <div class="stat-label">Amount Borrowed</div>
                <div class="stat-number">₱<?php echo number_format($loan['principal_amount'], 2); ?></div>
            </div>
            
            <div class="stat-box warning">
                <div class="stat-label">Total Repaid</div>
                <div class="stat-number">₱<?php echo number_format($totalPaid, 2); ?></div>
            </div>
            
            <div class="stat-box danger">
                <div class="stat-label">Remaining Balance</div>
                <div class="stat-number">₱<?php echo number_format(max(0, $remainingBalance), 2); ?></div>
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
