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

// Sample suppliers data
$suppliers = [
    ['id' => 1, 'company_name' => 'WALDO', 'phone' => '0911290', 'email' => 'info@waldo.com', 'address' => '123 Main St, City'],
    ['id' => 2, 'company_name' => 'Global Foods Inc.', 'phone' => '0922456789', 'email' => 'contact@globalfoods.com', 'address' => '456 Market Ave, Metro City'],
    ['id' => 3, 'company_name' => 'Fresh Farms Co.', 'phone' => '0933123456', 'email' => 'sales@freshfarms.com', 'address' => '789 Harvest Rd, Rural County'],
    ['id' => 4, 'company_name' => 'Prime Distributors', 'phone' => '0944987654', 'email' => 'orders@primedist.com', 'address' => '101 Logistics Blvd, Warehouse District'],
    ['id' => 5, 'company_name' => 'Quality Goods Ltd.', 'phone' => '0955654321', 'email' => 'inquiries@qualitygoods.com', 'address' => '202 Quality Lane, Business Park']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Business IMS</title>
    
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #FFF8F1;
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
        
        /* Supplier Table */
        .supplier-container {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .supplier-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-dark);
        }
        
        .filter-container {
            display: flex;
            align-items: center;
        }
        
        .filter-label {
            font-size: 18px;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .btn-filter {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
        }
        
        .btn-add {
            background-color: var(--highlight);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
        }
        
        .supplier-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .supplier-table th {
            background-color: var(--primary-bg);
            color: white;
            text-align: left;
            padding: 12px 15px;
        }
        
        .supplier-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .supplier-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
            margin-right: 5px;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
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
        }
        
        .modal-header {
            background-color: var(--primary-bg);
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-footer .btn-primary {
            background-color: var(--highlight);
            border: none;
        }
        
        .modal-footer .btn-primary:hover {
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
        }
        
        @media (max-width: 768px) {
            .supplier-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-container {
                margin-bottom: 15px;
            }
            
            .supplier-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
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
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="../suppliers/suppliers.php" class="active">
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
            <h1 class="page-title">Suppliers</h1>
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
        
        <!-- Supplier Management -->
        <div class="supplier-container">
            <div class="supplier-header">
                <div class="filter-container">
                    <span class="filter-label">Filter:</span>
                    <div class="dropdown">
                        <button class="btn btn-filter dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            ID
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a class="dropdown-item" href="#">ID</a></li>
                            <li><a class="dropdown-item" href="#">Company Name</a></li>
                            <li><a class="dropdown-item" href="#">Phone Number</a></li>
                            <li><a class="dropdown-item" href="#">Email</a></li>
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus"></i> ADD
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Company Name</th>
                            <th>Phone Number</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>ALTER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo $supplier['id']; ?></td>
                            <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                            <td>
                                <button class="btn-edit" onclick="editSupplier(<?php echo $supplier['id']; ?>)">Edit</button>
                                <button class="btn-delete" onclick="deleteSupplier(<?php echo $supplier['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSupplierForm">
                        <div class="mb-3">
                            <label for="companyName" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="companyName" required>
                        </div>
                        <div class="mb-3">
                            <label for="phoneNumber" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phoneNumber" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Edit Supplier
        function editSupplier(id) {
            // In a real application, you would fetch the supplier data from the server
            alert('Edit supplier with ID: ' + id);
            // Then open a modal with the data pre-filled
        }
        
        // Delete Supplier
        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier?')) {
                // In a real application, you would send a delete request to the server
                alert('Delete supplier with ID: ' + id);
            }
        }
        
        // Save New Supplier
        function saveSupplier() {
            const companyName = document.getElementById('companyName').value;
            const phoneNumber = document.getElementById('phoneNumber').value;
            const email = document.getElementById('email').value;
            const address = document.getElementById('address').value;
            
            if (!companyName || !phoneNumber || !email || !address) {
                alert('Please fill all required fields');
                return;
            }
            
            // In a real application, you would send this data to the server
            alert('Supplier added successfully!');
            document.getElementById('addSupplierForm').reset();
            document.getElementById('addSupplierModal').modal.hide();
        }
    </script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?>
