<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Validator.php');

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    $redirect = Auth::isAdmin() ? '/LoaningSystem/admin/dashboard.php' : '/LoaningSystem/pages/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        $result = Auth::login($username, $password);
        if ($result['success']) {
            $redirect = Auth::isAdmin() ? '/LoaningSystem/admin/dashboard.php' : '/LoaningSystem/pages/dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container" style="margin-top: 5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="color: white; font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo APP_NAME; ?></h1>
                <p style="color: rgba(255,255,255,0.8);">Secure Loan Management System</p>
            </div>
            
            <form method="POST" style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem; text-align: center;">User Login</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username or Email <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required style="padding-right: 40px;">
                        <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; color: #7f8c8d;">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
                
                <script>
                    const toggleBtn = document.getElementById('togglePassword');
                    const passwordField = document.getElementById('password');
                    
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (passwordField.type === 'password') {
                            passwordField.type = 'text';
                            toggleBtn.textContent = '🙈';
                        } else {
                            passwordField.type = 'password';
                            toggleBtn.textContent = '👁️';
                        }
                    });
                </script>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <p style="color: var(--gray);">Don't have an account? <a href="register.php" style="color: var(--secondary); font-weight: 600;">Register here</a></p>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 2rem; color: white; font-size: 0.9rem;">
                <p>Demo Credentials:</p>
                <p>Username: admin | Password: Admin@123</p>
            </div>
        </div>
    </div>
</body>
</html>
