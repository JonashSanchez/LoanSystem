<?php
/**
 * QUICK SETUP GUIDE
 * 
 * Follow these steps to get your Loaning System running:
 */

echo "========================================\n";
echo "  LOANING SYSTEM - QUICK SETUP\n";
echo "========================================\n\n";

echo "STEP 1: DATABASE SETUP\n";
echo "-------------------------------------\n";
echo "1. Open phpMyAdmin: http://localhost/phpmyadmin/\n";
echo "2. Create database: 'loaning_system'\n";
echo "3. Go to SQL tab and paste: database/setup.sql\n";
echo "4. Click Go to execute\n\n";

echo "STEP 2: DATABASE CREDENTIALS\n";
echo "-------------------------------------\n";
echo "Edit: config/db.php\n";
echo "Update if needed:\n";
echo "  DB_HOST: localhost\n";
echo "  DB_USER: root\n";
echo "  DB_PASS: (empty if default)\n";
echo "  DB_NAME: loaning_system\n\n";

echo "STEP 3: CREATE ADMIN ACCOUNT\n";
echo "-------------------------------------\n";
echo "Run this SQL in phpMyAdmin:\n\n";
echo "INSERT INTO users (account_type, username, password, email, full_name, address, birthday, age, contact_number, tin_number, status, is_admin, created_at)\n";
echo "VALUES (\n";
echo "  'Premium',\n";
echo "  'admin',\n";
echo "  '\$2y\$10\$kDyCXl8v.aWplpV7Z1p3H.oJJeNFJFLNgXcsVPFvXREKMhBa7LByO',\n";
echo "  'admin@loaning.local',\n";
echo "  'System Administrator',\n";
echo "  'Admin Address',\n";
echo "  '1995-01-01',\n";
echo "  31,\n";
echo "  '09123456789',\n";
echo "  '123-456-789-12345',\n";
echo "  'Approved',\n";
echo "  TRUE,\n";
echo "  NOW()\n";
echo ");\n\n";

echo "ADMIN CREDENTIALS:\n";
echo "  Username: admin\n";
echo "  Password: Admin@123\n\n";

echo "STEP 4: ACCESS THE SYSTEM\n";
echo "-------------------------------------\n";
echo "User Registration: http://localhost/LoaningSystem/pages/register.php\n";
echo "User Login: http://localhost/LoaningSystem/pages/login.php\n";
echo "Admin Panel: http://localhost/LoaningSystem/admin/dashboard.php\n\n";

echo "========================================\n";
echo "Installation Guide: See README.md\n";
echo "========================================\n";
?>
