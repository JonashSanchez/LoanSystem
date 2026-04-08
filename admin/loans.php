<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get loans
$limit = 20;
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? '';

if ($status && in_array($status, [LOAN_ACTIVE, LOAN_PAID, LOAN_DEFAULTED, LOAN_CANCELLED])) {
    $loans = fetchAll(
        "SELECT l.*, u.username, u.full_name, u.email FROM loans l JOIN users u ON l.user_id = u.id WHERE l.status = ? ORDER BY l.loan_date DESC LIMIT ? OFFSET ?",
        "sii",
        [$status, $limit, $offset]
    );
    $total = fetchRow("SELECT COUNT(*) as count FROM loans WHERE status = ?", "s", [$status])['count'];
} else {
    $loans = fetchAll(
        "SELECT l.*, u.username, u.full_name, u.email FROM loans l JOIN users u ON l.user_id = u.id ORDER BY l.loan_date DESC LIMIT ? OFFSET ?",
        "ii",
        [$limit, $offset]
    );
    $total = fetchRow("SELECT COUNT(*) as count FROM loans", "", [])['count'];
}

$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - <?php echo APP_NAME; ?></title>
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
        <h1>Loan Management</h1>
        <p>View and monitor all loans in the system</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="?status=" class="btn <?php echo !$status ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All Loans</a>
                    <a href="?status=<?php echo LOAN_ACTIVE; ?>" class="btn <?php echo $status === LOAN_ACTIVE ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Active</a>
                    <a href="?status=<?php echo LOAN_PAID; ?>" class="btn <?php echo $status === LOAN_PAID ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Paid</a>
                    <a href="?status=<?php echo LOAN_DEFAULTED; ?>" class="btn <?php echo $status === LOAN_DEFAULTED ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Defaulted</a>
                    <a href="?status=<?php echo LOAN_CANCELLED; ?>" class="btn <?php echo $status === LOAN_CANCELLED ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Cancelled</a>
                </div>
            </div>
        </div>
        
        <!-- Loans Table -->
        <div class="card">
            <div class="card-header">
                <h3>Loans (<?php echo $total; ?> total)</h3>
            </div>
            
            <?php if (!empty($loans)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Borrower</th>
                            <th>Principal Amount</th>
                            <th>Term</th>
                            <th>Monthly Payment</th>
                            <th>Status</th>
                            <th>Loan Date</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>#<?php echo $loan['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($loan['full_name']); ?></strong><br>
                                    <small style="color: var(--gray);">@<?php echo htmlspecialchars($loan['username']); ?></small>
                                </td>
                                <td>₱<?php echo number_format($loan['principal_amount'], 2); ?></td>
                                <td><?php echo $loan['loan_term']; ?> mo</td>
                                <td>₱<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($loan['status']); ?>"><?php echo $loan['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($loan['loan_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($loan['due_date'])); ?></td>
                                <td>
                                    <a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="text-align: center; margin-top: 1.5rem; padding-bottom: 1rem;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>" class="btn btn-<?php echo ($i === $page ? 'primary' : 'secondary'); ?> btn-sm"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No loans found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
