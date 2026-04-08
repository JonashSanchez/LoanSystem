<?php
/**
 * Payment Monitoring CRON Job
 * Disables accounts that have missed loan payments
 * 
 * Schedule this to run daily (e.g., via cPanel or Windows Task Scheduler)
 * Command: php /path/to/cron-payment-monitor.php
 */

require_once(__DIR__ . '/config/db.php');
require_once(__DIR__ . '/config/constants.php');

/**
 * Check for missed payments and disable accounts
 */
function checkAndDisableDelinquentAccounts() {
    global $mysqli;
    
    $log = [];
    $log[] = "[" . date('Y-m-d H:i:s') . "] Payment monitoring started";
    
    try {
        // Find active loans where the last payment is overdue
        $query = "
            SELECT DISTINCT u.id, u.username, u.full_name, l.id as loan_id, l.monthly_payment
            FROM users u
            INNER JOIN loans l ON u.id = l.user_id
            WHERE u.status = ? AND l.status = ?
            AND DATE_ADD(l.created_at, INTERVAL 45 DAY) < NOW()
            AND l.id NOT IN (
                SELECT loan_id FROM loan_payments 
                WHERE payment_date > DATE_SUB(NOW(), INTERVAL 45 DAY)
            )
        ";
        
        $status_active = STATUS_APPROVED;
        $status_loan = LOAN_ACTIVE;
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ss", $status_active, $status_loan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $disabled_count = 0;
        $accounts_disabled = [];
        
        while ($row = $result->fetch_assoc()) {
            // Disable the account
            $update_stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE id = ?");
            $disabled_status = 'Disabled';
            $update_stmt->bind_param("si", $disabled_status, $row['id']);
            
            if ($update_stmt->execute()) {
                $disabled_count++;
                $accounts_disabled[] = "{$row['username']} (ID: {$row['id']}) - Loan #{$row['loan_id']}";
                $log[] = "✓ Disabled account: {$row['username']} due to missed payment on Loan #{$row['loan_id']}";
            } else {
                $log[] = "✗ Failed to disable account: {$row['username']}";
            }
            $update_stmt->close();
            
            // Log admin action
            $log_stmt = $mysqli->prepare("
                INSERT INTO admin_logs (admin_id, action, details) 
                VALUES (NULL, ?, ?)
            ");
            $action = "Auto-disable account for missed payment";
            $details = "User: {$row['full_name']}, Loan ID: {$row['loan_id']}, Amount: ₱{$row['monthly_payment']}";
            $log_stmt->bind_param("ss", $action, $details);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        $stmt->close();
        
        $log[] = "[" . date('Y-m-d H:i:s') . "] Disabled $disabled_count account(s) for missed payments";
        
    } catch (Exception $e) {
        $log[] = "✗ Error: " . $e->getMessage();
    }
    
    return $log;
}

/**
 * Re-enable accounts after payment is made
 */
function checkAndEnableAccounts() {
    global $mysqli;
    
    $log = [];
    
    try {
        // Find disabled accounts with recent payments
        $query = "
            SELECT DISTINCT u.id, u.username
            FROM users u
            INNER JOIN loans l ON u.id = l.user_id
            INNER JOIN loan_payments lp ON l.id = lp.loan_id
            WHERE u.status = ? AND lp.payment_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        
        $disabled_status = 'Disabled';
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $disabled_status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $enabled_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Re-enable the account
            $update_stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE id = ?");
            $approved_status = STATUS_APPROVED;
            $update_stmt->bind_param("si", $approved_status, $row['id']);
            
            if ($update_stmt->execute()) {
                $enabled_count++;
                $log[] = "✓ Re-enabled account: {$row['username']} after payment received";
            }
            $update_stmt->close();
        }
        
        $stmt->close();
        
        $log[] = "Re-enabled $enabled_count account(s)";
        
    } catch (Exception $e) {
        $log[] = "✗ Error: " . $e->getMessage();
    }
    
    return $log;
}

// Run the jobs
$disable_logs = checkAndDisableDelinquentAccounts();
$enable_logs = checkAndEnableAccounts();

// Combine logs
$all_logs = array_merge($disable_logs, $enable_logs);

// Display logs
$log_output = implode("\n", $all_logs);

// Optionally save to file
$log_file = __DIR__ . '/logs/payment-monitor.log';
if (!is_dir(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}
file_put_contents($log_file, $log_output . "\n\n", FILE_APPEND);

echo $log_output;
?>
