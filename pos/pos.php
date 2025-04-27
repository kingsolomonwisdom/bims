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
    ['id' => 1, 'name' => 'Full Cream Milk', 'price' => 50.00, 'image' => 'noodles.png'],
    ['id' => 2, 'name' => 'Coffee Beans', 'price' => 75.50, 'image' => 'noodles.png'],
    ['id' => 3, 'name' => 'Brown Sugar', 'price' => 30.25, 'image' => 'noodles.png'],
    ['id' => 4, 'name' => 'White Rice', 'price' => 45.75, 'image' => 'noodles.png'],
    ['id' => 5, 'name' => 'Whole Wheat Bread', 'price' => 35.00, 'image' => 'noodles.png'],
    ['id' => 6, 'name' => 'Fresh Eggs', 'price' => 60.00, 'image' => 'noodles.png'],
    ['id' => 7, 'name' => 'Chicken Meat', 'price' => 120.00, 'image' => 'noodles.png'],
    ['id' => 8, 'name' => 'Tomato Sauce', 'price' => 25.50, 'image' => 'noodles.png'],
    ['id' => 9, 'name' => 'Cooking Oil', 'price' => 85.75, 'image' => 'noodles.png'],
    ['id' => 10, 'name' => 'Instant Noodles', 'price' => 15.00, 'image' => 'noodles.png'],
    ['id' => 11, 'name' => 'Soy Sauce', 'price' => 40.25, 'image' => 'noodles.png'],
    ['id' => 12, 'name' => 'Canned Tuna', 'price' => 55.50, 'image' => 'noodles.png']
];

// Sample tax rate
$taxRate = 0.10; // 10%
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Business IMS</title>
    
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
        
        /* POS System */
        .pos-container {
            display: flex;
            height: calc(100vh - 40px);
            gap: 20px;
        }
        
        /* Product Grid */
        .product-grid {
            flex: 2;
            background-color: var(--secondary-bg);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .search-bar {
            padding: 15px;
            background-color: white;
            border-bottom: 1px solid #ddd;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 15px;
            overflow-y: auto;
            height: 100%;
        }
        
        .product-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }
        
        .product-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .product-price {
            font-size: 14px;
            color: var(--highlight);
            font-weight: 600;
        }
        
        /* Order Section */
        .order-section {
            flex: 1;
            background-color: var(--secondary-bg);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .order-header {
            padding: 15px;
            background-color: var(--gray-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-action {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-action:hover {
            background-color: #5a6268;
        }
        
        .btn-customer {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-customer:hover {
            background-color: #5a6268;
        }
        
        .order-items {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .order-item {
            background-color: var(--gray-bg);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .order-item-left {
            display: flex;
            align-items: center;
        }
        
        .item-id {
            background-color: var(--primary-bg);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .item-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .item-price {
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-remove {
            background-color: transparent;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }
        
        .btn-remove:hover {
            color: #c0392b;
        }
        
        .item-details {
            padding: 5px 10px;
            margin-top: 5px;
            background-color: rgba(0,0,0,0.05);
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-label {
            font-size: 12px;
            color: #666;
        }
        
        .detail-input {
            width: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            text-align: right;
        }
        
        .order-summary {
            padding: 15px;
            background-color: white;
            border-top: 1px solid #ddd;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-label {
            font-weight: 500;
        }
        
        .summary-value {
            font-weight: 600;
        }
        
        .total-amount {
            color: var(--highlight);
            font-size: 18px;
            font-weight: 700;
        }
        
        .order-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-hold, .btn-proceed {
            flex: 1;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-hold {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-proceed {
            background-color: var(--highlight);
            color: white;
        }
        
        .btn-hold:hover {
            background-color: #5a6268;
        }
        
        .btn-proceed:hover {
            background-color: #e67e22;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .products {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
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
            
            .products {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .pos-container {
                flex-direction: column;
                height: auto;
            }
            
            .order-section {
                height: 100vh;
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
                <a href="../products/products.php">
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
                <a href="../pos/pos.php" class="active">
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
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="pos-container">
            <!-- Product Grid -->
            <div class="product-grid">
                <div class="search-bar">
                    <input type="text" class="search-input" placeholder="Search products..." id="searchProducts">
                </div>
                <div class="products" id="productGrid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-item" onclick="addToOrder(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['price']; ?>)">
                        <img src="../assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                        <div class="product-name"><?php echo $product['name']; ?></div>
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Section -->
            <div class="order-section">
                <div class="order-header">
                    <button class="btn-customer">
                        <i class="fas fa-plus me-2"></i> Add Customer
                    </button>
                    <div class="order-actions">
                        <button class="btn-action" title="Add Item">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn-action" title="Apply Discount">
                            <i class="fas fa-percentage"></i>
                        </button>
                        <button class="btn-action" title="Settings">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
                
                <div class="order-items" id="orderItemsList">
                    <!-- Order items will be added dynamically using JavaScript -->
                </div>
                
                <div class="order-summary">
                    <div class="summary-item">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value" id="subtotal">$0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Tax:</span>
                        <span class="summary-value" id="tax">$0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Payable Amount:</span>
                        <span class="summary-value total-amount" id="total">$0.00</span>
                    </div>
                    
                    <div class="order-buttons">
                        <button class="btn-hold">Hold Order</button>
                        <button class="btn-proceed">Proceed</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Store cart items
        let cart = [];
        const taxRate = <?php echo $taxRate; ?>;
        
        // Function to add item to order
        function addToOrder(id, name, price) {
            // Check if the item is already in the cart
            const existingItemIndex = cart.findIndex(item => item.id === id);
            
            if (existingItemIndex !== -1) {
                // If item exists, increment quantity
                cart[existingItemIndex].quantity += 1;
                cart[existingItemIndex].total = cart[existingItemIndex].quantity * cart[existingItemIndex].price;
            } else {
                // Otherwise add new item to cart
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    discount: 0,
                    total: price
                });
            }
            
            // Update the order display
            updateOrderDisplay();
        }
        
        // Function to remove item from order
        function removeFromOrder(id) {
            cart = cart.filter(item => item.id !== id);
            updateOrderDisplay();
        }
        
        // Function to update quantity
        function updateQuantity(id, quantity) {
            const itemIndex = cart.findIndex(item => item.id === id);
            
            if (itemIndex !== -1) {
                cart[itemIndex].quantity = parseInt(quantity);
                cart[itemIndex].total = cart[itemIndex].quantity * cart[itemIndex].price;
                updateOrderDisplay();
            }
        }
        
        // Function to update discount
        function updateDiscount(id, discount) {
            const itemIndex = cart.findIndex(item => item.id === id);
            
            if (itemIndex !== -1) {
                cart[itemIndex].discount = parseFloat(discount);
                cart[itemIndex].total = (cart[itemIndex].quantity * cart[itemIndex].price) - cart[itemIndex].discount;
                updateOrderDisplay();
            }
        }
        
        // Function to update the order display
        function updateOrderDisplay() {
            const orderItemsList = document.getElementById('orderItemsList');
            orderItemsList.innerHTML = '';
            
            if (cart.length === 0) {
                orderItemsList.innerHTML = '<div class="text-center p-4">No items in order</div>';
                
                // Update summary
                document.getElementById('subtotal').textContent = '$0.00';
                document.getElementById('tax').textContent = '$0.00';
                document.getElementById('total').textContent = '$0.00';
                
                return;
            }
            
            let subtotal = 0;
            
            // Add each item to the order display
            cart.forEach((item, index) => {
                const orderItem = document.createElement('div');
                orderItem.className = 'order-item';
                orderItem.innerHTML = `
                    <div class="order-item-left">
                        <div class="item-id">${index + 1}</div>
                        <div class="item-name">${item.name}</div>
                    </div>
                    <div class="item-price">$${item.total.toFixed(2)}</div>
                    <button class="btn-remove" onclick="removeFromOrder(${item.id})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                // Add item details section (quantity and discount)
                const itemDetails = document.createElement('div');
                itemDetails.className = 'item-details';
                itemDetails.innerHTML = `
                    <div>
                        <span class="detail-label">Quantity</span>
                        <input type="number" class="detail-input" value="${item.quantity}" min="1" 
                            onchange="updateQuantity(${item.id}, this.value)">
                    </div>
                    <div>
                        <span class="detail-label">Discount</span>
                        <input type="number" class="detail-input" value="${item.discount.toFixed(2)}" min="0" step="0.01" 
                            onchange="updateDiscount(${item.id}, this.value)">
                    </div>
                `;
                
                orderItemsList.appendChild(orderItem);
                orderItemsList.appendChild(itemDetails);
                
                subtotal += item.total;
            });
            
            // Calculate tax and total
            const tax = subtotal * taxRate;
            const total = subtotal + tax;
            
            // Update summary
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('tax').textContent = '$' + tax.toFixed(2);
            document.getElementById('total').textContent = '$' + total.toFixed(2);
        }
        
        // Search products functionality
        document.getElementById('searchProducts').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const productItems = document.querySelectorAll('.product-item');
            
            productItems.forEach(item => {
                const productName = item.querySelector('.product-name').textContent.toLowerCase();
                if (productName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Initialize order display
        updateOrderDisplay();
    </script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?>
