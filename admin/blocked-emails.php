<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');

Auth::requireAdmin();
$admin = Auth::getCurrentUser();

$message = '';
$error = '';

// Handle add blocked email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $email = trim($_POST['email'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = executeQuery("INSERT INTO blocked_emails (email, reason) VALUES (?, ?)", "ss", [$email, $reason]);
            if ($result) {
                $message = "Email blocked successfully";
            } else {
                $error = "Email already blocked or database error";
            }
        } else {
            $error = "Invalid email address";
        }
    } elseif ($_POST['action'] === 'remove') {
        $blockedId = intval($_POST['blocked_id'] ?? 0);
        if ($blockedId > 0) {
            executeQuery("DELETE FROM blocked_emails WHERE id = ?", "i", [$blockedId]);
            $message = "Email unblocked successfully";
        }
    }
}

// Get blocked emails
$blockedEmails = fetchAll("SELECT * FROM blocked_emails ORDER BY created_at DESC", "", []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked Emails - <?php echo APP_NAME; ?></title>
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
        <h1>Blocked Emails Management</h1>
        <p>Manage blocked email addresses</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Add Blocked Email -->
        <div class="card">
            <div class="card-header">
                <h3>Add Blocked Email</h3>
            </div>
            
            <form method="POST" class="card-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason (Optional)</label>
                        <input type="text" id="reason" name="reason">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-danger">Block Email</button>
            </form>
        </div>
        
        <!-- Blocked Emails Table -->
        <div class="card">
            <div class="card-header">
                <h3>Blocked Emails (<?php echo count($blockedEmails); ?> total)</h3>
            </div>
            
            <?php if (!empty($blockedEmails)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Email Address</th>
                            <th>Reason</th>
                            <th>Blocked Since</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedEmails as $emailRow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emailRow['email']); ?></td>
                                <td><?php echo htmlspecialchars($emailRow['reason'] ?? '-'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($emailRow['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="blocked_id" value="<?php echo $emailRow['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Unblock this email?');">Unblock</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-body" style="text-align: center;">
                    <p style="color: var(--gray);">No blocked emails</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
