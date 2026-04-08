<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Registration.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

// Get pending registrations
$limit = 20;
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$pendingRegs = Registration::getPendingRegistrations($limit, $offset);
$totalPending = Registration::getPendingCount();
$totalPages = ceil($totalPending / $limit);

// Handle approval/rejection
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($userId > 0 && in_array($action, [STATUS_APPROVED, STATUS_REJECTED])) {
        $result = executeQuery("UPDATE users SET status = ? WHERE id = ? AND status = ?", "sss", [$action, $userId, STATUS_PENDING]);
        if ($result) {
            // Log action
            executeQuery(
                "INSERT INTO admin_logs (admin_id, action, target_user_id, ip_address) VALUES (?, ?, ?, ?)",
                "isss",
                [$admin['id'], "User " . strtolower($action), $userId, $_SERVER['REMOTE_ADDR']]
            );
            $message = "User " . strtolower($action) . " successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Registrations - <?php echo APP_NAME; ?></title>
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
        <h1>Pending Registrations</h1>
        <p>Review and approve/reject user registrations</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Pending Registrations (<?php echo $totalPending; ?> total)</h3>
            </div>
            
            <?php if (!empty($pendingRegs)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                <td>#<?php echo $reg['id']; ?></td>
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="btn btn-<?php echo ($i === $page ? 'primary' : 'secondary'); ?> btn-sm"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No pending registrations</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
