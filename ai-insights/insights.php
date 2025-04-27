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
 * Pure function to get all AI insights
 * 
 * @param mysqli $connection Database connection
 * @param bool $onlyUnread Whether to get only unread insights
 * @return array List of insights
 */
function getInsights(mysqli $connection, bool $onlyUnread = false): array {
    try {
        $whereClause = $onlyUnread ? "WHERE is_read = 0 AND (expires_at IS NULL OR expires_at > NOW())" : 
                                     "WHERE expires_at IS NULL OR expires_at > NOW()";
        
        $query = "
            SELECT * FROM ai_insights
            $whereClause
            ORDER BY created_at DESC
        ";
        
        return executeQuery($connection, $query);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Mark an insight as read
 * 
 * @param mysqli $connection Database connection
 * @param int $insightId Insight ID to mark as read
 * @return bool Whether the update was successful
 */
function markInsightAsRead(mysqli $connection, int $insightId): bool {
    try {
        $query = "UPDATE ai_insights SET is_read = 1 WHERE insight_id = ?";
        $result = executeQuery($connection, $query, [$insightId]);
        return ($result['affected_rows'] > 0);
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Generate inventory recommendations based on stock levels and sales data
 * 
 * @param mysqli $connection Database connection
 * @return array Generated insights
 */
function generateInventoryInsights(mysqli $connection): array {
    try {
        // Get low stock products
        $lowStockQuery = "
            SELECT 
                p.product_id, 
                p.name, 
                p.sku, 
                p.stock_quantity, 
                p.reorder_level,
                c.name as category_name,
                s.company_name as supplier_name
            FROM 
                products p
            LEFT JOIN 
                categories c ON p.category_id = c.category_id
            LEFT JOIN 
                suppliers s ON p.supplier_id = s.supplier_id
            WHERE 
                p.stock_quantity <= p.reorder_level
                AND p.is_active = 1
            ORDER BY 
                p.stock_quantity ASC
            LIMIT 5
        ";
        
        $lowStockProducts = executeQuery($connection, $lowStockQuery);
        
        $insights = [];
        
        if (!empty($lowStockProducts)) {
            // Create low stock insight
            $productList = "";
            foreach ($lowStockProducts as $product) {
                $productList .= "• {$product['name']} ({$product['sku']}) - Current stock: {$product['stock_quantity']}, " .
                               "Reorder level: {$product['reorder_level']}\n";
            }
            
            $insights[] = [
                'insight_type' => 'inventory_recommendation',
                'title' => count($lowStockProducts) . ' products need reordering',
                'content' => "The following products are at or below their reorder level and need to be restocked:\n\n" . 
                             $productList . "\n\nConsider placing orders with the suppliers soon to avoid stockouts."
            ];
        }
        
        // Products with no sales in last 30 days
        $noSalesQuery = "
            SELECT 
                p.product_id, 
                p.name, 
                p.sku, 
                p.purchase_price,
                p.selling_price,
                p.stock_quantity,
                c.name as category_name
            FROM 
                products p
            LEFT JOIN 
                categories c ON p.category_id = c.category_id
            WHERE 
                p.product_id NOT IN (
                    SELECT DISTINCT oi.product_id 
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.order_id
                    WHERE o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                )
                AND p.is_active = 1
                AND p.stock_quantity > 0
            ORDER BY 
                p.stock_quantity DESC
            LIMIT 5
        ";
        
        $noSalesProducts = executeQuery($connection, $noSalesQuery);
        
        if (!empty($noSalesProducts)) {
            // Create no sales insight
            $productList = "";
            foreach ($noSalesProducts as $product) {
                $productList .= "• {$product['name']} ({$product['sku']}) - Current stock: {$product['stock_quantity']}, " .
                               "Value: " . number_format($product['stock_quantity'] * $product['purchase_price'], 2) . "\n";
            }
            
            $insights[] = [
                'insight_type' => 'inventory_recommendation',
                'title' => 'Products with no sales in the last 30 days',
                'content' => "The following products have not sold in the last 30 days and may be tying up inventory capital:\n\n" . 
                             $productList . "\n\nConsider running promotions, reducing prices, or evaluating if these products should be kept in stock."
            ];
        }
        
        return $insights;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Generate sales trend insights based on order data
 * 
 * @param mysqli $connection Database connection
 * @return array Generated insights
 */
function generateSalesTrendInsights(mysqli $connection): array {
    try {
        $insights = [];
        
        // Sales by category
        $salesByCategoryQuery = "
            SELECT 
                c.name as category_name,
                COUNT(oi.order_id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_sales
            FROM 
                order_items oi
            JOIN 
                products p ON oi.product_id = p.product_id
            JOIN 
                categories c ON p.category_id = c.category_id
            JOIN 
                orders o ON oi.order_id = o.order_id
            WHERE 
                o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                AND o.order_status = 'completed'
            GROUP BY 
                c.category_id
            ORDER BY 
                total_sales DESC
            LIMIT 3
        ";
        
        $salesByCategory = executeQuery($connection, $salesByCategoryQuery);
        
        if (!empty($salesByCategory)) {
            // Create sales by category insight
            $categoryList = "";
            foreach ($salesByCategory as $category) {
                $categoryList .= "• {$category['category_name']} - " . 
                                "Sales: " . number_format($category['total_sales'], 2) . ", " .
                                "Units sold: {$category['total_quantity']}\n";
            }
            
            $insights[] = [
                'insight_type' => 'sales_trend',
                'title' => 'Top selling categories in the last 30 days',
                'content' => "Your top performing product categories in the last 30 days are:\n\n" . 
                             $categoryList . "\n\nConsider featuring these categories prominently and ensuring adequate stock levels."
            ];
        }
        
        // Top selling products
        $topProductsQuery = "
            SELECT 
                p.name,
                p.sku,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT o.order_id) as order_count
            FROM 
                order_items oi
            JOIN 
                products p ON oi.product_id = p.product_id
            JOIN 
                orders o ON oi.order_id = o.order_id
            WHERE 
                o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                AND o.order_status = 'completed'
            GROUP BY 
                p.product_id
            ORDER BY 
                total_sales DESC
            LIMIT 5
        ";
        
        $topProducts = executeQuery($connection, $topProductsQuery);
        
        if (!empty($topProducts)) {
            // Create top products insight
            $productList = "";
            foreach ($topProducts as $product) {
                $productList .= "• {$product['name']} ({$product['sku']}) - " . 
                               "Sales: " . number_format($product['total_sales'], 2) . ", " .
                               "Units sold: {$product['total_quantity']}\n";
            }
            
            $insights[] = [
                'insight_type' => 'sales_trend',
                'title' => 'Top selling products in the last 30 days',
                'content' => "Your top selling products in the last 30 days are:\n\n" . 
                             $productList . "\n\nThese are your star performers. Ensure you maintain adequate stock levels for these products."
            ];
        }
        
        // Sales trend growth/decline
        $salesTrendQuery = "
            SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m-%d') as date,
                SUM(o.total_amount) as daily_sales
            FROM 
                orders o
            WHERE 
                o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY)
                AND o.order_status = 'completed'
            GROUP BY 
                DATE_FORMAT(o.order_date, '%Y-%m-%d')
            ORDER BY 
                date ASC
        ";
        
        $salesTrend = executeQuery($connection, $salesTrendQuery);
        
        if (count($salesTrend) > 30) {
            // Calculate 30-day moving averages
            $firstPeriodSales = 0;
            $secondPeriodSales = 0;
            
            // First 30 days
            for ($i = 0; $i < 30; $i++) {
                $firstPeriodSales += $salesTrend[$i]['daily_sales'] ?? 0;
            }
            
            // Second 30 days
            for ($i = 30; $i < 60 && $i < count($salesTrend); $i++) {
                $secondPeriodSales += $salesTrend[$i]['daily_sales'] ?? 0;
            }
            
            $salesDifference = $secondPeriodSales - $firstPeriodSales;
            $percentChange = $firstPeriodSales > 0 ? ($salesDifference / $firstPeriodSales) * 100 : 0;
            
            $trendContent = '';
            
            if ($percentChange > 10) {
                $trendContent = "Your sales are trending upward! Sales in the last 30 days have increased by " . 
                               number_format(abs($percentChange), 1) . "% compared to the previous 30 days.\n\n" .
                               "Total sales in recent 30 days: " . number_format($secondPeriodSales, 2) . "\n" .
                               "Total sales in previous 30 days: " . number_format($firstPeriodSales, 2) . "\n\n" .
                               "Keep up the good work and capitalize on this positive momentum.";
                
                $insights[] = [
                    'insight_type' => 'sales_trend',
                    'title' => 'Sales trending upward',
                    'content' => $trendContent
                ];
            } elseif ($percentChange < -10) {
                $trendContent = "Your sales are trending downward. Sales in the last 30 days have decreased by " . 
                               number_format(abs($percentChange), 1) . "% compared to the previous 30 days.\n\n" .
                               "Total sales in recent 30 days: " . number_format($secondPeriodSales, 2) . "\n" .
                               "Total sales in previous 30 days: " . number_format($firstPeriodSales, 2) . "\n\n" .
                               "Consider investigating the causes and implementing strategies to boost sales.";
                
                $insights[] = [
                    'insight_type' => 'sales_trend',
                    'title' => 'Sales trending downward',
                    'content' => $trendContent
                ];
            }
        }
        
        return $insights;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Generate price optimization insights
 * 
 * @param mysqli $connection Database connection
 * @return array Generated insights
 */
function generatePriceOptimizationInsights(mysqli $connection): array {
    try {
        $insights = [];
        
        // Products with high margins but low sales
        $highMarginLowSalesQuery = "
            SELECT 
                p.product_id,
                p.name,
                p.sku,
                p.purchase_price,
                p.selling_price,
                ((p.selling_price - p.purchase_price) / p.purchase_price * 100) as margin_percent,
                COALESCE(SUM(oi.quantity), 0) as total_quantity_sold
            FROM 
                products p
            LEFT JOIN 
                order_items oi ON p.product_id = oi.product_id
            LEFT JOIN 
                orders o ON oi.order_id = o.order_id AND o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            WHERE 
                p.is_active = 1
            GROUP BY 
                p.product_id
            HAVING 
                margin_percent > 40 AND
                total_quantity_sold < 5
            ORDER BY 
                margin_percent DESC
            LIMIT 5
        ";
        
        $highMarginLowSales = executeQuery($connection, $highMarginLowSalesQuery);
        
        if (!empty($highMarginLowSales)) {
            // Create price optimization insight
            $productList = "";
            foreach ($highMarginLowSales as $product) {
                $productList .= "• {$product['name']} ({$product['sku']}) - " . 
                               "Margin: " . number_format($product['margin_percent'], 1) . "%, " .
                               "Units sold: {$product['total_quantity_sold']}, " .
                               "Current price: " . number_format($product['selling_price'], 2) . "\n";
            }
            
            $insights[] = [
                'insight_type' => 'price_optimization',
                'title' => 'High margin products with low sales',
                'content' => "The following products have high profit margins but aren't selling well:\n\n" . 
                             $productList . "\n\nConsider lowering prices to find a better balance between margin and volume. " .
                             "Even with lower margins, increased sales volume could lead to higher overall profit."
            ];
        }
        
        // Products with low margins but high sales
        $lowMarginHighSalesQuery = "
            SELECT 
                p.product_id,
                p.name,
                p.sku,
                p.purchase_price,
                p.selling_price,
                ((p.selling_price - p.purchase_price) / p.purchase_price * 100) as margin_percent,
                COALESCE(SUM(oi.quantity), 0) as total_quantity_sold
            FROM 
                products p
            LEFT JOIN 
                order_items oi ON p.product_id = oi.product_id
            LEFT JOIN 
                orders o ON oi.order_id = o.order_id AND o.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            WHERE 
                p.is_active = 1
            GROUP BY 
                p.product_id
            HAVING 
                margin_percent < 20 AND
                total_quantity_sold > 10
            ORDER BY 
                total_quantity_sold DESC
            LIMIT 5
        ";
        
        $lowMarginHighSales = executeQuery($connection, $lowMarginHighSalesQuery);
        
        if (!empty($lowMarginHighSales)) {
            // Create price optimization insight
            $productList = "";
            foreach ($lowMarginHighSales as $product) {
                $productList .= "• {$product['name']} ({$product['sku']}) - " . 
                               "Margin: " . number_format($product['margin_percent'], 1) . "%, " .
                               "Units sold: {$product['total_quantity_sold']}, " .
                               "Current price: " . number_format($product['selling_price'], 2) . "\n";
            }
            
            $insights[] = [
                'insight_type' => 'price_optimization',
                'title' => 'Popular products with low margins',
                'content' => "The following products are selling well but have low profit margins:\n\n" . 
                             $productList . "\n\nConsider gradually increasing prices to improve profitability. " .
                             "These products are popular, so customers may be willing to pay more for them."
            ];
        }
        
        return $insights;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Store insights in the database
 * 
 * @param mysqli $connection Database connection
 * @param array $insights List of insights to store
 * @return bool Whether all insights were stored successfully
 */
function storeInsights(mysqli $connection, array $insights): bool {
    if (empty($insights)) {
        return true;
    }
    
    try {
        return transaction($connection, function($conn) use ($insights) {
            foreach ($insights as $insight) {
                $query = "
                    INSERT INTO ai_insights (
                        insight_type, title, content, is_read, created_at, expires_at
                    ) VALUES (?, ?, ?, 0, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
                ";
                
                $params = [
                    $insight['insight_type'],
                    $insight['title'],
                    $insight['content']
                ];
                
                executeQuery($conn, $query, $params);
            }
            
            return true;
        });
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Generate new insights based on current data
 * 
 * @param mysqli $connection Database connection
 * @return bool Whether insights were generated successfully
 */
function generateInsights(mysqli $connection): bool {
    // Call all insight generation functions
    $inventoryInsights = generateInventoryInsights($connection);
    $salesTrendInsights = generateSalesTrendInsights($connection);
    $priceOptimizationInsights = generatePriceOptimizationInsights($connection);
    
    // Combine all insights
    $allInsights = array_merge($inventoryInsights, $salesTrendInsights, $priceOptimizationInsights);
    
    // Store insights in the database
    return storeInsights($connection, $allInsights);
}

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'mark_read':
            if (isset($_GET['id'])) {
                markInsightAsRead($con, (int)$_GET['id']);
            }
            header("Location: insights.php");
            exit();
            break;
            
        case 'generate':
            generateInsights($con);
            header("Location: insights.php?generated=true");
            exit();
            break;
    }
}

// Fetch insights
$insights = getInsights($con);
$unreadCount = count(getInsights($con, true));

// Check for success messages
$generated = isset($_GET['generated']) && $_GET['generated'] === 'true';
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
            --text-light: #FFFFFF;
            --text-dark: #2E251C;
            --accent: #D8C3A5;
            --body-bg: #f4f6f9;
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
        
        /* Insights */
        .insights-container {
            margin-bottom: 30px;
        }
        
        .insight-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .insight-card:hover {
            transform: translateY(-5px);
        }
        
        .insight-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .insight-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--text-dark);
            margin: 0;
        }
        
        .insight-type {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-left: 10px;
        }
        
        .insight-type.inventory {
            background-color: #e1f5fe;
            color: #0288d1;
        }
        
        .insight-type.sales {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .insight-type.price {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .insight-type.customer {
            background-color: #f3e5f5;
            color: #8e24aa;
        }
        
        .insight-type.supplier {
            background-color: #e8eaf6;
            color: #3949ab;
        }
        
        .insight-body {
            padding: 20px;
            white-space: pre-line;
        }
        
        .insight-footer {
            padding: 10px 20px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        
        .insight-actions {
            display: flex;
            gap: 15px;
        }
        
        .insight-actions a {
            color: var(--highlight);
            text-decoration: none;
        }
        
        .insight-actions a:hover {
            text-decoration: underline;
        }
        
        .unread-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--highlight);
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .generate-btn {
            background-color: var(--highlight);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .generate-btn i {
            margin-right: 8px;
        }
        
        .generate-btn:hover {
            background-color: #e67e38;
        }
        
        .no-insights {
            text-align: center;
            padding: 50px 0;
            color: #666;
        }
        
        .no-insights i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
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
                <a href="insights.php" class="active">
                    <i class="fas fa-lightbulb"></i>
                    <span>AI Insights</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-auto"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
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
            <h2 class="page-title">AI Insights</h2>
            
            <div class="d-flex align-items-center">
                <a href="?action=generate" class="generate-btn me-4">
                    <i class="fas fa-sync-alt"></i>
                    Generate New Insights
                </a>
                
                <div class="user-info">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($generated): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                New insights have been generated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Insights Content -->
        <div class="insights-container">
            <?php if (empty($insights)): ?>
                <div class="no-insights">
                    <i class="fas fa-search"></i>
                    <h4>No insights available</h4>
                    <p>Click the "Generate New Insights" button to analyze your business data and get recommendations.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($insights as $insight): ?>
                        <?php
                            // Determine insight type class and icon
                            $typeClass = '';
                            $typeText = '';
                            $icon = '';
                            
                            switch ($insight['insight_type']) {
                                case 'inventory_recommendation':
                                    $typeClass = 'inventory';
                                    $typeText = 'Inventory';
                                    $icon = 'fas fa-boxes';
                                    break;
                                case 'sales_trend':
                                    $typeClass = 'sales';
                                    $typeText = 'Sales';
                                    $icon = 'fas fa-chart-line';
                                    break;
                                case 'price_optimization':
                                    $typeClass = 'price';
                                    $typeText = 'Pricing';
                                    $icon = 'fas fa-tags';
                                    break;
                                case 'customer_behavior':
                                    $typeClass = 'customer';
                                    $typeText = 'Customer';
                                    $icon = 'fas fa-users';
                                    break;
                                case 'supplier_performance':
                                    $typeClass = 'supplier';
                                    $typeText = 'Supplier';
                                    $icon = 'fas fa-truck';
                                    break;
                                default:
                                    $typeClass = '';
                                    $typeText = 'General';
                                    $icon = 'fas fa-info-circle';
                            }
                            
                            $createdAt = new DateTime($insight['created_at']);
                            $formattedDate = $createdAt->format('M j, Y g:i A');
                        ?>
                        <div class="col-md-6">
                            <div class="insight-card">
                                <div class="insight-header">
                                    <div class="d-flex align-items-center">
                                        <?php if (!$insight['is_read']): ?>
                                            <span class="unread-badge"></span>
                                        <?php endif; ?>
                                        <h5 class="insight-title"><?php echo htmlspecialchars($insight['title']); ?></h5>
                                    </div>
                                    <span class="insight-type <?php echo $typeClass; ?>">
                                        <i class="<?php echo $icon; ?> me-1"></i>
                                        <?php echo $typeText; ?>
                                    </span>
                                </div>
                                <div class="insight-body">
                                    <?php echo nl2br(htmlspecialchars($insight['content'])); ?>
                                </div>
                                <div class="insight-footer">
                                    <div>
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo $formattedDate; ?>
                                    </div>
                                    <div class="insight-actions">
                                        <?php if (!$insight['is_read']): ?>
                                            <a href="?action=mark_read&id=<?php echo $insight['insight_id']; ?>">
                                                <i class="far fa-check-circle me-1"></i>
                                                Mark as read
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 