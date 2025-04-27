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
 * Pure function to get all suppliers
 * 
 * @param mysqli $connection Database connection
 * @return array List of suppliers
 */
function getSuppliers(mysqli $connection): array {
    try {
        $query = "SELECT * FROM suppliers ORDER BY company_name ASC";
        return executeQuery($connection, $query);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Add a new supplier
 * 
 * @param mysqli $connection Database connection
 * @param array $supplierData Supplier data to insert
 * @return int|bool The inserted supplier ID or false on failure
 */
function addSupplier(mysqli $connection, array $supplierData): int|bool {
    try {
        $query = "
            INSERT INTO suppliers (
                company_name, contact_person, phone, email, 
                address, city, country, postal_code, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $params = [
            $supplierData['company_name'],
            $supplierData['contact_person'] ?? null,
            $supplierData['phone'],
            $supplierData['email'],
            $supplierData['address'],
            $supplierData['city'] ?? null,
            $supplierData['country'] ?? null,
            $supplierData['postal_code'] ?? null,
            $supplierData['notes'] ?? null
        ];
        
        $result = executeQuery($connection, $query, $params);
        return $result['insert_id'] ?? false;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Update an existing supplier
 * 
 * @param mysqli $connection Database connection
 * @param int $supplierId Supplier ID to update
 * @param array $supplierData Updated supplier data
 * @return bool Whether the update was successful
 */
function updateSupplier(mysqli $connection, int $supplierId, array $supplierData): bool {
    try {
        $query = "
            UPDATE suppliers SET
                company_name = ?,
                contact_person = ?,
                phone = ?,
                email = ?,
                address = ?,
                city = ?,
                country = ?,
                postal_code = ?,
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE supplier_id = ?
        ";
        
        $params = [
            $supplierData['company_name'],
            $supplierData['contact_person'] ?? null,
            $supplierData['phone'],
            $supplierData['email'],
            $supplierData['address'],
            $supplierData['city'] ?? null,
            $supplierData['country'] ?? null,
            $supplierData['postal_code'] ?? null,
            $supplierData['notes'] ?? null,
            $supplierId
        ];
        
        $result = executeQuery($connection, $query, $params);
        return ($result['affected_rows'] > 0);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Delete or deactivate a supplier
 * 
 * @param mysqli $connection Database connection
 * @param int $supplierId Supplier ID to delete/deactivate
 * @return bool Whether the operation was successful
 */
function deactivateSupplier(mysqli $connection, int $supplierId): bool {
    try {
        $query = "UPDATE suppliers SET status = 'inactive' WHERE supplier_id = ?";
        $result = executeQuery($connection, $query, [$supplierId]);
        return ($result['affected_rows'] > 0);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Handle form submission for adding/editing/deleting suppliers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (addSupplier($con, $_POST)) {
                    $_SESSION['success_message'] = "Supplier added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add supplier.";
                }
                break;
                
            case 'edit':
                if (isset($_POST['supplier_id']) && updateSupplier($con, (int)$_POST['supplier_id'], $_POST)) {
                    $_SESSION['success_message'] = "Supplier updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update supplier.";
                }
                break;
                
            case 'delete':
                if (isset($_POST['supplier_id']) && deactivateSupplier($con, (int)$_POST['supplier_id'])) {
                    $_SESSION['success_message'] = "Supplier deactivated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to deactivate supplier.";
                }
                break;
        }
        
        // Redirect to avoid form resubmission
        header("Location: suppliers.php");
        exit();
    }
}

// Fetch suppliers
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
            font-size: 20px;
            font-weight: bold;
            color: var(--text-dark);
            margin: 0;
        }
        
        .add-supplier-btn {
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
        
        .add-supplier-btn i {
            margin-right: 8px;
        }
        
        .add-supplier-btn:hover {
            background-color: #e67e38;
            transform: translateY(-2px);
        }
        
        .supplier-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .supplier-table th {
            background-color: var(--primary-bg);
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-weight: 500;
        }
        
        .supplier-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .supplier-table tr:last-child td {
            border-bottom: none;
        }
        
        .supplier-table tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }
        
        .supplier-table tr:hover {
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
                <a href="../products/products.php">
                    <i class="fas fa-boxes"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="suppliers.php" class="active">
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
            <h2 class="page-title">Suppliers Management</h2>
            
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
        
        <!-- Suppliers List -->
        <div class="supplier-container">
            <div class="supplier-header">
                <h3 class="supplier-title">Supplier List</h3>
                
                <button class="add-supplier-btn" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus"></i> Add Supplier
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                            <td>
                                    <span class="badge <?php echo $supplier['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($supplier['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editSupplierModal" 
                                                data-supplier-id="<?php echo $supplier['supplier_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#deleteSupplierModal"
                                                data-supplier-id="<?php echo $supplier['supplier_id']; ?>"
                                                data-supplier-name="<?php echo htmlspecialchars($supplier['company_name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No suppliers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="suppliers.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="suppliers.php" method="POST" id="editSupplierForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="supplier_id" id="edit_supplier_id">
                        
                        <!-- Form fields with same IDs as add form but with 'edit_' prefix -->
                        <div class="mb-3">
                            <label for="edit_company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="edit_company_name" name="company_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="edit_city" name="city">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="edit_country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="edit_country" name="country">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="edit_postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="edit_postal_code" name="postal_code">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Supplier Modal -->
    <div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSupplierModalLabel">Deactivate Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to deactivate the supplier <strong id="delete_supplier_name"></strong>?</p>
                    <p>This will mark the supplier as inactive but preserve all related data.</p>
                    
                    <form action="suppliers.php" method="POST" id="deleteSupplierForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="supplier_id" id="delete_supplier_id">
                        
                        <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Deactivate Supplier</button>
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
            // Edit supplier functionality
            const editButtons = document.querySelectorAll('.btn-edit');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const supplierId = this.dataset.supplierId;
                    
                    // Here you would typically fetch the supplier details via AJAX and populate the edit form
                    // For now, we'll simulate this by finding the supplier data in the DOM
                    const row = this.closest('tr');
                    const cells = row.querySelectorAll('td');
                    
                    document.getElementById('edit_supplier_id').value = supplierId;
                    document.getElementById('edit_company_name').value = cells[0].textContent.trim();
                    document.getElementById('edit_contact_person').value = cells[1].textContent.trim() !== 'N/A' ? cells[1].textContent.trim() : '';
                    document.getElementById('edit_phone').value = cells[2].textContent.trim();
                    document.getElementById('edit_email').value = cells[3].textContent.trim();
                    document.getElementById('edit_address').value = cells[4].textContent.trim();
                });
            });
            
            // Delete supplier functionality
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const supplierId = this.dataset.supplierId;
                    const supplierName = this.dataset.supplierName;
                    
                    document.getElementById('delete_supplier_id').value = supplierId;
                    document.getElementById('delete_supplier_name').textContent = supplierName;
                });
            });
        });
    </script>
</body>
</html>
