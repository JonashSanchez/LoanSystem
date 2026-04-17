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
        .form-container {
            padding: 40px 44px;
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
    </style>
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
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="#" class="logo"><?php echo APP_NAME; ?></a>
            <div class="nav-right">
                <span style="color: white;">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1>Apply for Loan</h1>
        <p>Maximum loan amount: ₱<?php echo number_format(MAX_LOAN_AMOUNT, 2); ?> | Interest rate: <?php echo LOAN_INTEREST_RATE; ?>%</p>
    </div>
    
    <div class="container">
        <a href="dashboard.php" style="margin-bottom: 1.5rem; display: inline-block;">← Back to Dashboard</a>
        
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
</body>
</html>
