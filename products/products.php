<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once("../connection.php");

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
 * Add a new product
 * 
 * @param mysqli $connection Database connection
 * @param array $productData Product data to insert
 * @return int|bool The inserted product ID or false on failure
 */
function addProduct(mysqli $connection, array $productData): int|bool {
    try {
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

// Handle form submission for adding/editing/deleting products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (addProduct($con, $_POST)) {
                    $_SESSION['success_message'] = "Product added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add product.";
                }
                break;
                
            case 'edit':
                if (isset($_POST['product_id']) && updateProduct($con, (int)$_POST['product_id'], $_POST)) {
                    $_SESSION['success_message'] = "Product updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update product.";
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
            --light-bg: #FFF8F1;
            --text-light: #FFFFFF;
            --text-dark: #2E251C;
            --accent: #D8C3A5;
            --gray-bg: #e6e2dc;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-bg);
            overflow-x: hidden;
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
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No products found.</td>
                            </tr>
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
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" required>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand">
                            </div>
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
                        </div>
                        
                        <div class="row mb-3">
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
                            <div class="col-md-6">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="purchase_price" class="form-label">Purchase Price</label>
                                <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="selling_price" class="form-label">Selling Price</label>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="discount_price" class="form-label">Discount Price (Optional)</label>
                                <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" value="10">
                            </div>
                            <div class="col-md-4">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="0">
                            </div>
                            <div class="col-md-4">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="5">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="product_image" name="product_image">
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
    
    <script>
        // JavaScript to handle edit and delete modals
        document.addEventListener('DOMContentLoaded', function() {
            // Edit product functionality
            const editButtons = document.querySelectorAll('.btn-edit');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    // Here you would typically fetch the product details via AJAX and populate the edit form
                    console.log(`Edit product with ID: ${productId}`);
                });
            });
            
            // Delete product functionality
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    
                    // Here you would populate the delete confirmation modal
                    console.log(`Delete product with ID: ${productId}, Name: ${productName}`);
                });
            });
        });
    </script>
</body>
</html>
