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

// Sample AI insights data
$insights = [
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
        
        /* AI Integration Section */
        .integration-container {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .code-block {
            background-color: #2d2d2d;
            color: #e6e6e6;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .code-block pre {
            margin: 0;
            white-space: pre-wrap;
        }
        
        .keyword {
            color: #569cd6;
        }
        
        .string {
            color: #ce9178;
        }
        
        .comment {
            color: #6a9955;
        }
        
        .code-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
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
        
        <!-- AI Integration Documentation -->
        <div class="integration-container">
            <h2 class="insights-title">GEMINI AI INTEGRATION</h2>
            <p class="insights-description">
                How to implement AI Insights with Gemini AI and secure API key management
            </p>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3 class="code-title">1. Store Gemini API key in .env file</h3>
                    <div class="code-block">
                        <pre><span class="comment"># .env file</span>
<span class="keyword">GEMINI_API_KEY</span>=<span class="string">your_api_key_here</span></pre>
                    </div>
                </div>
                <div class="col-md-6">
                    <h3 class="code-title">2. Load API key in your application</h3>
                    <div class="code-block">
                        <pre><span class="comment">// Load environment variables</span>
<span class="keyword">require_once</span>(<span class="string">'vendor/autoload.php'</span>);
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

<span class="comment">// Access the API key</span>
$geminiApiKey = $_ENV[<span class="string">'GEMINI_API_KEY'</span>];</pre>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="code-title">3. Example Prompt to Send to Gemini</h3>
                    <div class="code-block">
                        <pre><span class="comment">// Example payload for Gemini API</span>
$payload = [
    <span class="string">"prompt"</span> => <span class="string">"Analyze the following inventory and sales data. Forecast demand for the next 30 days. Suggest reorder quantities and flag any anomalies. Also identify the top and bottom performing products. Data:\n\nProduct ID: 001\nName: Wireless Mouse\nCurrent Stock: 120\n30-Day Sales: 300\nSupplier: SupplierX\n\nProduct ID: 002\nName: Keyboard\nCurrent Stock: 40\n30-Day Sales: 25\nSupplier: SupplierY\n\n...etc"</span>
];</pre>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h3 class="code-title">4. Features to Implement</h3>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Demand Forecasting (30/60/90 days)
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Stock Optimization
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Anomaly Detection
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Product Performance Analysis
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Supplier Insights
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Dynamic Pricing Suggestions
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h3 class="code-title">5. Response Handling</h3>
                    <div class="code-block">
                        <pre><span class="comment">// Process Gemini API response</span>
<span class="keyword">function</span> <span class="keyword">processGeminiResponse</span>($response) {
    <span class="comment">// Parse the AI response</span>
    $insights = [];
    
    <span class="comment">// Extract key metrics and recommendations</span>
    $insights[<span class="string">'forecasted_demand'</span>] = extractMetric($response, <span class="string">'demand'</span>);
    $insights[<span class="string">'reorder_suggestion'</span>] = extractMetric($response, <span class="string">'reorder'</span>);
    $insights[<span class="string">'anomalies'</span>] = extractMetric($response, <span class="string">'anomaly'</span>);
    $insights[<span class="string">'performance'</span>] = extractMetric($response, <span class="string">'performance'</span>);
    $insights[<span class="string">'pricing_tip'</span>] = extractMetric($response, <span class="string">'pricing'</span>);
    
    <span class="keyword">return</span> $insights;
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?>
