<?php
/**
 * Database Configuration
 * Update these credentials with your actual database info
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'loaning_system');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

/**
 * Function to execute queries safely
 */
function executeQuery($query, $types = "", $params = []) {
    global $mysqli;
    
    if ($types && $params) {
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt;
    } else {
        return $mysqli->query($query);
    }
}

/**
 * Fetch single row
 */
function fetchRow($query, $types = "", $params = []) {
    $result = executeQuery($query, $types, $params);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Fetch all rows
 */
function fetchAll($query, $types = "", $params = []) {
    $result = executeQuery($query, $types, $params);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}
?>
