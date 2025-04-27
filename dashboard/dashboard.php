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

// Demo data - no database queries needed
$total_products = 187;
$total_suppliers = 24;

// Sample chart data
$top_products = ['Full Cream Milk', 'Rice Premium', 'Sugar White', 'Coffee Beans', 'Flour All-Purpose'];
$top_products_data = [42, 35, 28, 20, 15];

$months = ['Jan 2023', 'Feb 2023', 'Mar 2023', 'Apr 2023', 'May 2023', 'Jun 2023'];
$revenue_data = [4500, 5200, 4800, 5800, 6000, 6500];

// Sample low stock products
$low_stock_items = [
    ['name' => 'Full Cream Milk', 'sku' => 'MAG-MLK-200ML', 'stock' => 4, 'status' => 'critical'],
    ['name' => 'Butter Unsalted', 'sku' => 'BT-UNSLT-500G', 'stock' => 8, 'status' => 'low'],
    ['name' => 'Coffee Beans Arabica', 'sku' => 'COF-ARAB-1KG', 'stock' => 7, 'status' => 'low'],
    ['name' => 'Rice Premium', 'sku' => 'RC-PREM-5KG', 'stock' => 5, 'status' => 'critical'],
    ['name' => 'Sugar Brown', 'sku' => 'SG-BRWN-1KG', 'stock' => 6, 'status' => 'low']
];

// Sample transactions
$recent_transactions = [
    ['id' => 'T-1001', 'product' => 'Full Cream Milk', 'date' => 'May 12, 2023', 'amount' => 120.00],
    ['id' => 'T-1002', 'product' => 'Sugar White', 'date' => 'May 11, 2023', 'amount' => 85.50],
    ['id' => 'T-1003', 'product' => 'Rice Premium', 'date' => 'May 10, 2023', 'amount' => 210.75],
    ['id' => 'T-1004', 'product' => 'Coffee Beans', 'date' => 'May 09, 2023', 'amount' => 350.00],
    ['id' => 'T-1005', 'product' => 'Flour All-Purpose', 'date' => 'May 08, 2023', 'amount' => 75.25]
];

// Category distribution data
$category_labels = ['Dairy', 'Grains', 'Beverages', 'Snacks', 'Produce', 'Meat'];
$category_data = [45, 38, 32, 28, 25, 20];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Business IMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            background-color: #f4f6f9;
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
        
        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-bg);
            color: var(--text-light);
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--highlight), #FFAB76);
            color: var(--text-light);
            border-radius: 10px;
            padding: 20px;
            height: 100%;
        }
        
        .stats-card .icon {
            background-color: rgba(255,255,255,0.2);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        
        .stats-card .icon i {
            font-size: 24px;
        }
        
        .stats-card h3 {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stats-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* Tables */
        .table {
            color: var(--text-dark);
        }
        
        .table th {
            border-top: none;
            border-bottom: 2px solid var(--accent);
            font-weight: 600;
            padding: 15px 10px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 10px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-low {
            background-color: #FFE6E6;
            color: #FF4C4C;
        }
        
        .status-medium {
            background-color: #FFF4E5;
            color: #FFA117;
        }
        
        .status-good {
            background-color: #E6F8E6;
            color: #4CAF50;
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
        
        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 15px;
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
                <a href="#" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-shopping-cart"></i>
                    <span>POS</span>
                </a>
            </li>
            <li>
                <a href="#">
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
            <h1 class="page-title">Dashboard</h1>
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
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <p>Total Products</p>
                    <h3><?php echo number_format($total_products); ?></h3>
                    <p class="trend"><i class="fas fa-arrow-up"></i> 12% from last month</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <p>Total Suppliers</p>
                    <h3><?php echo number_format($total_suppliers); ?></h3>
                    <p class="trend"><i class="fas fa-arrow-up"></i> 5% from last month</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <p>Revenue (Monthly)</p>
                    <h3>$<?php echo number_format(array_sum($revenue_data)/6, 2); ?></h3>
                    <p class="trend"><i class="fas fa-arrow-up"></i> 8% from last month</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <p>Transactions</p>
                    <h3>124</h3>
                    <p class="trend"><i class="fas fa-arrow-up"></i> 14% from last month</p>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-8 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Revenue Trend</span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Last 6 Months
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Last 3 Months</a></li>
                                <li><a class="dropdown-item" href="#">Last 6 Months</a></li>
                                <li><a class="dropdown-item" href="#">This Year</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <span>Top Selling Products</span>
                    </div>
                    <div class="card-body">
                        <canvas id="productChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock and Recent Transactions -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Low Stock Alert</span>
                        <a href="#" class="btn btn-sm btn-outline-light">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td><?php echo $item['stock']; ?></td>
                                            <td>
                                                <?php if ($item['status'] == 'critical'): ?>
                                                    <span class="status-badge status-low">Critical</span>
                                                <?php elseif ($item['status'] == 'low'): ?>
                                                    <span class="status-badge status-medium">Low</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-good">Good</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Transactions</span>
                        <a href="#" class="btn btn-sm btn-outline-light">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['product']); ?></td>
                                            <td><?php echo $transaction['date']; ?></td>
                                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inventory Distribution -->
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <span>Inventory Distribution by Category</span>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Charts -->
    <script>
        // Configure Charts
        window.onload = function() {
            // Revenue Chart
            const revenueChart = new Chart(
                document.getElementById('revenueChart').getContext('2d'),
                {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($months); ?>,
                        datasets: [{
                            label: 'Revenue',
                            data: <?php echo json_encode($revenue_data); ?>,
                            fill: true,
                            backgroundColor: 'rgba(247, 143, 64, 0.2)',
                            borderColor: '#F78F40',
                            tension: 0.4,
                            pointBackgroundColor: '#F78F40',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '$' + context.parsed.y.toLocaleString();
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                }
            );
            
            // Top Products Chart
            const productChart = new Chart(
                document.getElementById('productChart').getContext('2d'),
                {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($top_products); ?>,
                        datasets: [{
                            data: <?php echo json_encode($top_products_data); ?>,
                            backgroundColor: [
                                '#F78F40',
                                '#D8C3A5',
                                '#FFAB76',
                                '#6C4F3D',
                                '#2E251C'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 15
                                }
                            }
                        }
                    }
                }
            );
            
            // Category Distribution Chart
            const categoryChart = new Chart(
                document.getElementById('categoryChart').getContext('2d'),
                {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($category_labels); ?>,
                        datasets: [{
                            label: 'Number of Products',
                            data: <?php echo json_encode($category_data); ?>,
                            backgroundColor: '#F78F40',
                            borderWidth: 0,
                            borderRadius: 5,
                            maxBarThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                }
            );
        };
    </script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?>
