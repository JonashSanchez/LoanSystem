<?php
/**
 * Loan Management Class
 */

class Loan {
    
    /**
     * Calculate loan details
     */
    public static function calculateLoanDetails($principal, $interestRate = LOAN_INTEREST_RATE, $term) {
        // Interest charge at once
        $interestAmount = $principal * ($interestRate / 100);
        
        // Net amount received (principal - interest deducted)
        $netAmount = $principal - $interestAmount;
        
        // Monthly payment (principal + interest divided by months)
        $totalAmount = $principal + $interestAmount;
        $monthlyPayment = $totalAmount / $term;
        
        return [
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'interest_amount' => round($interestAmount, 2),
            'net_amount_received' => round($netAmount, 2),
            'total_amount_due' => round($totalAmount, 2),
            'monthly_payment' => round($monthlyPayment, 2),
            'loan_term' => $term
        ];
    }
    
    /**
     * Create a new loan
     */
    public static function createLoan($userId, $amount, $term) {
        // Validate
        $validator = Validator::validateLoanAmount($amount);
        if ($validator !== true) {
            return ['success' => false, 'message' => $validator];
        }
        
        $termValidator = Validator::validateLoanTerm($term);
        if ($termValidator !== true) {
            return ['success' => false, 'message' => $termValidator];
        }
        
        // Check user has approved account
        $user = fetchRow("SELECT status FROM users WHERE id = ?", "i", [$userId]);
        if (!$user || $user['status'] !== STATUS_APPROVED) {
            return ['success' => false, 'message' => 'Your account must be approved to apply for a loan'];
        }
        
        // Calculate loan details
        $details = self::calculateLoanDetails($amount, LOAN_INTEREST_RATE, $term);
        
        // Insert loan
        $result = executeQuery(
            "INSERT INTO loans (user_id, principal_amount, interest_rate, interest_amount, net_amount_received, loan_term, monthly_payment) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "idddddd",
            [
                $userId,
                $details['principal'],
                $details['interest_rate'],
                $details['interest_amount'],
                $details['net_amount_received'],
                $details['loan_term'],
                $details['monthly_payment']
            ]
        );
        
        if ($result) {
            $loanId = $result->insert_id;
            return [
                'success' => true,
                'message' => 'Loan application created successfully',
                'loan_id' => $loanId,
                'details' => $details
            ];
        }
        
        return ['success' => false, 'message' => 'Error creating loan'];
    }
    
    /**
     * Get user's loans
     */
    public static function getUserLoans($userId, $status = null) {
        if ($status) {
            return fetchAll(
                "SELECT * FROM loans WHERE user_id = ? AND status = ? ORDER BY created_at DESC",
                "is",
                [$userId, $status]
            );
        }
        return fetchAll("SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC", "i", [$userId]);
    }
    
    /**
     * Get loan details
     */
    public static function getLoanDetails($loanId) {
        return fetchRow("SELECT * FROM loans WHERE id = ?", "i", [$loanId]);
    }
    
    /**
     * Get loan payments
     */
    public static function getLoanPayments($loanId) {
        return fetchAll("SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY payment_date DESC", "i", [$loanId]);
    }
    
    /**
     * Record loan payment
     */
    public static function recordPayment($loanId, $amount, $paymentMethod = 'Manual', $notes = '') {
        $loan = self::getLoanDetails($loanId);
        if (!$loan) {
            return ['success' => false, 'message' => 'Loan not found'];
        }
        
        if ($loan['status'] !== LOAN_ACTIVE) {
            return ['success' => false, 'message' => 'Loan is not active'];
        }
        
        // Insert payment
        $result = executeQuery(
            "INSERT INTO loan_payments (loan_id, amount_paid, payment_method, notes) VALUES (?, ?, ?, ?)",
            "idss",
            [$loanId, $amount, $paymentMethod, $notes]
        );
        
        if ($result) {
            // Check if loan is fully paid
            $totalPaid = fetchRow(
                "SELECT SUM(amount_paid) as total FROM loan_payments WHERE loan_id = ?",
                "i",
                [$loanId]
            );
            
            $totalDue = $loan['principal_amount'] + $loan['interest_amount'];
            if ($totalPaid['total'] >= $totalDue) {
                executeQuery("UPDATE loans SET status = ? WHERE id = ?", "si", [LOAN_PAID, $loanId]);
            }
            
            return ['success' => true, 'message' => 'Payment recorded successfully'];
        }
        
        return ['success' => false, 'message' => 'Error recording payment'];
    }
    
    /**
     * Get loan amortization schedule
     */
    public static function getAmortizationSchedule($loanId) {
        $loan = self::getLoanDetails($loanId);
        if (!$loan) {
            return null;
        }
        
        $schedule = [];
        $loanDate = new DateTime($loan['loan_date']);
        $balance = $loan['principal_amount'] + $loan['interest_amount'];
        
        for ($i = 1; $i <= $loan['loan_term']; $i++) {
            $dueDate = clone $loanDate;
            $dueDate->add(new DateInterval("P{$i}M"));
            
            $balance -= $loan['monthly_payment'];
            $balance = max(0, $balance);
            
            $schedule[] = [
                'month' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'monthly_payment' => round($loan['monthly_payment'], 2),
                'remaining_balance' => round($balance, 2)
            ];
        }
        
        return $schedule;
    }
}
?>
