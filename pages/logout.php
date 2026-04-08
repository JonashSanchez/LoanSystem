<?php
require_once('../config/db.php');
require_once('../includes/Auth.php');

Auth::logout();
header('Location: /LoaningSystem/pages/login.php');
exit;
?>
