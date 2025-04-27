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

// Sample products data (in a real application, this would come from the database)
$products = [
    [
        'id' => 1, 
        'sku' => 'MAG-MLK-200ML', 
        'name' => 'Full Cream Milk', 
        'brand' => 'Magnolia', 
        'category' => 'Dairy', 
        'stock' => '20 pcs.', 
        'expiry' => '3/28/2025', 
        'description' => 'Recombined Full Cream Milk. UHT-Processed long life. No need to go through traffic just to get your favorite premium food and beverage products!'
    ],
    [
        'id' => 2, 
        'sku' => 'CHO-BAR-100G', 
        'name' => 'Chocolate Bar', 
        'brand' => 'CacaoDelight', 
        'category' => 'Confectionery', 
        'stock' => '45 pcs.', 
        'expiry' => '5/15/2024', 
        'description' => 'Premium dark chocolate bar made with 70% cocoa. Rich flavor with notes of fruit and a smooth finish.'
    ],
    [
        'id' => 3, 
        'sku' => 'COF-ARAB-500G', 
        'name' => 'Arabica Coffee', 
        'brand' => 'MountainBean', 
        'category' => 'Beverages', 
        'stock' => '32 pcs.', 
        'expiry' => '9/10/2024', 
        'description' => 'Premium roasted arabica coffee beans. Single-origin from highland plantations for a rich, aromatic brew.'
    ],
    [
        'id' => 4, 
        'sku' => 'RICE-JAS-5KG', 
        'name' => 'Jasmine Rice', 
        'brand' => 'GoldenHarvest', 
        'category' => 'Grains', 
        'stock' => '15 pcs.', 
        'expiry' => '12/20/2025', 
        'description' => 'Premium jasmine rice with fragrant aroma. Perfect for everyday meals and special occasions.'
    ],
    [
        'id' => 5, 
        'sku' => 'OIL-OLIVE-750ML', 
        'name' => 'Extra Virgin Olive Oil', 
        'brand' => 'MediterraGold', 
        'category' => 'Oils', 
        'stock' => '28 pcs.', 
        'expiry' => '6/15/2024', 
        'description' => 'Cold-pressed extra virgin olive oil. High quality with low acidity and rich flavor for culinary excellence.'
    ]
];
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
        
        /* Products Section */
        .products-container {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .products-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
        }
        
        .products-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-add-product {
            background-color: var(--highlight);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .btn-add-product i {
            margin-right: 8px;
        }
        
        .btn-add-product:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 600px;
            margin: 0 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            background-color: white;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-filter {
            background-color: var(--highlight);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background-color: #e67e22;
        }
        
        /* Products Table */
        .products-table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .products-table th {
            background-color: var(--primary-bg);
            color: white;
            text-align: left;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .products-table tr {
            transition: all 0.3s;
        }
        
        .products-table tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.5);
        }
        
        .products-table tr:hover {
            background-color: rgba(247, 143, 64, 0.1);
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        
        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
        }
        
        .action-cell {
            display: flex;
            gap: 5px;
        }
        
        .btn-edit, .btn-delete {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: #3498db;
        }
        
        .btn-delete {
            background-color: #e74c3c;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        /* Modal */
        .modal-content {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .modal-header {
            background-color: var(--primary-bg);
            color: white;
            border-bottom: none;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background-color: var(--highlight);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #e67e22;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                transition: width 0.3s;
            }
            
            .sidebar-brand {
                justify-content: center;
                padding: 10px;
            }
            
            .sidebar-brand span, 
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .signout {
                padding: 0;
            }
            
            .signout a {
                justify-content: center;
            }
            
            .signout a i {
                margin-right: 0;
            }
            
            .products-actions {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
                max-width: none;
                margin: 10px 0;
            }
            
            .filter-actions {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-briefcase briefcase-icon"></i>
            <span>BUSINESS IMS</span>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="../dashboard/dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../products/products.php" class="active">
                    <i class="fas fa-box"></i>
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
                    <span>POS</span>
                </a>
            </li>
            <li>
            <a href="../ai-insights/ai-insights.php">
                    <i class="fas fa-brain"></i>
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
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Products</h1>
            <div class="user-info">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="user-profile">
                    <i class="fas fa-user"></i>
                    <span class="ms-2"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Products Management -->
        <div class="products-container">
            <h2 class="products-title">PRODUCTS</h2>
            
            <div class="products-actions">
                <button class="btn-add-product" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> ADD PRODUCT
                </button>
                
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search" id="searchProducts">
                </div>
                
                <div class="filter-actions">
                    <button class="btn-filter" title="Sort Ascending">
                        <i class="fas fa-sort-amount-up"></i>
                    </button>
                    <button class="btn-filter" title="Sort Descending">
                        <i class="fas fa-sort-amount-down"></i>
                    </button>
                    <button class="btn-filter" title="Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
            
            <div class="products-table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Expiry</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                            <td><?php echo htmlspecialchars($product['expiry']); ?></td>
                            <td class="description-cell"><?php echo htmlspecialchars($product['description']); ?></td>
                            <td class="action-cell">
                                <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" class="row g-3">
                        <div class="col-md-6">
                            <label for="productSKU" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="productSKU" required>
                        </div>
                        <div class="col-md-6">
                            <label for="productName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="productName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="productBrand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="productBrand" required>
                        </div>
                        <div class="col-md-6">
                            <label for="productCategory" class="form-label">Category</label>
                            <select class="form-select" id="productCategory" required>
                                <option value="">Select Category</option>
                                <option value="Dairy">Dairy</option>
                                <option value="Beverages">Beverages</option>
                                <option value="Confectionery">Confectionery</option>
                                <option value="Grains">Grains</option>
                                <option value="Oils">Oils</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Produce">Produce</option>
                                <option value="Meat">Meat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="productStock" class="form-label">Stock</label>
                            <input type="text" class="form-control" id="productStock" required>
                        </div>
                        <div class="col-md-6">
                            <label for="productExpiry" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="productExpiry" required>
                        </div>
                        <div class="col-12">
                            <label for="productDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="productDescription" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Edit Product
        function editProduct(id) {
            // In a real application, you would fetch the product data from the server
            alert('Edit product with ID: ' + id);
            // Then open a modal with the data pre-filled
        }
        
        // Delete Product
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                // In a real application, you would send a delete request to the server
                alert('Delete product with ID: ' + id);
            }
        }
        
        // Save New Product
        function saveProduct() {
            const sku = document.getElementById('productSKU').value;
            const name = document.getElementById('productName').value;
            const brand = document.getElementById('productBrand').value;
            const category = document.getElementById('productCategory').value;
            const stock = document.getElementById('productStock').value;
            const expiry = document.getElementById('productExpiry').value;
            const description = document.getElementById('productDescription').value;
            
            if (!sku || !name || !brand || !category || !stock || !expiry || !description) {
                alert('Please fill all required fields');
                return;
            }
            
            // In a real application, you would send this data to the server
            alert('Product added successfully!');
            document.getElementById('addProductForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            modal.hide();
        }
        
        // Search Products
        document.getElementById('searchProducts').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTableBody tr');
            
            rows.forEach(row => {
                const productName = row.cells[2].textContent.toLowerCase();
                const sku = row.cells[1].textContent.toLowerCase();
                const brand = row.cells[3].textContent.toLowerCase();
                const category = row.cells[4].textContent.toLowerCase();
                
                if (productName.includes(searchTerm) || sku.includes(searchTerm) || 
                    brand.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?>
