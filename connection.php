<?php
/**
 * Database Connection Class
 * 
 * This file provides a functional approach to database connection management.
 * It uses dependency injection, immutability, and advanced error handling.
 */

// Custom exception for database errors
class DatabaseException extends Exception {
    public function __construct(string $message, int $code = 0, Throwable $previous = null) {
        parent::__construct('Database Error: ' . $message, $code, $previous);
    }
}

/**
 * Database configuration for different environments
 * This uses a functional approach with immutable configurations
 */
function getDbConfig(string $environment = 'development'): array {
    $configs = [
        'development' => [
            'server' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'bbims',
            'charset' => 'utf8mb4'
        ],
        'production' => [
            'server' => 'localhost', // Change in production
            'username' => 'bbims_user', // Change in production
            'password' => 'secure_password', // Change in production
            'database' => 'bbims',
            'charset' => 'utf8mb4'
        ],
        'testing' => [
            'server' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'bbims_test',
            'charset' => 'utf8mb4'
        ]
    ];
    
    // Return a copy of the config to ensure immutability
    return isset($configs[$environment]) ? $configs[$environment] : $configs['development'];
}

/**
 * Create a database connection
 * 
 * @param array $config Database configuration
 * @return mysqli Database connection
 * @throws DatabaseException
 */
function createConnection(array $config): mysqli {
    try {
        // Create connection without database first
        $conn = new mysqli($config['server'], $config['username'], $config['password']);
        
        // Check connection
        if ($conn->connect_error) {
            throw new DatabaseException("Connection failed: " . $conn->connect_error);
        }
        
        // Create the database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS {$config['database']}");
        
        // Close the initial connection
        $conn->close();
        
        // Connect to the database
        $conn = new mysqli(
            $config['server'], 
            $config['username'], 
            $config['password'], 
            $config['database']
        );
        
        // Set character set
        if (!$conn->set_charset($config['charset'])) {
            throw new DatabaseException("Error loading character set {$config['charset']}: " . $conn->error);
        }
        
        return $conn;
    } catch (Exception $e) {
        throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
    }
}

/**
 * Execute a database query safely and functionally
 * 
 * @param mysqli $connection Database connection
 * @param string $query SQL query
 * @param array $params Query parameters
 * @return mixed Query result
 * @throws DatabaseException
 */
function executeQuery(mysqli $connection, string $query, array $params = []): mixed {
    try {
        $stmt = $connection->prepare($query);
        
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $connection->error);
        }
        
        if (!empty($params)) {
            // Build types string
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i'; // integer
                } elseif (is_float($param)) {
                    $types .= 'd'; // double
                } elseif (is_string($param)) {
                    $types .= 's'; // string
                } else {
                    $types .= 'b'; // blob
                }
            }
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
        }
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }
        
        // Get the result
        $result = $stmt->get_result();
        
        // If it's a SELECT query, fetch all results
        if ($result) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        }
        
        // For INSERT, UPDATE, DELETE queries
        $affected = $stmt->affected_rows;
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return [
            'affected_rows' => $affected,
            'insert_id' => $insertId
        ];
    } catch (Exception $e) {
        throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
    }
}

/**
 * Functional wrapper for transactions
 * 
 * @param mysqli $connection Database connection
 * @param callable $operation Function that performs the database operations
 * @return mixed Result of the operation
 * @throws DatabaseException
 */
function transaction(mysqli $connection, callable $operation): mixed {
    try {
        $connection->begin_transaction();
        $result = $operation($connection);
        $connection->commit();
        return $result;
    } catch (Exception $e) {
        $connection->rollback();
        throw new DatabaseException("Transaction failed: " . $e->getMessage(), $e->getCode(), $e);
    }
}

/**
 * Close a database connection safely
 * 
 * @param mysqli $connection Database connection
 * @return bool Whether the connection was closed successfully
 */
function closeConnection(mysqli $connection): bool {
    return $connection->close();
}

// Create a database connection for global use
try {
    $config = getDbConfig();
    $con = createConnection($config);
} catch (DatabaseException $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Display user-friendly error message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Create users table if it doesn't exist
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login DATETIME DEFAULT NULL
)";

if ($con->query($users_table) !== TRUE) {
    die("Error creating users table: " . $con->error);
}

// Create User Profiles directory if it doesn't exist
$profile_dir = __DIR__ . "/User Profiles";
if (!file_exists($profile_dir)) {
    mkdir($profile_dir, 0777, true);
}
?> 