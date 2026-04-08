<?php
/**
 * Database Update Script - Add Premium Features
 * This script creates the new tables for savings and money back features
 */

$mysqli = new mysqli('localhost', 'root', '', 'loaning_system');

if ($mysqli->connect_error) {
    die("Database Error: " . $mysqli->connect_error);
}

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the update SQL
    $sql_statements = [
        // Create Savings table
        "CREATE TABLE IF NOT EXISTS savings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
            transaction_type ENUM('Deposit', 'Withdrawal') NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Create Money Back table
        "CREATE TABLE IF NOT EXISTS money_back (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            reference_type VARCHAR(50) COMMENT 'loan_referral, savings_interest, etc',
            reference_id INT,
            status ENUM('Pending', 'Credited') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            credited_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Modify users table to add 'Disabled' status
        "ALTER TABLE users MODIFY status ENUM('Pending', 'Approved', 'Rejected', 'Suspended', 'Disabled') DEFAULT 'Pending'"
    ];
    
    $success_count = 0;
    $errors = [];
    
    foreach ($sql_statements as $stmt_sql) {
        if ($mysqli->query($stmt_sql)) {
            $success_count++;
        } else {
            $errors[] = $mysqli->error;
        }
    }
    
    if ($success_count === count($sql_statements)) {
        $message = "✅ Database updated successfully! All Premium features tables created.";
        $status = 'success';
    } else {
        $message = "⚠️ Some updates failed:<br>" . implode("<br>", $errors);
        $status = 'warning';
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Features Update</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-color: #27ae60;
        }
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border-color: #f39c12;
        }
        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #3498db;
        }
        .feature-list {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .feature-list li {
            list-style: none;
            padding: 8px 0;
            color: #333;
        }
        .feature-list li:before {
            content: "✓ ";
            color: #27ae60;
            font-weight: bold;
            margin-right: 8px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
        }
        button:hover {
            background: #5568d3;
        }
        .next-steps {
            margin-top: 30px;
            padding: 20px;
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .next-steps h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .next-steps li {
            list-style: none;
            padding: 5px 0;
            color: #333;
        }
        .next-steps li:before {
            content: "→ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💎 Premium Features Update</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $status; ?>">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="alert info">
                ℹ️ This will create new database tables for Premium account features:
                <ul class="feature-list">
                    <li>Savings account management</li>
                    <li>Money back rewards tracking</li>
                    <li>Account disable on missed payments</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit">Install Premium Features ✓</button>
            </form>
        <?php endif; ?>
        
        <?php if ($status === 'success'): ?>
            <div class="next-steps">
                <h3>Next Steps</h3>
                <ul>
                    <li>Test Premium account registration</li>
                    <li>Run <code>cron-payment-monitor.php</code> daily to check for missed payments</li>
                    <li>Premium users can now access savings and money back features</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
