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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 50%, var(--secondary) 100%);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }
        
        .admin-sidebar {
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
            display: flex;
            flex-direction: column;
        }
        
        .admin-logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
            color: white;
        }
        
        .admin-logo-icon {
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
        
        .admin-logo-text {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .admin-user-info {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 24px;
            font-size: 12px;
        }
        
        .admin-user-label {
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .admin-user-name {
            color: white;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .admin-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }
        
        .admin-nav-item {
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
        
        .admin-nav-item:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .admin-nav-item.active {
            background: var(--secondary);
            color: white;
        }
        
        .admin-main {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .admin-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 0.5px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .admin-nav .container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }
        
        .admin-content {
            flex: 1;
            padding: 40px;
            overflow-x: hidden;
        }
        
        .admin-header-section {
            margin-bottom: 40px;
        }
        
        .admin-header-section h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        
        .admin-header-section p {
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
        
        .table-card {
            background: rgba(255, 255, 255, 0.95);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .table-card table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-card th {
            background: #f8f9fa;
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #eee;
        }
        
        .table-card td {
            padding: 14px 16px;
            border-bottom: 0.5px solid #f0f0f0;
            color: #666;
            font-size: 13px;
        }
        
        .table-card tbody tr:hover {
            background: #fafbfc;
        }
        
        .logout-btn {
            padding: 8px 16px;
            font-size: 12px;
            background: transparent !important;
            border: 1px solid rgba(102, 126, 234, 0.5) !important;
            color: rgba(255, 255, 255, 0.8) !important;
            margin-top: auto;
            width: 100%;
        }
        
        .logout-btn:hover {
            background: rgba(102, 126, 234, 0.2) !important;
            border-color: rgba(102, 126, 234, 0.8) !important;
            color: white !important;
        }
        
        .sidebar-logout-wrapper {
            display: flex;
            gap: 8px;
            margin-top: 24px;
        }
        
        .sidebar-logout-wrapper .logout-btn {
            flex: 1;
            margin-top: 0;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 240px;
            }
            .admin-main {
                margin-left: 240px;
            }
            .admin-content {
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
    <div class="admin-sidebar">
        <a href="#" class="admin-logo-section">
            <div class="admin-logo-icon">💼</div>
            <div class="admin-logo-text"><?php echo APP_NAME; ?></div>
        </a>
        
        <div class="admin-user-info">
            <div class="admin-user-label">Logged in as</div>
            <div class="admin-user-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
        </div>
        
        <div class="admin-sidebar-nav">
            <a href="dashboard.php" class="admin-nav-item active">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="registrations.php" class="admin-nav-item">
                <span>📋</span>
                <span>Pending Registrations</span>
            </a>
            <a href="users.php" class="admin-nav-item">
                <span>👥</span>
                <span>User Management</span>
            </a>
            <a href="loans.php" class="admin-nav-item">
                <span>💰</span>
                <span>Loan Management</span>
            </a>
            <a href="blocked-emails.php" class="admin-nav-item">
                <span>🚫</span>
                <span>Blocked Emails</span>
            </a>
            <a href="reports.php" class="admin-nav-item">
                <span>📈</span>
                <span>Reports</span>
            </a>
            <a href="logs.php" class="admin-nav-item">
                <span>📝</span>
                <span>Activity Logs</span>
            </a>
        </div>
        
        <div class="sidebar-logout-wrapper">
            <a href="/LoaningSystem/pages/logout.php" class="btn btn-danger btn-sm logout-btn">Logout</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Navigation -->
        <div class="admin-nav">
            <div class="container">
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="admin-content">
            <div class="admin-header-section">
                <h1>Admin Dashboard</h1>
                <p>System overview and management</p>
            </div>
            
            <!-- Key Statistics -->
            <h2 class="section-title">Key Statistics</h2>
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-number" style="color: var(--secondary);"><?php echo $totalUsers; ?></div>
                    <div class="stat-subtitle">Registered accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Approvals</div>
                    <div class="stat-number" style="color: var(--warning);"><?php echo $pendingUsers; ?></div>
                    <div class="stat-subtitle">Awaiting review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved Users</div>
                    <div class="stat-number" style="color: var(--success);"><?php echo $approvedUsers; ?></div>
                    <div class="stat-subtitle">Active users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Loans</div>
                    <div class="stat-number" style="color: var(--danger);"><?php echo $activeLoans; ?></div>
                    <div class="stat-subtitle">Currently active</div>
                </div>
            </div>
            
            <!-- Account Types -->
            <h2 class="section-title">Account Types</h2>
            <div class="stat-cards cards-2col">
                <div class="stat-card">
                    <div class="stat-label">Basic Accounts</div>
                    <div class="stat-number"><?php echo $basicCount; ?></div>
                    <div class="stat-subtitle">Unlimited slots</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Premium Accounts</div>
                    <div class="stat-number" style="color: var(--accent);"><?php echo $premiumCount; ?>/<?php echo MAX_PREMIUM_MEMBERS; ?></div>
                    <div class="stat-subtitle"><?php echo (MAX_PREMIUM_MEMBERS - $premiumCount); ?> slots remaining</div>
                </div>
            </div>
            
            <!-- Loan Statistics -->
            <h2 class="section-title">Loan Statistics</h2>
            <div class="stat-cards cards-2col">
                <div class="stat-card">
                    <div class="stat-label">Total Loans</div>
                    <div class="stat-number"><?php echo $totalLoans; ?></div>
                    <div class="stat-subtitle">All-time loans</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Borrowed</div>
                    <div class="stat-number" style="color: var(--success); font-size: 24px;">₱<?php echo number_format($totalBorrowed, 0); ?></div>
                    <div class="stat-subtitle">Total disbursed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Repaid</div>
                    <div class="stat-number" style="color: var(--info); font-size: 24px;">₱<?php echo number_format($totalRepaid, 0); ?></div>
                    <div class="stat-subtitle">Completed payments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Outstanding Balance</div>
                    <div class="stat-number" style="color: var(--danger); font-size: 24px;">₱<?php echo number_format($totalBorrowed - $totalRepaid, 0); ?></div>
                    <div class="stat-subtitle">Still to be repaid</div>
                </div>
            </div>
            
            <!-- Recent Registrations -->
            <h2 class="section-title">Recent Pending Registrations</h2>
            <?php
            $pendingRegs = Registration::getPendingRegistrations(5, 0);
            if (!empty($pendingRegs)):
            ?>
            <div class="table-card">
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
                            <td><strong><?php echo htmlspecialchars($reg['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><span style="background: #f0f2f8; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($reg['account_type']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
                            <td><strong><?php echo $reg['documents_count']; ?>/3</strong></td>
                            <td>
                                <a href="review-registration.php?id=<?php echo $reg['id']; ?>" class="btn btn-primary btn-sm" style="padding: 6px 12px; font-size: 11px;">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: right; margin-bottom: 40px;">
                <a href="registrations.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 13px;">View All Registrations →</a>
            </div>
            <?php else: ?>
            <div class="table-card" style="text-align: center; padding: 40px;">
                <p style="color: #999; font-size: 14px;">✓ No pending registrations</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
