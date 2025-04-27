<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once("../connection.php");

// Load environment variables if .env file exists
if (file_exists('../.env')) {
    $envVars = parse_ini_file('../.env');
    if (isset($envVars['GEMINI_API_KEY'])) {
        $geminiApiKey = $envVars['GEMINI_API_KEY'];
    }
}

// Get user information
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'User';

// Sample inventory data for analysis (in a real app, this would come from a database)
$inventoryData = [
    ['id' => '001', 'name' => 'Full Cream Milk', 'stock' => 120, 'sales' => 300, 'supplier' => 'Magnolia'],
    ['id' => '002', 'name' => 'Coffee Beans', 'stock' => 45, 'sales' => 180, 'supplier' => 'MountainBean'],
    ['id' => '003', 'name' => 'Brown Sugar', 'stock' => 60, 'sales' => 110, 'supplier' => 'SugarCo'],
    ['id' => '004', 'name' => 'Whole Wheat Bread', 'stock' => 25, 'sales' => 95, 'supplier' => 'BakeryPlus'],
    ['id' => '005', 'name' => 'Tomato Sauce', 'stock' => 80, 'sales' => 40, 'supplier' => 'SupplierX']
];

// Function to get insights from Gemini AI
function getGeminiInsights($data) {
    global $geminiApiKey;
    
    // This would normally call the Gemini API with the data
    // For now, returning sample data - in a real implementation,
    // this would use the provided Gemini AI code
    return [
        [
            'title' => 'Demand Forecasting',
            'description' => 'Predict demand for each product over the next 30/60/90 days',
            'recommendation' => 'Expected 15% increase in demand for Full Cream Milk over the next 30 days.',
            'icon' => 'fa-chart-line'
        ],
        [
            'title' => 'Stock Optimization',
            'description' => 'Suggest optimal reorder points and quantities',
            'recommendation' => 'Reorder Coffee Beans (20 units) and Brown Sugar (15 units) within 5 days.',
            'icon' => 'fa-cubes'
        ],
        [
            'title' => 'Anomaly Detection',
            'description' => 'Highlight unexpected inventory or sales patterns',
            'recommendation' => 'Unusually high sales of Whole Wheat Bread detected. Consider increasing stock.',
            'icon' => 'fa-triangle-exclamation'
        ],
        [
            'title' => 'Product Performance',
            'description' => 'Identify fast-moving vs. slow-moving products',
            'recommendation' => 'Tomato Sauce is moving slowly. Consider promotions or placement changes.',
            'icon' => 'fa-ranking-star'
        ],
        [
            'title' => 'Supplier Insights',
            'description' => 'Flag unreliable suppliers based on past order history',
            'recommendation' => 'SupplierX has been late on 30% of deliveries. Consider alternative sources.',
            'icon' => 'fa-truck'
        ],
        [
            'title' => 'Dynamic Pricing Suggestions',
            'description' => 'Propose price adjustments to improve margins and move stock',
            'recommendation' => 'Increasing Fresh Eggs price by 5% would optimize profit margin.',
            'icon' => 'fa-tags'
        ]
    ];
}

// Process Gemini API response
function processGeminiResponse($response) {
    // Parse the AI response - in a real implementation, this would 
    // extract insights from actual Gemini API responses
    $insights = [];
    
    // Sample processing of response data
    foreach ($response as $item) {
        $insights[$item['title']] = $item['recommendation'];
    }
    
    return $insights;
}

// Get insights from sample data
$insights = getGeminiInsights($inventoryData);

// Process insights
$processedInsights = processGeminiResponse($insights);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - Business IMS</title>
    
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
        
        /* AI Insights Section */
        .insights-container {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .insights-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
        }
        
        .insights-description {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-dark);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .insight-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .insight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .insight-icon {
            width: 60px;
            height: 60px;
            background-color: var(--highlight);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 24px;
        }
        
        .insight-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-bg);
        }
        
        .insight-description {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .insight-recommendation {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--highlight);
            margin-top: auto;
        }
        
        .insight-recommendation strong {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-bg);
        }
        
        /* Inventory Data Table */
        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--primary-bg);
            color: white;
            border: none;
        }
        
        .table tr:hover {
            background-color: rgba(247, 143, 64, 0.1);
        }
        
        .btn-refresh {
            background-color: var(--highlight);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-size: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .btn-refresh i {
            margin-right: 8px;
        }
        
        .btn-refresh:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
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
                <a href="../ai-insights/ai-insights.php" class="active">
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
            <h1 class="page-title">AI Insights</h1>
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
        
        <!-- Inventory Data Section -->
        <div class="insights-container">
            <h2 class="insights-title">INVENTORY DATA</h2>
            <p class="insights-description">
                The following inventory data is being analyzed by Gemini AI to generate insights.
            </p>
            
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Current Stock</th>
                            <th>30-Day Sales</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryData as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['stock']); ?></td>
                            <td><?php echo htmlspecialchars($item['sales']); ?></td>
                            <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button class="btn-refresh" id="refreshInsights">
                <i class="fas fa-sync-alt"></i> Generate AI Insights
            </button>
        </div>
        
        <!-- AI Insights Section -->
        <div class="insights-container">
            <h2 class="insights-title">GEMINI AI INSIGHTS</h2>
            <p class="insights-description">
                Leveraging Google's Gemini AI to generate smart insights based on your product, sales, and inventory data. Make better business decisions with AI-powered recommendations.
            </p>
            
            <div class="row">
                <?php foreach ($insights as $insight): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="insight-card">
                        <div class="insight-icon">
                            <i class="fas <?php echo $insight['icon']; ?>"></i>
                        </div>
                        <h3 class="insight-title"><?php echo htmlspecialchars($insight['title']); ?></h3>
                        <p class="insight-description"><?php echo htmlspecialchars($insight['description']); ?></p>
                        <div class="insight-recommendation">
                            <strong>AI Recommendation:</strong>
                            <?php echo htmlspecialchars($insight['recommendation']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Refresh insights functionality
        document.getElementById('refreshInsights').addEventListener('click', function() {
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Insights...';
            this.disabled = true;
            
            // In a real application, this would make an AJAX request to a PHP endpoint 
            // that interfaces with Gemini AI and returns the insights
            
            // Simulate API call delay
            setTimeout(() => {
                // Reset button state
                this.innerHTML = '<i class="fas fa-sync-alt"></i> Generate AI Insights';
                this.disabled = false;
                
                // Show success message
                alert('AI Insights refreshed successfully!');
                
                // In a real app, you would update the UI with the new insights
                // window.location.reload();
            }, 2000);
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
