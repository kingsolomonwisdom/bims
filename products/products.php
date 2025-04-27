<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once("../connection.php");

// Ensure the required tables exist
$tables = [
    "categories" => "
        CREATE TABLE IF NOT EXISTS categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    "suppliers" => "
        CREATE TABLE IF NOT EXISTS suppliers (
            supplier_id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(100) NOT NULL,
            contact_name VARCHAR(100),
            contact_email VARCHAR(100),
            contact_phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            state VARCHAR(50),
            postal_code VARCHAR(20),
            country VARCHAR(50),
            tax_id VARCHAR(50),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    "products" => "
        CREATE TABLE IF NOT EXISTS products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            brand VARCHAR(50),
            category_id INT,
            supplier_id INT,
            purchase_price DECIMAL(10,2) NOT NULL,
            selling_price DECIMAL(10,2) NOT NULL,
            discount_price DECIMAL(10,2),
            tax_rate DECIMAL(5,2) DEFAULT 0,
            stock_quantity INT NOT NULL DEFAULT 0,
            reorder_level INT DEFAULT 5,
            expiry_date DATE,
            image_url VARCHAR(255),
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL
        )
    "
];

foreach ($tables as $table_name => $query) {
    if ($con->query($query) !== TRUE) {
        error_log("Error creating $table_name table: " . $con->error);
    }
}

// Check if products table is empty, and add some sample data if needed
$checkProducts = $con->query("SELECT COUNT(*) as count FROM products");
$productCount = $checkProducts->fetch_assoc()['count'];

if ($productCount == 0) {
    // Insert sample category if none exist
    $checkCategories = $con->query("SELECT COUNT(*) as count FROM categories");
    if ($checkCategories->fetch_assoc()['count'] == 0) {
        $con->query("INSERT INTO categories (name, description) VALUES 
            ('Electronics', 'Electronic devices and accessories'),
            ('Clothing', 'Apparel and fashion items'),
            ('Office Supplies', 'Stationery and office equipment')");
    }
    
    // Insert sample supplier if none exist
    $checkSuppliers = $con->query("SELECT COUNT(*) as count FROM suppliers");
    if ($checkSuppliers->fetch_assoc()['count'] == 0) {
        $con->query("INSERT INTO suppliers (company_name, contact_name, contact_email, status) VALUES 
            ('TechSupply Inc.', 'John Smith', 'john@techsupply.com', 'active'),
            ('Fashion Wholesale', 'Mary Johnson', 'mary@fashionws.com', 'active'),
            ('Office Depot', 'Robert Brown', 'robert@officedepot.com', 'active')");
    }
    
    // Get category and supplier IDs
    $categories = $con->query("SELECT category_id, name FROM categories LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    $suppliers = $con->query("SELECT supplier_id, company_name FROM suppliers LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    
    // Map categories and suppliers by name for easy access
    $categoryMap = [];
    foreach ($categories as $category) {
        $categoryMap[$category['name']] = $category['category_id'];
    }
    
    $supplierMap = [];
    foreach ($suppliers as $supplier) {
        $supplierMap[$supplier['company_name']] = $supplier['supplier_id'];
    }
    
    // Insert sample products
    $sampleProducts = [
        [
            'sku' => 'TECH-001',
            'name' => 'Smartphone X1',
            'description' => 'Latest smartphone with high-end features',
            'brand' => 'TechBrand',
            'category_id' => $categoryMap['Electronics'] ?? null,
            'supplier_id' => $supplierMap['TechSupply Inc.'] ?? null,
            'purchase_price' => 300.00,
            'selling_price' => 499.99,
            'stock_quantity' => 25,
            'reorder_level' => 5
        ],
        [
            'sku' => 'TECH-002',
            'name' => 'Laptop Pro',
            'description' => 'Professional laptop for business use',
            'brand' => 'CompTech',
            'category_id' => $categoryMap['Electronics'] ?? null,
            'supplier_id' => $supplierMap['TechSupply Inc.'] ?? null,
            'purchase_price' => 700.00,
            'selling_price' => 1199.99,
            'stock_quantity' => 10,
            'reorder_level' => 3
        ],
        [
            'sku' => 'CLOTH-001',
            'name' => 'T-Shirt Basic',
            'description' => 'Cotton t-shirt in various colors',
            'brand' => 'BasicWear',
            'category_id' => $categoryMap['Clothing'] ?? null,
            'supplier_id' => $supplierMap['Fashion Wholesale'] ?? null,
            'purchase_price' => 5.00,
            'selling_price' => 15.99,
            'stock_quantity' => 100,
            'reorder_level' => 20
        ],
        [
            'sku' => 'OFF-001',
            'name' => 'Notebook Premium',
            'description' => 'High-quality notebook with premium paper',
            'brand' => 'OfficePro',
            'category_id' => $categoryMap['Office Supplies'] ?? null,
            'supplier_id' => $supplierMap['Office Depot'] ?? null,
            'purchase_price' => 2.50,
            'selling_price' => 6.99,
            'stock_quantity' => 200,
            'reorder_level' => 50
        ]
    ];
    
    foreach ($sampleProducts as $product) {
        addProduct($con, $product);
    }
}

// Get user information
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'User';

/**
 * Pure function to get products with their categories and suppliers
 * 
 * @param mysqli $connection Database connection
 * @return array List of products with their details
 */
function getProducts(mysqli $connection): array {
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
                p.is_active = 1
            ORDER BY 
                p.name ASC
        ";
        
        return executeQuery($connection, $query);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Pure function to get all categories
 * 
 * @param mysqli $connection Database connection
 * @return array List of categories
 */
function getCategories(mysqli $connection): array {
    try {
        $query = "SELECT * FROM categories ORDER BY name ASC";
        return executeQuery($connection, $query);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Pure function to get all suppliers
 * 
 * @param mysqli $connection Database connection
 * @return array List of suppliers
 */
function getSuppliers(mysqli $connection): array {
    try {
        $query = "SELECT * FROM suppliers WHERE status = 'active' ORDER BY company_name ASC";
        return executeQuery($connection, $query);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Generate a unique SKU for a product
 * 
 * @param mysqli $connection Database connection
 * @param string $name Product name
 * @param string $category Category name (optional)
 * @return string Unique SKU
 */
function generateSKU(mysqli $connection, string $name, string $category = ''): string {
    // Get category prefix or use first letter of name
    $prefix = '';
    if (!empty($category)) {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $category), 0, 3));
    } else {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3));
    }
    
    // Ensure prefix is at least 2 characters
    if (strlen($prefix) < 2) {
        $prefix = str_pad($prefix, 2, 'X');
    }
    
    // Get highest sequential number for this prefix
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(sku, '-', -1) AS UNSIGNED)) as max_num 
              FROM products 
              WHERE sku LIKE ?";
    
    $result = executeQuery($connection, $query, [$prefix . '-%']);
    $maxNum = $result[0]['max_num'] ?? 0;
    
    // Create new SKU with incremented sequential number
    $newNum = $maxNum + 1;
    return $prefix . '-' . str_pad($newNum, 3, '0', STR_PAD_LEFT);
}

/**
 * Add a new product
 * 
 * @param mysqli $connection Database connection
 * @param array $productData Product data to insert
 * @return int|bool The inserted product ID or false on failure
 */
function addProduct(mysqli $connection, array $productData): int|bool {
    try {
        // Generate SKU if not provided
        if (empty($productData['sku'])) {
            // Get category name if category_id is provided
            $categoryName = '';
            if (!empty($productData['category_id'])) {
                $categoryResult = executeQuery($connection, 
                    "SELECT name FROM categories WHERE category_id = ?", 
                    [$productData['category_id']]
                );
                if (!empty($categoryResult)) {
                    $categoryName = $categoryResult[0]['name'];
                }
            }
            
            $productData['sku'] = generateSKU($connection, $productData['name'], $categoryName);
        }
        
        $query = "
            INSERT INTO products (
                sku, name, description, brand, category_id, supplier_id,
                purchase_price, selling_price, discount_price, tax_rate,
                stock_quantity, reorder_level, expiry_date, image_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $productData['sku'],
            $productData['name'],
            $productData['description'] ?? '',
            $productData['brand'] ?? '',
            $productData['category_id'] ?? null,
            $productData['supplier_id'] ?? null,
            $productData['purchase_price'],
            $productData['selling_price'],
            $productData['discount_price'] ?? null,
            $productData['tax_rate'] ?? 0,
            $productData['stock_quantity'] ?? 0,
            $productData['reorder_level'] ?? 5,
            $productData['expiry_date'] ?? null,
            $productData['image_url'] ?? null
        ];
        
        $result = executeQuery($connection, $query, $params);
        return $result['insert_id'] ?? false;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Update an existing product
 * 
 * @param mysqli $connection Database connection
 * @param int $productId Product ID to update
 * @param array $productData Updated product data
 * @return bool Whether the update was successful
 */
function updateProduct(mysqli $connection, int $productId, array $productData): bool {
    try {
        // Get existing SKU if not provided
        if (empty($productData['sku'])) {
            $existingProduct = executeQuery($connection, 
                "SELECT sku FROM products WHERE product_id = ?", 
                [$productId]
            );
            
            if (!empty($existingProduct)) {
                $productData['sku'] = $existingProduct[0]['sku'];
            } else {
                // Generate a new SKU if necessary
                $categoryName = '';
                if (!empty($productData['category_id'])) {
                    $categoryResult = executeQuery($connection, 
                        "SELECT name FROM categories WHERE category_id = ?", 
                        [$productData['category_id']]
                    );
                    if (!empty($categoryResult)) {
                        $categoryName = $categoryResult[0]['name'];
                    }
                }
                
                $productData['sku'] = generateSKU($connection, $productData['name'], $categoryName);
            }
        }
        
        $query = "
            UPDATE products SET
                sku = ?,
                name = ?,
                description = ?,
                brand = ?,
                category_id = ?,
                supplier_id = ?,
                purchase_price = ?,
                selling_price = ?,
                discount_price = ?,
                tax_rate = ?,
                stock_quantity = ?,
                reorder_level = ?,
                expiry_date = ?,
                image_url = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE product_id = ?
        ";
        
        $params = [
            $productData['sku'],
            $productData['name'],
            $productData['description'] ?? '',
            $productData['brand'] ?? '',
            $productData['category_id'] ?? null,
            $productData['supplier_id'] ?? null,
            $productData['purchase_price'],
            $productData['selling_price'],
            $productData['discount_price'] ?? null,
            $productData['tax_rate'] ?? 0,
            $productData['stock_quantity'] ?? 0,
            $productData['reorder_level'] ?? 5,
            $productData['expiry_date'] ?? null,
            $productData['image_url'] ?? null,
            $productId
        ];
        
        $result = executeQuery($connection, $query, $params);
        return ($result['affected_rows'] > 0);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Delete a product (soft delete by setting is_active to 0)
 * 
 * @param mysqli $connection Database connection
 * @param int $productId Product ID to delete
 * @return bool Whether the deletion was successful
 */
function deleteProduct(mysqli $connection, int $productId): bool {
    try {
        $query = "UPDATE products SET is_active = 0 WHERE product_id = ?";
        $result = executeQuery($connection, $query, [$productId]);
        return ($result['affected_rows'] > 0);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Handle product image upload
 * 
 * @param array $file The $_FILES array element for the uploaded file
 * @return string|bool The path to the uploaded image or false on failure
 */
function handleImageUpload(array $file): string|bool {
    // Check if a file was uploaded
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileName = uniqid('product_') . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return false;
}

// Handle form submission for adding/editing/deleting products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $productData = $_POST;
                
                // Handle image upload if provided
                if (isset($_FILES['product_image']) && $_FILES['product_image']['name']) {
                    $imagePath = handleImageUpload($_FILES['product_image']);
                    if ($imagePath) {
                        $productData['image_url'] = $imagePath;
                    }
                }
                
                if (addProduct($con, $productData)) {
                    $_SESSION['success_message'] = "Product added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add product.";
                }
                break;
                
            case 'edit':
                if (isset($_POST['product_id'])) {
                    $productData = $_POST;
                    
                    // Handle image upload if provided
                    if (isset($_FILES['product_image']) && $_FILES['product_image']['name']) {
                        $imagePath = handleImageUpload($_FILES['product_image']);
                        if ($imagePath) {
                            $productData['image_url'] = $imagePath;
                        }
                    }
                    
                    if (updateProduct($con, (int)$_POST['product_id'], $productData)) {
                        $_SESSION['success_message'] = "Product updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Failed to update product.";
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid product ID.";
                }
                break;
                
            case 'delete':
                if (isset($_POST['product_id']) && deleteProduct($con, (int)$_POST['product_id'])) {
                    $_SESSION['success_message'] = "Product deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to delete product.";
                }
                break;
        }
        
        // Redirect to avoid form resubmission
        header("Location: products.php");
        exit();
    }
}

// Fetch products, categories, and suppliers
$products = getProducts($con);
$categories = getCategories($con);
$suppliers = getSuppliers($con);

// Check for success and error messages
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;

// Clear session messages
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Business IMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-bg: #2E251C;
            --secondary-bg: #F5E7D1;
            --highlight: #F78F40;
            --text-light: #FFFFFF;
            --text-dark: #2E251C;
            --accent: #D8C3A5;
            --body-bg: #FFF8F1;
            --card-bg: #FFFFFF;
        }
        
        [data-theme="dark"] {
            --primary-bg: #1a1a1a;
            --secondary-bg: #333333;
            --highlight: #F78F40;
            --text-light: #FFFFFF;
            --text-dark: #f4f4f4;
            --accent: #444444;
            --body-bg: #121212;
            --card-bg: #2c2c2c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--body-bg);
            color: var(--text-dark);
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 275px;
            height: 100%;
            background-color: var(--primary-bg);
            color: var(--text-light);
            padding-top: 20px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            font-size: 24px;
            font-weight: bold;
            padding: 0 20px 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand .briefcase-icon {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 15px 20px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--highlight);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 18px;
            width: 25px;
        }
        
        .sidebar-menu a span {
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .signout {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }
        
        .signout a {
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .signout a:hover {
            color: var(--highlight);
        }
        
        .signout a i {
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 275px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Header */
        .header {
            background-color: var(--secondary-bg);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-dark);
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .notification-icon, .user-profile {
            margin-left: 20px;
            cursor: pointer;
            position: relative;
        }
        
        .notification-icon i, .user-profile i {
            font-size: 20px;
            color: var(--text-dark);
        }
        
        /* Products Table */
        .product-container {
            background-color: #F5E7D1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .product-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-dark);
            margin: 0;
        }
        
        .add-product-btn {
            background-color: var(--highlight);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .add-product-btn i {
            margin-right: 8px;
        }
        
        .add-product-btn:hover {
            background-color: #e67e38;
            transform: translateY(-2px);
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .product-table th {
            background-color: var(--primary-bg);
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-weight: 500;
        }
        
        .product-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .product-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-table tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }
        
        .product-table tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: #4a90e2;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #357abd;
        }
        
        .btn-delete {
            background-color: #e25c5c;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c94444;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                width: 250px;
            }
            
            .main-content {
                margin-left: 250px;
            }
        }
        
        @media (max-width: 767px) {
            .sidebar {
                left: -250px;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="briefcase-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <span>Business IMS</span>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="../dashboard/dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products.php" class="active">
                    <i class="fas fa-boxes"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="../suppliers/suppliers.php">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>
            <li>
                <a href="../pos/pos.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Point of Sale</span>
                </a>
            </li>
            <li>
                <a href="../ai-insights/insights.php">
                    <i class="fas fa-lightbulb"></i>
                    <span>AI Insights</span>
                </a>
            </li>
            <li>
                <a href="../settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="signout">
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h2 class="page-title">Products Management</h2>
            
            <div class="user-info">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                
                <div class="user-profile">
                    <i class="fas fa-user-circle"></i>
                    <span class="ms-2"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Products List -->
        <div class="product-container">
            <div class="product-header">
                <h3 class="product-title">Product List</h3>
                
                <button class="add-product-btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add Product
                    </button>
            </div>
            
            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">No products found. Add your first product!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php 
                                    // Determine stock status
                                    $stockStatus = 'In Stock';
                                    $statusClass = 'text-success';
                                    
                                    if ($product['stock_quantity'] <= 0) {
                                        $stockStatus = 'Out of Stock';
                                        $statusClass = 'text-danger';
                                    } elseif ($product['stock_quantity'] <= $product['reorder_level']) {
                                        $stockStatus = 'Low Stock';
                                        $statusClass = 'text-warning';
                                    }
                                ?>
                                <tr>
                                <td>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo number_format($product['selling_price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td class="<?php echo $statusClass; ?>"><?php echo $stockStatus; ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editProductModal" 
                                                    data-product-id="<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                            <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#deleteProductModal"
                                                    data-product-id="<?php echo $product['product_id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                        </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="products.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="text-muted">SKU will be auto-generated</small>
                            </div>
                            <div class="col-md-6">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select class="form-select" id="supplier_id" name="supplier_id">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="purchase_price" class="form-label">Purchase Price*</label>
                                <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="selling_price" class="form-label">Selling Price*</label>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="discount_price" class="form-label">Discount Price</label>
                                <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" value="10">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="5">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                            </div>
                            <div class="col-md-6">
                                <label for="product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="products.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="edit_sku" name="sku" readonly>
                                <small class="text-muted">Auto-generated (cannot be changed)</small>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="edit_brand" name="brand">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_supplier_id" class="form-label">Supplier</label>
                                <select class="form-select" id="edit_supplier_id" name="supplier_id">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="edit_expiry_date" name="expiry_date">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_purchase_price" class="form-label">Purchase Price*</label>
                                <input type="number" class="form-control" id="edit_purchase_price" name="purchase_price" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_selling_price" class="form-label">Selling Price*</label>
                                <input type="number" class="form-control" id="edit_selling_price" name="selling_price" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_discount_price" class="form-label">Discount Price</label>
                                <input type="number" class="form-control" id="edit_discount_price" name="discount_price" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="edit_tax_rate" name="tax_rate" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="edit_reorder_level" name="reorder_level">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="edit_product_image" name="product_image">
                            <div id="current_image_container" class="mt-2" style="display: none;">
                                <p>Current image: <span id="current_image_name"></span></p>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the product: <strong id="delete_product_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form action="products.php" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="delete_product_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // JavaScript to handle edit and delete modals
        document.addEventListener('DOMContentLoaded', function() {
            // Edit product functionality
            const editButtons = document.querySelectorAll('.btn-edit');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    
                    // AJAX request to get product data
                    fetch(`get_product.php?id=${productId}`)
                        .then(response => response.json())
                        .then(product => {
                            // Populate the edit form with product data
                            document.getElementById('edit_product_id').value = product.product_id;
                            document.getElementById('edit_sku').value = product.sku;
                            document.getElementById('edit_name').value = product.name;
                            document.getElementById('edit_brand').value = product.brand || '';
                            document.getElementById('edit_category_id').value = product.category_id || '';
                            document.getElementById('edit_supplier_id').value = product.supplier_id || '';
                            document.getElementById('edit_expiry_date').value = product.expiry_date || '';
                            document.getElementById('edit_purchase_price').value = product.purchase_price;
                            document.getElementById('edit_selling_price').value = product.selling_price;
                            document.getElementById('edit_discount_price').value = product.discount_price || '';
                            document.getElementById('edit_tax_rate').value = product.tax_rate || 0;
                            document.getElementById('edit_stock_quantity').value = product.stock_quantity || 0;
                            document.getElementById('edit_reorder_level').value = product.reorder_level || 5;
                            document.getElementById('edit_description').value = product.description || '';
                            
                            // Show current image if exists
                            if (product.image_url) {
                                document.getElementById('current_image_container').style.display = 'block';
                                document.getElementById('current_image_name').textContent = product.image_url.split('/').pop();
                            } else {
                                document.getElementById('current_image_container').style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching product data:', error);
                            alert('Failed to load product data. Please try again.');
                        });
                });
            });
            
            // Delete product functionality
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    
                    // Populate the delete confirmation modal
                    document.getElementById('delete_product_id').value = productId;
                    document.getElementById('delete_product_name').textContent = productName;
                });
            });
        });
    </script>
</body>
</html>
