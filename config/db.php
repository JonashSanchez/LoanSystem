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
    
    // If executeQuery returns a statement (from prepared queries), get the result set
    if ($result && is_object($result) && get_class($result) === 'mysqli_stmt') {
        $resultSet = $result->get_result();
        if ($resultSet && $resultSet->num_rows > 0) {
            return $resultSet->fetch_assoc();
        }
    } elseif ($result && is_object($result) && get_class($result) === 'mysqli_result') {
        // For direct queries without prepared statements
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Fetch all rows
 */
function fetchAll($query, $types = "", $params = []) {
    $result = executeQuery($query, $types, $params);
    $data = [];
    
    // If executeQuery returns a statement (from prepared queries), get the result set
    if ($result && is_object($result) && get_class($result) === 'mysqli_stmt') {
        $resultSet = $result->get_result();
        if ($resultSet && $resultSet->num_rows > 0) {
            while ($row = $resultSet->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } elseif ($result && is_object($result) && get_class($result) === 'mysqli_result') {
        // For direct queries without prepared statements
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    }
    
    return $data;
}
?>
