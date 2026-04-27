<?php
require_once('../config/db.php');
require_once('../config/constants.php');
require_once('../includes/Auth.php');
require_once('../includes/Loan.php');
require_once('../includes/Validator.php');

Auth::requireLogin();
$user = Auth::getCurrentUser();

// Prevent admins from applying for loans
if ($user['is_admin']) {
    header('Location: /LoaningSystem/admin/dashboard.php');
    exit;
}

// Check if user can apply for loans
if ($user['status'] !== STATUS_APPROVED) {
    header('Location: /LoaningSystem/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle loan application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $term = intval($_POST['term'] ?? 0);
    
    // Validate
    $amountValidator = Validator::validateLoanAmount($amount);
    if ($amountValidator !== true) {
        $error = $amountValidator;
    } else {
        $termValidator = Validator::validateLoanTerm($term);
        if ($termValidator !== true) {
            $error = $termValidator;
        } else {
            $result = Loan::createLoan($user['id'], $amount, $term);
            if ($result['success']) {
                $success = $result['message'];
                $loanDetails = $result['details'];
                // Calculate due date from loan term (months from today)
                $dueDate = date('Y-m-d', strtotime("+" . $term . " months"));
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Loan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/LoaningSystem/public/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 50%, var(--secondary) 100%);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }
        
        .user-sidebar {
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
        }
        
        .user-logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            text-decoration: none;
            color: white;
        }
        
        .user-logo-icon {
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
        
        .user-logo-text {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .user-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .user-nav-item {
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
        
        .user-nav-item:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .user-main {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .user-nav-top {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 0.5px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .user-nav-top .container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }
        
        .user-content {
            flex: 1;
            padding: 40px;
            overflow-x: hidden;
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 40px 44px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-container h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 24px;
        }
        
        .form-container .form-section h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-header-section {
            margin-bottom: 40px;
        }
        
        .user-header-section h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        
        .user-header-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
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
        
        @media (max-width: 768px) {
            .user-sidebar {
                width: 240px;
            }
            .user-main {
                margin-left: 240px;
            }
            .user-content {
                padding: 20px;
            }
            .stat-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    <script>
        function calculateLoan() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const term = parseInt(document.getElementById('term').value) || 0;
            const interestRate = 3.00;
            
            if (amount > 0 && term > 0) {
                const interest = amount * (interestRate / 100);
                const netAmount = amount - interest;
                const totalDue = amount + interest;
                const monthlyPayment = totalDue / term;
                
                document.getElementById('interestAmount').textContent = interest.toFixed(2);
                document.getElementById('netAmountReceived').textContent = netAmount.toFixed(2);
                document.getElementById('totalDue').textContent = totalDue.toFixed(2);
                document.getElementById('monthlyPayment').textContent = monthlyPayment.toFixed(2);
                
                document.getElementById('calculationsDiv').style.display = 'block';
            } else {
                document.getElementById('calculationsDiv').style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <!-- Sidebar -->
    <div class="user-sidebar">
        <a href="#" class="user-logo-section">
            <div class="user-logo-icon">💰</div>
            <div class="user-logo-text"><?php echo APP_NAME; ?></div>
        </a>
        
        <div class="user-nav">
            <a href="dashboard.php" class="user-nav-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            <a href="apply-loan.php" class="user-nav-item" style="background: rgba(102, 126, 234, 0.2); border-color: rgba(102, 126, 234, 0.5); color: white;">
                <span>💵</span>
                <span>Apply for Loan</span>
            </a>
            <a href="loan-details.php" class="user-nav-item">
                <span>📋</span>
                <span>My Loans</span>
            </a>
            <?php if ($user['account_type'] === 'Premium'): ?>
            <a href="premium-account.php" class="user-nav-item">
                <span>💎</span>
                <span>Premium Account</span>
            </a>
            <?php endif; ?>
            <a href="register.php" class="user-nav-item">
                <span>⚙️</span>
                <span>Account Settings</span>
            </a>
            <a href="logout.php" class="user-nav-item" style="margin-top: auto;">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="user-main">
        <!-- Top Navigation -->
        <div class="user-nav-top">
            <div class="container" style="display: flex; justify-content: flex-end;">
                <span style="color: #666; font-size: 14px;">Welcome, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
            </div>
        </div>
        
        <!-- Content -->
        <div class="user-content">
            <div class="user-header-section">
                <h1>Apply for Loan</h1>
                <p>Loan amount: ₱1 - ₱<?php echo number_format(MAX_LOAN_AMOUNT, 2); ?> | Interest rate: <?php echo LOAN_INTEREST_RATE; ?>%</p>
            </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Loan Application Successful!</strong><br>
                    <?php echo htmlspecialchars($success); ?><br><br>
                    <strong>Loan Details:</strong><br>
                    Principal Amount: ₱<?php echo number_format($loanDetails['principal'], 2); ?><br>
                    Interest Amount (3%): ₱<?php echo number_format($loanDetails['interest_amount'], 2); ?><br>
                    Net Amount Received: ₱<?php echo number_format($loanDetails['net_amount_received'], 2); ?><br>
                    Total Amount Due: ₱<?php echo number_format($loanDetails['total_amount_due'], 2); ?><br>
                    Monthly Payment: ₱<?php echo number_format($loanDetails['monthly_payment'], 2); ?><br>
                    Loan Term: <?php echo $loanDetails['loan_term']; ?> months<br>
                    Due Date: <?php echo date('F d, Y', strtotime($dueDate)); ?><br><br>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" onsubmit="return validateForm();">
                    <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Loan Application Form</h2>
                    
                    <div class="form-section">
                        <h3>Loan Details</h3>
                        
                        <div class="form-group">
                            <label for="amount">Loan Amount <span class="required">*</span></label>
                            <input type="number" id="amount" name="amount" min="1" max="<?php echo MAX_LOAN_AMOUNT; ?>" step="0.01" required onchange="calculateLoan()" onkeyup="calculateLoan()">
                            <div class="help-text">Minimum: ₱1.00 | Maximum: ₱<?php echo number_format(MAX_LOAN_AMOUNT, 2); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="term">Loan Term <span class="required">*</span></label>
                            <select id="term" name="term" required onchange="calculateLoan()">
                                <option value="">-- Select Loan Term --</option>
                                <option value="1">1 Month</option>
                                <option value="3">3 Months</option>
                                <option value="6">6 Months</option>
                                <option value="12">12 Months</option>
                            </select>
                            <div class="help-text">Select the duration you want to repay your loan</div>
                        </div>
                    </div>
                    
                    <!-- Loan Calculations Display -->
                    <div id="calculationsDiv" style="display: none;" class="form-section">
                        <h3>Loan Calculation Preview</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p><strong>Principal Amount:</strong> <span id="principalDisplay">₱0.00</span></p>
                                <p><strong>Interest (3%):</strong> <span id="interestAmount">₱0.00</span></p>
                                <p><strong>Total Amount Due:</strong> <span id="totalDue">₱0.00</span></p>
                            </div>
                            <div>
                                <p><strong>Net Amount You'll Receive:</strong> <span id="netAmountReceived">₱0.00</span></p>
                                <p><strong>Monthly Payment:</strong> <span id="monthlyPayment">₱0.00</span></p>
                                <p style="color: var(--warning); margin-top: 1rem;"><strong>Note:</strong> Interest is deducted upfront</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Important Information -->
                    <div class="alert alert-info">
                        <strong>Important Information:</strong><br>
                        ✓ Interest rate is fixed at 3% for the whole borrowed amount<br>
                        ✓ The 3% interest will be deducted immediately from your borrowed amount<br>
                        ✓ You will receive: Principal Amount - (Principal × 3%)<br>
                        ✓ You must repay: Principal Amount + (Principal × 3%)
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Submit Loan Application</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                
                <script>
                    function validateForm() {
                        const amount = parseFloat(document.getElementById('amount').value);
                        const term = parseInt(document.getElementById('term').value);
                        
                        if (!amount || !term) {
                            alert('Please fill in all required fields');
                            return false;
                        }
                        
                        if (amount <= 0 || amount > <?php echo MAX_LOAN_AMOUNT; ?>) {
                            alert('Loan amount must be between ₱1 and ₱<?php echo number_format(MAX_LOAN_AMOUNT, 2); ?>');
                            return false;
                        }
                        
                        if (![1, 3, 6, 12].includes(term)) {
                            alert('Invalid loan term');
                            return false;
                        }
                        
                        return confirm('Are you sure you want to apply for this loan?');
                    }
                </script>
            <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
