<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get users
$limit = 20;
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? '';

if ($status && in_array($status, [STATUS_APPROVED, STATUS_REJECTED, STATUS_SUSPENDED, STATUS_PENDING])) {
    $users = fetchAll("SELECT * FROM users WHERE is_admin = FALSE AND status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?", "sii", [$status, $limit, $offset]);
    $total = fetchRow("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE AND status = ?", "s", [$status])['count'];
} else {
    $users = fetchAll("SELECT * FROM users WHERE is_admin = FALSE ORDER BY created_at DESC LIMIT ? OFFSET ?", "ii", [$limit, $offset]);
    $total = fetchRow("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE", "", [])['count'];
}

$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
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
        <h1>User Management</h1>
        <p>View and manage user accounts</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="?status=" class="btn <?php echo !$status ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All Users</a>
                    <a href="?status=<?php echo STATUS_PENDING; ?>" class="btn <?php echo $status === STATUS_PENDING ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Pending</a>
                    <a href="?status=<?php echo STATUS_APPROVED; ?>" class="btn <?php echo $status === STATUS_APPROVED ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Approved</a>
                    <a href="?status=<?php echo STATUS_REJECTED; ?>" class="btn <?php echo $status === STATUS_REJECTED ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Rejected</a>
                    <a href="?status=<?php echo STATUS_SUSPENDED; ?>" class="btn <?php echo $status === STATUS_SUSPENDED ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Suspended</a>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3>Users (<?php echo $total; ?> total)</h3>
            </div>
            
            <?php if (!empty($users)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Account Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['account_type']); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($u['status']); ?>"><?php echo $u['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <a href="user-details.php?id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm">View</a>
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
                    <p style="color: var(--gray);">No users found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
