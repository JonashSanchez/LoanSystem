<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Loan.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get loan ID from URL
$loanId = intval($_GET['id'] ?? 0);
if ($loanId <= 0) {
    header('Location: /LoaningSystem/admin/loans.php');
    exit;
}

// Get loan details with user info
$loan = fetchRow(
    "SELECT l.*, u.id as user_id, u.username, u.full_name, u.email FROM loans l JOIN users u ON l.user_id = u.id WHERE l.id = ?",
    "i",
    [$loanId]
);

if (!$loan) {
    header('HTTP/1.0 404 Not Found');
    exit('Loan not found');
}

// Get payments
$payments = Loan::getLoanPayments($loanId);
$totalPaid = array_sum(array_map(fn($p) => $p['amount_paid'], $payments));

// Get schedule
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
            <a href="#" class="logo"><?php echo APP_NAME; ?> - ADMIN</a>
            <div class="nav-right">
                <span style="color: white;">Admin: <?php echo htmlspecialchars($admin['full_name']); ?></span>
                <a href="/LoaningSystem/pages/logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1>Loan #<?php echo $loan['id']; ?> Details</h1>
        <p>Borrower: <?php echo htmlspecialchars($loan['full_name']); ?></p>
    </div>
    
    <div class="container">
        <a href="loans.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Loans</a>
        
        <!-- Quick Statistics -->
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Principal Amount</div>
                <div class="stat-number">₱<?php echo number_format($loan['principal_amount'], 0); ?></div>
            </div>
            
            <div class="stat-box success">
                <div class="stat-label">Total Repaid</div>
                <div class="stat-number">₱<?php echo number_format($totalPaid, 0); ?></div>
            </div>
            
            <div class="stat-box warning">
                <div class="stat-label">Remaining Balance</div>
                <div class="stat-number">₱<?php echo number_format(max(0, ($loan['principal_amount'] + $loan['interest_amount']) - $totalPaid), 0); ?></div>
            </div>
            
            <div class="stat-box danger">
                <div class="stat-label">Loan Status</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-<?php echo strtolower($loan['status']); ?>"><?php echo $loan['status']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Loan Overview -->
        <div class="card">
            <div class="card-header">
                <h3>Loan Overview</h3>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                    <div>
                        <p><strong>Principal Amount:</strong> ₱<?php echo number_format($loan['principal_amount'], 2); ?></p>
                        <p><strong>Interest (3%):</strong> ₱<?php echo number_format($loan['interest_amount'], 2); ?></p>
                        <p><strong>Total Amount Due:</strong> ₱<?php echo number_format($loan['principal_amount'] + $loan['interest_amount'], 2); ?></p>
                    </div>
                    
                    <div>
                        <p><strong>Loan Term:</strong> <?php echo $loan['loan_term']; ?> months</p>
                        <p><strong>Monthly Payment:</strong> ₱<?php echo number_format($loan['monthly_payment'], 2); ?></p>
                        <p><strong>Net Amount Received:</strong> ₱<?php echo number_format($loan['net_amount_received'], 2); ?></p>
                    </div>
                    
                    <div>
                        <p><strong>Loan Date:</strong> <?php echo date('M d, Y', strtotime($loan['loan_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($loan['due_date'])); ?></p>
                        <p><strong>Days Remaining:</strong> <?php echo max(0, ceil((strtotime($loan['due_date']) - time()) / (60 * 60 * 24))); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Borrower Information -->
        <div class="card">
            <div class="card-header">
                <h3>Borrower Information</h3>
            </div>
            
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($loan['full_name']); ?></p>
                        <p><strong>Username:</strong> @<?php echo htmlspecialchars($loan['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($loan['email']); ?></p>
                    </div>
                    
                    <div>
                        <a href="user-details.php?id=<?php echo $loan['user_id']; ?>" class="btn btn-primary">View Full User Profile</a>
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
                            <th>Amount</th>
                            <th>Method</th>
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
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No payments recorded</p>
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
