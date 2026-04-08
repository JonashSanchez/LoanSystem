# Loaning System - Setup & Installation Guide

## 📋 Overview

A comprehensive loan management system built with PHP and MySQL. Supports user registration, loan applications, admin approvals, and payment tracking.

**Features:**
- User Registration & Authentication with validation
- Multiple Account Types (Basic/Premium)
- Loan Applications (Max ₱10,000 with 3% interest)
- Document Uploads (Proof of Billing, Valid ID, COE)
- Admin Registration Approval
- Loan Payment Tracking
- Amortization Schedules

---

## 🔧 Prerequisites

- XAMPP (or similar AMP stack) with PHP 7.4+
- MySQL Server
- Modern Web Browser

---

## 📦 Installation Steps

### Step 1: Setup Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin/)
2. Create a new database named `loaning_system`
3. Go to the **SQL** tab and paste the contents of `database/setup.sql`
4. Click **Go** to execute the SQL script

### Step 2: Configure Database Connection

1. Open `config/db.php`
2. Update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'loaning_system');
   ```

### Step 3: Create Admin Account

Run this SQL in phpMyAdmin to create the first admin user:

```sql
INSERT INTO users (account_type, username, password, email, full_name, address, birthday, age, contact_number, tin_number, status, is_admin, created_at)
VALUES (
  'Premium',
  'admin',
  '$2y$10$kDyCXl8v.aWplpV7Z1p3H.oJJeNFJFLNgXcsVPFvXREKMhBa7LByO',
  'admin@loaning.local',
  'System Administrator',
  'Admin Address',
  '1995-01-01',
  31,
  '09123456789',
  '123-456-789-12345',
  'Approved',
  TRUE,
  NOW()
);
```

**Admin Credentials:**
- Username: `admin`
- Password: `Admin@123`

### Step 4: Verify File Permissions

Ensure the `public/uploads/` directory is writable:
- Windows: Right-click folder → Properties → Security → Edit → Full Control

---

## 🚀 Accessing the System

### User Access
- **URL:** http://localhost/LoaningSystem/pages/login.php
- **Register:** New users create accounts here
- New registrations are **Pending** until admin approval

### Admin Access
- **URL:** http://localhost/LoaningSystem/admin/dashboard.php
- **Credentials:** admin / Admin@123
- Approve/reject new user applications
- Manage users, loans, and blocked emails

---

## 📝 Loan Specifications

### Loan Requirements
- **Maximum Amount:** ₱10,000
- **Available Terms:** 1, 3, 6, 12 months
- **Interest Rate:** 3% (fixed, charged upfront)
- **Conditions:** 
  - User must have **Approved** account status
  - Interest is deducted from borrowed amount
  - Monthly payments are calculated automatically

### Loan Calculation Example
```
Principal: ₱10,000
Interest (3%): ₱300
Net Amount Received: ₱9,700
Total Amount Due: ₱10,300
Term: 12 months
Monthly Payment: ₱858.33
```

---

## 👤 User Registration Fields

### Required Information
1. **Account Type** - Basic or Premium
2. **Login Credentials** - Username (6+ chars), Password (8+ chars with uppercase, lowercase, number, special char)
3. **Personal Details** - Name, Email, Contact, Birthday, Address, Gender, TIN
4. **Bank Details** - Bank name, Account number, Cardholder name
5. **Company Details** - Company name, address, phone, position, monthly earnings
6. **Documents** - Proof of Billing, Valid ID, Certificate of Employment

### Account Types
- **Basic:** Unlimited registration slots
- **Premium:** Limited to 50 members maximum

---

## 🔐 Validation Rules

### Username
- Minimum 6 characters
- Alphanumeric, dots, underscores, hyphens, @ allowed
- Cannot be duplicate

### Password
- Minimum 8 characters
- Must contain: uppercase, lowercase, number, special character (!@#$%^&*)

### Email
- Valid email format
- Cannot be duplicate
- Email blocking supported (admin feature)

### Contact Number (Philippine format)
- Accepted: 09xxxxxxxxx, +639xxxxxxxxx, 639xxxxxxxxx

### TIN Number
- Format: XXX-XXX-XXX-XXXXX or 12 digits

---

## 📊 Admin Features

### Dashboard
- User statistics
- Loan statistics
- Account type breakdown
- Quick access to pending registrations

### User Management
- View all user accounts
- Filter by status (Pending, Approved, Rejected, Suspended)
- Review detailed user profiles
- Change account status
- View user loans

### Registration Review
- View pending user applications
- Review uploaded documents
- Approve or reject registrations

### Loan Management
- View all system loans
- Filter by status (Active, Paid, Defaulted, Cancelled)
- Check payment history
- View amortization schedules

### Blocked Emails
- Add/remove blocked email addresses
- Set rejection reasons

### Admin Logs
- Track all administrative actions

---

## 📁 Project Structure

```
LoaningSystem/
├── config/
│   ├── db.php (Database connection)
│   └── constants.php (App constants)
├── database/
│   └── setup.sql (Database schema)
├── includes/
│   ├── Auth.php (Authentication)
│   ├── Validator.php (Input validation)
│   ├── Loan.php (Loan management)
│   └── Registration.php (User registration)
├── pages/
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── apply-loan.php
│   ├── loan-details.php
│   └── logout.php
├── admin/
│   ├── dashboard.php
│   ├── registrations.php
│   ├── review-registration.php
│   ├── users.php
│   ├── user-details.php
│   ├── loans.php
│   ├── loan-details.php
│   └── blocked-emails.php
├── public/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   ├── uploads/ (Document storage)
│   └── index.html
└── index.php (Entry point)
```

---

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check credentials in `config/db.php`
- Ensure `loaning_system` database exists

### Upload Directory Error
- Ensure `public/uploads/` folder exists
- Check folder permissions (should be writable)

### Session Issues
- Clear browser cookies
- Verify PHP session settings
- Check session timeout (30 minutes default)

### Admin Login Not Working
- Verify admin account was created with correct SQL
- Check password hash (bcrypt with cost 10)

---

## 🛡️ Security Features

- **Password Hashing:** bcrypt (PASSWORD_BCRYPT)
- **SQL Injection Prevention:** Prepared statements
- **Session Management:** Login sessions with timeouts
- **Input Validation:** Comprehensive validation rules
- **Email Blocking:** Admin control over registrations

---

## 📞 System Notifications

The system tracks:
- User registrations (pending approval)
- Loan applications
- Payment due dates
- Account status changes via admin logs

---

## ⚙️ Configuration

Edit `config/constants.php` to customize:
- Maximum loan amount
- Interest rate
- Loan terms
- Premium account limit
- Upload file size limit
- Session timeout duration

---

## 📞 Support

For issues or customizations, check:
1. Browser console for JavaScript errors
2. Server error logs in XAMPP
3. Database tables in phpMyAdmin
4. Admin logs table for action history

---

**Version:** 1.0.0  
**Last Updated:** April 2026

---

## 🎯 Next Steps

1. ✅ Database setup
2. ✅ Admin account creation
3. ✅ Test user registration
4. ✅ Test admin approval workflow
5. ✅ Test loan application
6. ✅ Test loan payments
7. ⭐ Deploy to production

Enjoy using the Loaning System!
