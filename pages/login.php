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
<body style="background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 50%, var(--secondary) 100%); color: var(--dark);">
    <!-- Navigation -->
    <nav style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-bottom: 0.5px solid var(--border); padding: 14px 0; position: sticky; top: 0; z-index: 50;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--dark); font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">
                <div style="width: 36px; height: 36px; background: var(--gradient-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: white;">💰</div>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="#features" style="color: var(--gray); text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s;">Features</a>
                <a href="#features" style="color: var(--gray); text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s;">Why Us</a>
                <a href="#contact" style="color: var(--gray); text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s;">Contact</a>
                <div style="width: 1px; height: 20px; background: var(--border);"></div>
                <a href="#login-form" class="btn btn-primary btn-sm" style="padding: 8px 16px; font-size: 12px;">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div style="padding: 80px 0; text-align: left; color: white;">
        <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
            <!-- Left Side: Hero Content -->
            <div>
                <div style="display: inline-block; background: rgba(102,126,234,0.15); border: 1px solid rgba(102,126,234,0.3); padding: 8px 16px; border-radius: var(--radius-full); margin-bottom: 24px;">
                    <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; color: rgba(255,255,255,0.9); text-transform: uppercase;">✓ Trusted Financial Partner</span>
                </div>
                
                <h1 style="font-size: 52px; font-weight: 800; line-height: 1.1; margin-bottom: 24px; letter-spacing: -1px;">
                    Unlock Your Financial
                    <span style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Potential</span>
                    Today
                </h1>
                
                <p style="font-size: 16px; line-height: 1.8; color: rgba(255,255,255,0.85); margin-bottom: 32px; max-width: 500px;">
                    Get approved for loans in minutes, not days. Simple application, transparent terms, and competitive rates tailored to your needs. Empower your dreams with reliable financial solutions.
                </p>
                
                <div style="display: flex; gap: 16px; margin-bottom: 40px;">
                    <a href="register.php" class="btn btn-primary">Create Account</a>
                    <a href="#login-form" class="btn" style="background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.3); color: white; text-decoration: none; padding: 11px 24px; border-radius: var(--radius-md); font-weight: 700; transition: all 0.2s;">Log In Now</a>
                </div>
                
                <!-- Stats -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #667eea; margin-bottom: 4px;">₱18M+</div>
                        <div style="font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">Funds Distributed</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #764ba2; margin-bottom: 4px;">2,400+</div>
                        <div style="font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">Community Members</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: #f093fb; margin-bottom: 4px;">99%</div>
                        <div style="font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">Success Rate</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Login Form -->
            <div id="login-form">
                <div class="card" style="border: none;">
                    <div class="card-body">
                        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 24px; text-align: center; color: var(--dark);">Welcome Back</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" style="margin-bottom: 20px;">
                                <span style="display: inline-block;">⚠️</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Username Field -->
                            <div class="form-group">
                                <label for="username" style="color: #333; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">
                                    Username or Email <span class="required" style="color: #e74c3c;">*</span>
                                </label>
                                <input type="text" id="username" name="username" placeholder="Enter your username or email" required autofocus style="font-size: 14px;">
                            </div>

                            <!-- Password Field -->
                            <div class="form-group">
                                <label for="password" style="color: #333; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block;">
                                    Password <span class="required" style="color: #e74c3c;">*</span>
                                </label>
                                <div style="position: relative;">
                                    <input type="password" id="password" name="password" placeholder="Enter your password" required style="padding-right: 40px; font-size: 14px;">
                                    <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #666;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-block" style="font-size: 14px; padding: 12px 24px; margin-bottom: 16px;">
                                Sign In
                            </button>
                        </form>

                        <!-- Register Link -->
                        <div style="text-align: center; padding-top: 16px; border-top: 0.5px solid var(--border);">
                            <p style="font-size: 13px; color: var(--gray); margin: 0;">Don't have an account?</p>
                            <a href="register.php" style="display: inline-block; margin-top: 8px; color: var(--secondary); font-weight: 700; text-decoration: none; transition: all 0.2s;">
                                Create Account →
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Trust Badge -->
                <div style="text-align: center; margin-top: 24px; padding: 16px; background: rgba(255,255,255,0.1); border-radius: var(--radius-lg); border: 0.5px solid rgba(255,255,255,0.15);">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.7); margin: 0; font-weight: 600;">
                        Secure & Encrypted
                    </p>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 4px;">Bank-level security for your data</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" style="padding: 80px 0; background: rgba(255,255,255,0.02); border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px; color: white;">
                <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 16px; letter-spacing: -0.5px;">Built for Your Success</h2>
                <p style="font-size: 16px; color: rgba(255,255,255,0.7); max-width: 500px; margin: 0 auto;">Designed with your financial goals in mind—fast, secure, and transparent from start to finish</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                <!-- Feature 1 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center; color: white; transition: all 0.3s;">
                    <div style="font-size: 40px; margin-bottom: 16px;">⚡</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Lightning Fast Approval</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">Say goodbye to lengthy processes. Our streamlined system delivers approval decisions within minutes</p>
                </div>
                
                <!-- Feature 2 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center; color: white; transition: all 0.3s;">
                    <div style="font-size: 40px; margin-bottom: 16px;">💳</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Complete Control</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">Choose from flexible loan amounts and repayment schedules designed to fit your unique lifestyle and financial situation</p>
                </div>
                
                <!-- Feature 3 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center; color: white; transition: all 0.3s;">
                    <div style="font-size: 40px; margin-bottom: 16px;">🛡️</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Privacy Guaranteed</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">Enterprise-grade encryption keeps your personal and financial information completely confidential and secure</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" style="padding: 80px 0; color: white;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px;">
                <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 16px; letter-spacing: -0.5px;">Get in Touch</h2>
                <p style="font-size: 16px; color: rgba(255,255,255,0.7); max-width: 500px; margin: 0 auto;">Have questions? Our support team is here to help you 24/7</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 900px; margin: 0 auto;">
                <!-- Contact Option 1 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 16px;">📧</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: white;">Email Support</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">support@loaningsystem.com</p>
                    <p style="font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 8px;">Response within 2 hours</p>
                </div>
                
                <!-- Contact Option 2 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 16px;">💬</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: white;">Live Chat</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">Available 9 AM - 9 PM (PST)</p>
                    <p style="font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 8px;">Start chat on dashboard</p>
                </div>
                
                <!-- Contact Option 3 -->
                <div style="background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: 32px 24px; text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 16px;">📱</div>
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: white;">Phone Support</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0;">+1 (555) 123-4567</p>
                    <p style="font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 8px;">Monday - Friday, 8 AM - 6 PM</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="padding: 40px 0; border-top: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); text-align: center; font-size: 12px;">
        <div class="container">
            <p style="margin: 0;">© 2026 <?php echo APP_NAME; ?>. All rights reserved. | Secure Loan Management Platform</p>
        </div>
    </div>

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

        // Add hover effect to register link
        document.querySelector('a[href="register.php"]').addEventListener('hover', function() {
            this.style.textDecoration = 'underline';
        });
    </script>
</body>
</html>
