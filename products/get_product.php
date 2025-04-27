<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once("../connection.php");

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

$product_id = (int)$_GET['id'];

try {
    // Prepare and execute query
    $query = "SELECT * FROM products WHERE product_id = ? AND is_active = 1";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found']);
        exit();
    }
    
    // Fetch product data
    $product = $result->fetch_assoc();
    
    // Format dates for form input
    if (!empty($product['expiry_date'])) {
        $product['expiry_date'] = date('Y-m-d', strtotime($product['expiry_date']));
    }
    
    // Return product data as JSON
    header('Content-Type: application/json');
    echo json_encode($product);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?> 