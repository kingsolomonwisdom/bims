<?php
/**
 * Business IMS REST API
 * 
 * This file serves as the main entry point for the API.
 * It uses a functional approach to handle API requests.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once('../connection.php');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/api/', '', $path);
$parts = explode('/', $path);
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;

// API response function
function sendResponse(int $statusCode, array $data): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Authentication middleware
function authenticate(): ?array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    // Validate token - this is a simplified example
    // In a real app, you'd use JWT or a more secure method
    global $con;
    
    try {
        $query = "SELECT u.* FROM users u JOIN api_tokens t ON u.user_id = t.user_id WHERE t.token = ? AND t.expires_at > NOW()";
        $result = executeQuery($con, $query, [$token]);
        
        return $result[0] ?? null;
    } catch (DatabaseException $e) {
        return null;
    }
}

// Create API functions for each resource
function handleProducts(string $method, ?int $id): void {
    global $con;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single product
                try {
                    $query = "
                        SELECT 
                            p.*, 
                            c.name as category_name,
                            s.company_name as supplier_name
                        FROM 
                            products p
                        LEFT JOIN 
                            categories c ON p.category_id = c.category_id
                        LEFT JOIN 
                            suppliers s ON p.supplier_id = s.supplier_id
                        WHERE 
                            p.product_id = ? AND p.is_active = 1
                    ";
                    
                    $result = executeQuery($con, $query, [$id]);
                    
                    if (empty($result)) {
                        sendResponse(404, ['error' => 'Product not found']);
                    }
                    
                    sendResponse(200, $result[0]);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error']);
                }
            } else {
                // Get all products
                try {
                    // Handle query parameters
                    $filters = [];
                    $params = [];
                    
                    // Category filter
                    if (isset($_GET['category'])) {
                        $filters[] = "c.name = ?";
                        $params[] = $_GET['category'];
                    }
                    
                    // Search filter
                    if (isset($_GET['search'])) {
                        $filters[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
                        $searchTerm = "%{$_GET['search']}%";
                        $params[] = $searchTerm;
                        $params[] = $searchTerm;
                        $params[] = $searchTerm;
                    }
                    
                    // Build where clause
                    $whereClause = "p.is_active = 1";
                    if (!empty($filters)) {
                        $whereClause .= " AND " . implode(" AND ", $filters);
                    }
                    
                    // Pagination
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                    $offset = ($page - 1) * $limit;
                    
                    // Sort
                    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'name';
                    $sortDir = isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc' ? 'DESC' : 'ASC';
                    
                    // Validate sort field to prevent SQL injection
                    $allowedSortFields = ['name', 'sku', 'selling_price', 'stock_quantity', 'created_at'];
                    if (!in_array($sortField, $allowedSortFields)) {
                        $sortField = 'name';
                    }
                    
                    $query = "
                        SELECT 
                            p.*, 
                            c.name as category_name,
                            s.company_name as supplier_name
                        FROM 
                            products p
                        LEFT JOIN 
                            categories c ON p.category_id = c.category_id
                        LEFT JOIN 
                            suppliers s ON p.supplier_id = s.supplier_id
                        WHERE 
                            $whereClause
                        ORDER BY 
                            p.$sortField $sortDir
                        LIMIT ?, ?
                    ";
                    
                    // Add pagination params
                    $params[] = $offset;
                    $params[] = $limit;
                    
                    $result = executeQuery($con, $query, $params);
                    
                    // Get total count for pagination
                    $countQuery = "
                        SELECT 
                            COUNT(*) as total
                        FROM 
                            products p
                        LEFT JOIN 
                            categories c ON p.category_id = c.category_id
                        LEFT JOIN 
                            suppliers s ON p.supplier_id = s.supplier_id
                        WHERE 
                            $whereClause
                    ";
                    
                    // Remove pagination params
                    array_pop($params);
                    array_pop($params);
                    
                    $countResult = executeQuery($con, $countQuery, $params);
                    $total = $countResult[0]['total'];
                    
                    sendResponse(200, [
                        'data' => $result,
                        'pagination' => [
                            'total' => (int)$total,
                            'page' => $page,
                            'limit' => $limit,
                            'pages' => ceil($total / $limit)
                        ]
                    ]);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
            }
            break;
            
        case 'POST':
            // Create product
            $user = authenticate();
            if (!$user) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            
            if (!in_array($user['role'], ['admin', 'manager', 'inventory'])) {
                sendResponse(403, ['error' => 'Forbidden']);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['sku'], $data['name'], $data['purchase_price'], $data['selling_price'])) {
                sendResponse(400, ['error' => 'Missing required fields']);
            }
            
            try {
                $query = "
                    INSERT INTO products (
                        sku, name, description, brand, category_id, supplier_id,
                        purchase_price, selling_price, discount_price, tax_rate,
                        stock_quantity, reorder_level, expiry_date, image_url
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $params = [
                    $data['sku'],
                    $data['name'],
                    $data['description'] ?? '',
                    $data['brand'] ?? '',
                    $data['category_id'] ?? null,
                    $data['supplier_id'] ?? null,
                    $data['purchase_price'],
                    $data['selling_price'],
                    $data['discount_price'] ?? null,
                    $data['tax_rate'] ?? 0,
                    $data['stock_quantity'] ?? 0,
                    $data['reorder_level'] ?? 5,
                    $data['expiry_date'] ?? null,
                    $data['image_url'] ?? null
                ];
                
                $result = executeQuery($con, $query, $params);
                $productId = $result['insert_id'];
                
                // Log inventory creation
                $logQuery = "
                    INSERT INTO inventory_log (
                        product_id, user_id, quantity_change, type, notes
                    ) VALUES (?, ?, ?, 'adjustment', ?)
                ";
                
                executeQuery($con, $logQuery, [
                    $productId,
                    $user['user_id'],
                    $data['stock_quantity'] ?? 0,
                    'Initial stock when creating product'
                ]);
                
                // Get the created product
                $productQuery = "SELECT * FROM products WHERE product_id = ?";
                $productResult = executeQuery($con, $productQuery, [$productId]);
                
                sendResponse(201, $productResult[0]);
            } catch (DatabaseException $e) {
                sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'PUT':
            // Update product
            if (!$id) {
                sendResponse(400, ['error' => 'Product ID is required']);
            }
            
            $user = authenticate();
            if (!$user) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            
            if (!in_array($user['role'], ['admin', 'manager', 'inventory'])) {
                sendResponse(403, ['error' => 'Forbidden']);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                sendResponse(400, ['error' => 'No data provided']);
            }
            
            try {
                // Check if product exists
                $checkQuery = "SELECT * FROM products WHERE product_id = ? AND is_active = 1";
                $checkResult = executeQuery($con, $checkQuery, [$id]);
                
                if (empty($checkResult)) {
                    sendResponse(404, ['error' => 'Product not found']);
                }
                
                $oldProduct = $checkResult[0];
                
                // Build update query
                $updateFields = [];
                $params = [];
                
                $fields = [
                    'sku', 'name', 'description', 'brand', 'category_id', 'supplier_id',
                    'purchase_price', 'selling_price', 'discount_price', 'tax_rate',
                    'reorder_level', 'expiry_date', 'image_url'
                ];
                
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $updateFields[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
                
                // Handle stock quantity separately to log inventory change
                $stockChanged = isset($data['stock_quantity']) && $data['stock_quantity'] != $oldProduct['stock_quantity'];
                
                if ($stockChanged) {
                    $updateFields[] = "stock_quantity = ?";
                    $params[] = $data['stock_quantity'];
                }
                
                if (empty($updateFields)) {
                    sendResponse(400, ['error' => 'No valid fields to update']);
                }
                
                $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
                
                $query = "
                    UPDATE products SET " . implode(", ", $updateFields) . "
                    WHERE product_id = ?
                ";
                
                $params[] = $id;
                
                $result = executeQuery($con, $query, $params);
                
                // Log inventory change if stock quantity was updated
                if ($stockChanged) {
                    $quantityChange = $data['stock_quantity'] - $oldProduct['stock_quantity'];
                    $logQuery = "
                        INSERT INTO inventory_log (
                            product_id, user_id, quantity_change, type, notes
                        ) VALUES (?, ?, ?, 'adjustment', ?)
                    ";
                    
                    executeQuery($con, $logQuery, [
                        $id,
                        $user['user_id'],
                        $quantityChange,
                        'Stock adjustment via API'
                    ]);
                }
                
                // Get the updated product
                $productQuery = "SELECT * FROM products WHERE product_id = ?";
                $productResult = executeQuery($con, $productQuery, [$id]);
                
                sendResponse(200, $productResult[0]);
            } catch (DatabaseException $e) {
                sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'DELETE':
            // Delete product (soft delete)
            if (!$id) {
                sendResponse(400, ['error' => 'Product ID is required']);
            }
            
            $user = authenticate();
            if (!$user) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            
            if (!in_array($user['role'], ['admin', 'manager'])) {
                sendResponse(403, ['error' => 'Forbidden']);
            }
            
            try {
                // Check if product exists
                $checkQuery = "SELECT * FROM products WHERE product_id = ? AND is_active = 1";
                $checkResult = executeQuery($con, $checkQuery, [$id]);
                
                if (empty($checkResult)) {
                    sendResponse(404, ['error' => 'Product not found']);
                }
                
                // Soft delete
                $query = "UPDATE products SET is_active = 0 WHERE product_id = ?";
                $result = executeQuery($con, $query, [$id]);
                
                sendResponse(200, ['message' => 'Product deleted successfully']);
            } catch (DatabaseException $e) {
                sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleSuppliers(string $method, ?int $id): void {
    global $con;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single supplier
                try {
                    $query = "SELECT * FROM suppliers WHERE supplier_id = ?";
                    $result = executeQuery($con, $query, [$id]);
                    
                    if (empty($result)) {
                        sendResponse(404, ['error' => 'Supplier not found']);
                    }
                    
                    sendResponse(200, $result[0]);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error']);
                }
            } else {
                // Get all suppliers
                try {
                    // Handle filters
                    $filters = [];
                    $params = [];
                    
                    // Status filter
                    if (isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive'])) {
                        $filters[] = "status = ?";
                        $params[] = $_GET['status'];
                    }
                    
                    // Search filter
                    if (isset($_GET['search'])) {
                        $filters[] = "(company_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
                        $searchTerm = "%{$_GET['search']}%";
                        $params[] = $searchTerm;
                        $params[] = $searchTerm;
                        $params[] = $searchTerm;
                    }
                    
                    // Build where clause
                    $whereClause = "";
                    if (!empty($filters)) {
                        $whereClause = "WHERE " . implode(" AND ", $filters);
                    }
                    
                    // Pagination
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                    $offset = ($page - 1) * $limit;
                    
                    // Sort
                    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'company_name';
                    $sortDir = isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc' ? 'DESC' : 'ASC';
                    
                    // Validate sort field
                    $allowedSortFields = ['company_name', 'contact_person', 'country', 'created_at'];
                    if (!in_array($sortField, $allowedSortFields)) {
                        $sortField = 'company_name';
                    }
                    
                    $query = "
                        SELECT * FROM suppliers
                        $whereClause
                        ORDER BY $sortField $sortDir
                        LIMIT ?, ?
                    ";
                    
                    // Add pagination params
                    $params[] = $offset;
                    $params[] = $limit;
                    
                    $result = executeQuery($con, $query, $params);
                    
                    // Get total count for pagination
                    $countQuery = "
                        SELECT COUNT(*) as total FROM suppliers
                        $whereClause
                    ";
                    
                    // Remove pagination params
                    array_pop($params);
                    array_pop($params);
                    
                    $countResult = executeQuery($con, $countQuery, $params);
                    $total = $countResult[0]['total'];
                    
                    sendResponse(200, [
                        'data' => $result,
                        'pagination' => [
                            'total' => (int)$total,
                            'page' => $page,
                            'limit' => $limit,
                            'pages' => ceil($total / $limit)
                        ]
                    ]);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
            }
            break;
            
        // Other methods for suppliers...
        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleOrders(string $method, ?int $id): void {
    global $con;
    
    switch ($method) {
        case 'GET':
            $user = authenticate();
            if (!$user) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            
            if ($id) {
                // Get single order with items
                try {
                    // Get order details
                    $orderQuery = "
                        SELECT 
                            o.*,
                            c.first_name as customer_first_name,
                            c.last_name as customer_last_name,
                            u.username as created_by
                        FROM 
                            orders o
                        LEFT JOIN 
                            customers c ON o.customer_id = c.customer_id
                        LEFT JOIN 
                            users u ON o.user_id = u.user_id
                        WHERE 
                            o.order_id = ?
                    ";
                    
                    $orderResult = executeQuery($con, $orderQuery, [$id]);
                    
                    if (empty($orderResult)) {
                        sendResponse(404, ['error' => 'Order not found']);
                    }
                    
                    $order = $orderResult[0];
                    
                    // Get order items
                    $itemsQuery = "
                        SELECT 
                            oi.*,
                            p.sku,
                            p.name as product_name
                        FROM 
                            order_items oi
                        JOIN 
                            products p ON oi.product_id = p.product_id
                        WHERE 
                            oi.order_id = ?
                    ";
                    
                    $itemsResult = executeQuery($con, $itemsQuery, [$id]);
                    
                    // Get payments
                    $paymentsQuery = "
                        SELECT * FROM payments WHERE order_id = ?
                    ";
                    
                    $paymentsResult = executeQuery($con, $paymentsQuery, [$id]);
                    
                    // Combine all data
                    $order['items'] = $itemsResult;
                    $order['payments'] = $paymentsResult;
                    
                    sendResponse(200, $order);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                // Get all orders with pagination and filtering
                try {
                    // Build filters
                    $filters = [];
                    $params = [];
                    
                    // Filter by status
                    if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'processing', 'completed', 'cancelled'])) {
                        $filters[] = "o.order_status = ?";
                        $params[] = $_GET['status'];
                    }
                    
                    // Filter by payment status
                    if (isset($_GET['payment_status']) && in_array($_GET['payment_status'], ['pending', 'completed', 'failed', 'refunded'])) {
                        $filters[] = "o.payment_status = ?";
                        $params[] = $_GET['payment_status'];
                    }
                    
                    // Filter by date range
                    if (isset($_GET['start_date'])) {
                        $filters[] = "DATE(o.order_date) >= ?";
                        $params[] = $_GET['start_date'];
                    }
                    
                    if (isset($_GET['end_date'])) {
                        $filters[] = "DATE(o.order_date) <= ?";
                        $params[] = $_GET['end_date'];
                    }
                    
                    // Filter by customer
                    if (isset($_GET['customer_id'])) {
                        $filters[] = "o.customer_id = ?";
                        $params[] = $_GET['customer_id'];
                    }
                    
                    // Build where clause
                    $whereClause = "";
                    if (!empty($filters)) {
                        $whereClause = "WHERE " . implode(" AND ", $filters);
                    }
                    
                    // Pagination
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                    $offset = ($page - 1) * $limit;
                    
                    // Sort
                    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
                    $sortDir = isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc' ? 'DESC' : 'ASC';
                    
                    // Validate sort field
                    $allowedSortFields = ['order_id', 'order_date', 'total_amount', 'payment_status', 'order_status'];
                    if (!in_array($sortField, $allowedSortFields)) {
                        $sortField = 'order_date';
                    }
                    
                    $query = "
                        SELECT 
                            o.*,
                            c.first_name as customer_first_name,
                            c.last_name as customer_last_name,
                            u.username as created_by
                        FROM 
                            orders o
                        LEFT JOIN 
                            customers c ON o.customer_id = c.customer_id
                        LEFT JOIN 
                            users u ON o.user_id = u.user_id
                        $whereClause
                        ORDER BY o.$sortField $sortDir
                        LIMIT ?, ?
                    ";
                    
                    // Add pagination params
                    $params[] = $offset;
                    $params[] = $limit;
                    
                    $result = executeQuery($con, $query, $params);
                    
                    // Get total count for pagination
                    $countQuery = "
                        SELECT COUNT(*) as total
                        FROM orders o
                        $whereClause
                    ";
                    
                    // Remove pagination params
                    array_pop($params);
                    array_pop($params);
                    
                    $countResult = executeQuery($con, $countQuery, $params);
                    $total = $countResult[0]['total'];
                    
                    sendResponse(200, [
                        'data' => $result,
                        'pagination' => [
                            'total' => (int)$total,
                            'page' => $page,
                            'limit' => $limit,
                            'pages' => ceil($total / $limit)
                        ]
                    ]);
                } catch (DatabaseException $e) {
                    sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
                }
            }
            break;
            
        case 'POST':
            // Create a new order
            $user = authenticate();
            if (!$user) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['items']) || empty($data['items'])) {
                sendResponse(400, ['error' => 'Order items are required']);
            }
            
            try {
                // Start transaction
                return transaction($con, function($connection) use ($data, $user) {
                    // Calculate order totals
                    $totalAmount = 0;
                    $taxAmount = 0;
                    $discountAmount = 0;
                    
                    // Validate items and calculate totals
                    foreach ($data['items'] as $item) {
                        if (!isset($item['product_id'], $item['quantity'], $item['unit_price'])) {
                            throw new DatabaseException('Invalid order item data');
                        }
                        
                        // Check if product exists and has enough stock
                        $productQuery = "SELECT * FROM products WHERE product_id = ? AND is_active = 1";
                        $productResult = executeQuery($connection, $productQuery, [$item['product_id']]);
                        
                        if (empty($productResult)) {
                            throw new DatabaseException("Product with ID {$item['product_id']} not found");
                        }
                        
                        $product = $productResult[0];
                        
                        if ($product['stock_quantity'] < $item['quantity']) {
                            throw new DatabaseException("Insufficient stock for product {$product['name']}");
                        }
                        
                        // Calculate item totals
                        $subtotal = $item['quantity'] * $item['unit_price'];
                        $itemDiscount = $item['discount_amount'] ?? 0;
                        $itemTax = $item['tax_amount'] ?? ($subtotal * ($product['tax_rate'] / 100));
                        
                        $totalAmount += $subtotal;
                        $taxAmount += $itemTax;
                        $discountAmount += $itemDiscount;
                    }
                    
                    // Create order
                    $orderQuery = "
                        INSERT INTO orders (
                            customer_id, user_id, total_amount, tax_amount, discount_amount,
                            payment_method, payment_status, order_status, notes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    
                    $orderParams = [
                        $data['customer_id'] ?? null,
                        $user['user_id'],
                        $totalAmount,
                        $taxAmount,
                        $discountAmount,
                        $data['payment_method'] ?? 'cash',
                        $data['payment_status'] ?? 'pending',
                        $data['order_status'] ?? 'pending',
                        $data['notes'] ?? null
                    ];
                    
                    $orderResult = executeQuery($connection, $orderQuery, $orderParams);
                    $orderId = $orderResult['insert_id'];
                    
                    // Add order items
                    foreach ($data['items'] as $item) {
                        $product = executeQuery($connection, "SELECT * FROM products WHERE product_id = ?", [$item['product_id']])[0];
                        
                        $subtotal = $item['quantity'] * $item['unit_price'];
                        $itemDiscount = $item['discount_amount'] ?? 0;
                        $itemTax = $item['tax_amount'] ?? ($subtotal * ($product['tax_rate'] / 100));
                        
                        $itemQuery = "
                            INSERT INTO order_items (
                                order_id, product_id, quantity, unit_price,
                                discount_amount, tax_amount, subtotal
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ";
                        
                        $itemParams = [
                            $orderId,
                            $item['product_id'],
                            $item['quantity'],
                            $item['unit_price'],
                            $itemDiscount,
                            $itemTax,
                            $subtotal
                        ];
                        
                        executeQuery($connection, $itemQuery, $itemParams);
                        
                        // Update product stock
                        $updateStockQuery = "
                            UPDATE products 
                            SET stock_quantity = stock_quantity - ?
                            WHERE product_id = ?
                        ";
                        
                        executeQuery($connection, $updateStockQuery, [$item['quantity'], $item['product_id']]);
                        
                        // Log inventory change
                        $logQuery = "
                            INSERT INTO inventory_log (
                                product_id, user_id, quantity_change, type, reference_id, notes
                            ) VALUES (?, ?, ?, 'sale', ?, ?)
                        ";
                        
                        executeQuery($connection, $logQuery, [
                            $item['product_id'],
                            $user['user_id'],
                            -$item['quantity'],
                            $orderId,
                            "Order #{$orderId}"
                        ]);
                    }
                    
                    // Add payment if status is completed
                    if (($data['payment_status'] ?? '') === 'completed') {
                        $paymentQuery = "
                            INSERT INTO payments (
                                order_id, amount, payment_method, reference_number, created_by
                            ) VALUES (?, ?, ?, ?, ?)
                        ";
                        
                        $paymentParams = [
                            $orderId,
                            $totalAmount,
                            $data['payment_method'] ?? 'cash',
                            $data['reference_number'] ?? null,
                            $user['user_id']
                        ];
                        
                        executeQuery($connection, $paymentQuery, $paymentParams);
                    }
                    
                    // Get full order details
                    $orderDetailsQuery = "
                        SELECT 
                            o.*,
                            c.first_name as customer_first_name,
                            c.last_name as customer_last_name,
                            u.username as created_by
                        FROM 
                            orders o
                        LEFT JOIN 
                            customers c ON o.customer_id = c.customer_id
                        LEFT JOIN 
                            users u ON o.user_id = u.user_id
                        WHERE 
                            o.order_id = ?
                    ";
                    
                    $order = executeQuery($connection, $orderDetailsQuery, [$orderId])[0];
                    
                    // Get order items
                    $itemsQuery = "
                        SELECT 
                            oi.*,
                            p.sku,
                            p.name as product_name
                        FROM 
                            order_items oi
                        JOIN 
                            products p ON oi.product_id = p.product_id
                        WHERE 
                            oi.order_id = ?
                    ";
                    
                    $items = executeQuery($connection, $itemsQuery, [$orderId]);
                    
                    // Get payments
                    $paymentsQuery = "
                        SELECT * FROM payments WHERE order_id = ?
                    ";
                    
                    $payments = executeQuery($connection, $paymentsQuery, [$orderId]);
                    
                    // Combine all data
                    $order['items'] = $items;
                    $order['payments'] = $payments;
                    
                    return [
                        'statusCode' => 201,
                        'response' => $order
                    ];
                });
            } catch (DatabaseException $e) {
                sendResponse(500, ['error' => 'Error creating order: ' . $e->getMessage()]);
            }
            break;
            
        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleDashboard(string $method): void {
    global $con;
    
    if ($method !== 'GET') {
        sendResponse(405, ['error' => 'Method not allowed']);
    }
    
    $user = authenticate();
    if (!$user) {
        sendResponse(401, ['error' => 'Unauthorized']);
    }
    
    try {
        $response = [
            'summary' => [],
            'recent_orders' => [],
            'low_stock_products' => [],
            'sales_chart' => []
        ];
        
        // Summary statistics
        $summaryQuery = "
            SELECT
                (SELECT COUNT(*) FROM orders WHERE order_status = 'completed') as completed_orders,
                (SELECT COUNT(*) FROM orders WHERE order_status = 'pending') as pending_orders,
                (SELECT COUNT(*) FROM products) as total_products,
                (SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level) as low_stock_count,
                (SELECT SUM(total_amount) FROM orders WHERE order_status = 'completed') as total_sales
        ";
        
        $summaryResult = executeQuery($con, $summaryQuery);
        $response['summary'] = $summaryResult[0];
        
        // Recent orders
        $recentOrdersQuery = "
            SELECT 
                o.*,
                c.first_name as customer_first_name,
                c.last_name as customer_last_name
            FROM 
                orders o
            LEFT JOIN 
                customers c ON o.customer_id = c.customer_id
            ORDER BY 
                o.order_date DESC
            LIMIT 5
        ";
        
        $response['recent_orders'] = executeQuery($con, $recentOrdersQuery);
        
        // Low stock products
        $lowStockQuery = "
            SELECT 
                p.*,
                c.name as category_name
            FROM 
                products p
            LEFT JOIN 
                categories c ON p.category_id = c.category_id
            WHERE 
                p.stock_quantity <= p.reorder_level
                AND p.is_active = 1
            ORDER BY 
                p.stock_quantity ASC
            LIMIT 10
        ";
        
        $response['low_stock_products'] = executeQuery($con, $lowStockQuery);
        
        // Sales chart data (last 7 days)
        $salesChartQuery = "
            SELECT 
                DATE(order_date) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as total_sales
            FROM 
                orders
            WHERE 
                order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                AND order_status = 'completed'
            GROUP BY 
                DATE(order_date)
            ORDER BY 
                date ASC
        ";
        
        $response['sales_chart'] = executeQuery($con, $salesChartQuery);
        
        sendResponse(200, $response);
    } catch (DatabaseException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Route the request to the appropriate handler
switch ($resource) {
    case 'products':
        handleProducts($method, $id);
        break;
        
    case 'suppliers':
        handleSuppliers($method, $id);
        break;
        
    case 'orders':
        handleOrders($method, $id);
        break;
        
    case 'dashboard':
        handleDashboard($method);
        break;
        
    case 'auth':
        // Auth would be handled here
        sendResponse(501, ['error' => 'Authentication API not implemented yet']);
        break;
        
    default:
        sendResponse(404, ['error' => 'Resource not found']);
} 