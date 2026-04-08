<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Registration.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

global $mysqli;

// Get statistics - using direct queries
$totalUsers = 0;
$pendingUsers = 0;
$approvedUsers = 0;
$totalLoans = 0;
$activeLoans = 0;
$totalBorrowed = 0;
$totalRepaid = 0;
$basicCount = 0;
$premiumCount = 0;

// Total users
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE");
if ($result && $row = $result->fetch_assoc()) {
    $totalUsers = $row['count'];
}

// Pending users
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE status = ?");
$stmt->bind_param("s", $status);
$status = STATUS_PENDING;
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingUsers = $row['count'];
}
$stmt->close();

// Approved users
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE status = ?");
$stmt->bind_param("s", $status);
$status = STATUS_APPROVED;
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $approvedUsers = $row['count'];
}
$stmt->close();

// Total loans
$result = $mysqli->query("SELECT COUNT(*) as count FROM loans");
if ($result && $row = $result->fetch_assoc()) {
    $totalLoans = $row['count'];
}

// Active loans
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM loans WHERE status = ?");
$stmt->bind_param("s", $status);
$status = LOAN_ACTIVE;
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $activeLoans = $row['count'];
}
$stmt->close();

// Total borrowed
$result = $mysqli->query("SELECT SUM(principal_amount) as total FROM loans");
if ($result && $row = $result->fetch_assoc()) {
    $totalBorrowed = $row['total'] ?? 0;
}

// Total repaid
$stmt = $mysqli->prepare("SELECT SUM(CASE WHEN status = ? THEN principal_amount + interest_amount ELSE 0 END) as total FROM loans");
$stmt->bind_param("s", $status);
$status = LOAN_PAID;
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalRepaid = $row['total'] ?? 0;
}
$stmt->close();

// Basic accounts
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE account_type = 'Basic' AND is_admin = FALSE");
if ($result && $row = $result->fetch_assoc()) {
    $basicCount = $row['count'];
}

// Premium accounts
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE account_type = 'Premium' AND is_admin = FALSE");
if ($result && $row = $result->fetch_assoc()) {
    $premiumCount = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
    <style>
        .admin-sidebar-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .admin-nav-btn {
            background: white;
            border: 2px solid var(--secondary);
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            color: var(--secondary);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .admin-nav-btn:hover {
            background: var(--secondary);
            color: white;
        }
    </style>
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
        <h1>Admin Dashboard</h1>
        <p>System overview and management</p>
    </div>
    
    <div class="container">
        <!-- Quick Navigation -->
        <div class="admin-sidebar-nav">
            <a href="registrations.php" class="admin-nav-btn">📋 Pending Registrations</a>
            <a href="users.php" class="admin-nav-btn">👥 User Management</a>
            <a href="loans.php" class="admin-nav-btn">💰 Loan Management</a>
            <a href="blocked-emails.php" class="admin-nav-btn">🚫 Blocked Emails</a>
            <a href="reports.php" class="admin-nav-btn">📊 Reports</a>
            <a href="logs.php" class="admin-nav-btn">📝 Activity Logs</a>
        </div>
        
        <!-- Statistics -->
        <h2 style="color: var(--primary); margin-bottom: 1.5rem;">System Statistics</h2>
        
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Total Users</div>
                <div class="stat-number" style="color: var(--primary);"><?php echo $totalUsers; ?></div>
            </div>
            
            <div class="stat-box success">
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-number" style="color: var(--success);"><?php echo $pendingUsers; ?></div>
            </div>
            
            <div class="stat-box warning">
                <div class="stat-label">Approved Users</div>
                <div class="stat-number" style="color: var(--warning);"><?php echo $approvedUsers; ?></div>
            </div>
            
            <div class="stat-box danger">
                <div class="stat-label">Active Loans</div>
                <div class="stat-number" style="color: var(--danger);"><?php echo $activeLoans; ?></div>
            </div>
        </div>
        
        <!-- Account Types -->
        <h2 style="color: var(--primary); margin-top: 3rem; margin-bottom: 1.5rem;">User Accounts</h2>
        
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Basic Accounts</div>
                <div class="stat-number"><?php echo $basicCount; ?></div>
                <p style="color: var(--gray); margin-top: 0.5rem;">Unlimited slots</p>
            </div>
            
            <div class="stat-box">
                <div class="stat-label">Premium Accounts</div>
                <div class="stat-number"><?php echo $premiumCount; ?>/<?php echo MAX_PREMIUM_MEMBERS; ?></div>
                <p style="color: var(--gray); margin-top: 0.5rem;"><?php echo (MAX_PREMIUM_MEMBERS - $premiumCount); ?> slots remaining</p>
            </div>
        </div>
        
        <!-- Loan Statistics -->
        <h2 style="color: var(--primary); margin-top: 3rem; margin-bottom: 1.5rem;">Loan Statistics</h2>
        
        <div class="card-grid">
            <div class="stat-box">
                <div class="stat-label">Total Loans</div>
                <div class="stat-number"><?php echo $totalLoans; ?></div>
            </div>
            
            <div class="stat-box success">
                <div class="stat-label">Total Borrowed</div>
                <div class="stat-number">₱<?php echo number_format($totalBorrowed, 0); ?></div>
            </div>
            
            <div class="stat-box warning">
                <div class="stat-label">Total Repaid</div>
                <div class="stat-number">₱<?php echo number_format($totalRepaid, 0); ?></div>
            </div>
            
            <div class="stat-box danger">
                <div class="stat-label">Outstanding Balance</div>
                <div class="stat-number">₱<?php echo number_format($totalBorrowed - $totalRepaid, 0); ?></div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <h2 style="color: var(--primary); margin-top: 3rem; margin-bottom: 1.5rem;">Recent Pending Registrations</h2>
        
        <?php
        $pendingRegs = Registration::getPendingRegistrations(5, 0);
        if (!empty($pendingRegs)):
        ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Account Type</th>
                            <th>Applied On</th>
                            <th>Documents</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRegs as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['username']); ?></td>
                                <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($reg['account_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
                                <td><?php echo $reg['documents_count']; ?>/3</td>
                                <td>
                                    <a href="review-registration.php?id=<?php echo $reg['id']; ?>" class="btn btn-primary btn-sm">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: right; margin-top: 1rem;">
                    <a href="registrations.php" class="btn btn-secondary">View All Pending Registrations</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No pending registrations</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
