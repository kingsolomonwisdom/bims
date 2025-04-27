<?php
/**
 * Business IMS Installation Script
 * 
 * This script sets up the database schema and initial data.
 * It uses a functional approach for better reliability and error handling.
 */

// Initial configuration
$config = [
    'dbhost' => 'localhost',
    'dbuser' => 'root',
    'dbpass' => '',
    'dbname' => 'bbims',
    'admin_email' => 'admin@bbims.com',
    'admin_username' => 'admin',
    'admin_password' => 'admin123', // Will be hashed
    'company_name' => 'Business IMS',
    'install_completed' => false
];

// Display functions
function displayHeader(): void {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Business IMS Installation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #FFF8F1;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            }
            .install-container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                padding: 30px;
            }
            .install-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .install-header h1 {
                color: #2E251C;
                font-weight: bold;
            }
            .progress-bar {
                height: 10px;
                border-radius: 5px;
                background-color: #F78F40;
            }
            .btn-primary {
                background-color: #F78F40;
                border-color: #F78F40;
            }
            .btn-primary:hover {
                background-color: #e67e38;
                border-color: #e67e38;
            }
            .alert-success {
                background-color: #d4edda;
                border-color: #c3e6cb;
                color: #155724;
            }
            .alert-danger {
                background-color: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24;
            }
        </style>
    </head>
    <body>
        <div class="install-container">
            <div class="install-header">
                <h1>Business IMS Installation</h1>
            </div>
            <div class="progress mb-4">
                <div id="progress-bar" class="progress-bar" style="width: 0%"></div>
            </div>';
}

function displayFooter(): void {
    echo '
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}

function displaySuccess(string $message): void {
    echo "<div class='alert alert-success'>{$message}</div>";
}

function displayError(string $message): void {
    echo "<div class='alert alert-danger'>{$message}</div>";
}

function updateProgress(int $progress): void {
    echo "<script>document.getElementById('progress-bar').style.width = '{$progress}%';</script>";
    ob_flush();
    flush();
}

// Installation functions
function checkRequirements(): array {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = "PHP version 8.0.0 or higher is required. Current version: " . PHP_VERSION;
    }
    
    // Check required extensions
    $requiredExtensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'session'];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Required PHP extension not found: {$ext}";
        }
    }
    
    // Check if connection.php exists
    if (!file_exists('./connection.php')) {
        $errors[] = "connection.php file not found in the root directory.";
    }
    
    // Check if schema.sql exists
    if (!file_exists('./schema.sql')) {
        $errors[] = "schema.sql file not found in the root directory.";
    }
    
    // Check directory permissions
    $directories = ['.', './auth', './products', './suppliers', './pos', './dashboard'];
    foreach ($directories as $dir) {
        if (!is_writable($dir)) {
            $errors[] = "Directory not writable: {$dir}";
        }
    }
    
    return $errors;
}

function testDatabaseConnection(array $config): bool {
    try {
        $conn = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpass']);
        
        if ($conn->connect_error) {
            return false;
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function runSQLFile(mysqli $conn, string $filename): array {
    $errors = [];
    $sqlContent = file_get_contents($filename);
    
    // Split SQL file into queries
    $queries = explode(';', $sqlContent);
    
    foreach ($queries as $query) {
        $query = trim($query);
        
        if (empty($query)) {
            continue;
        }
        
        if (!$conn->query($query)) {
            $errors[] = "Error executing query: {$conn->error}";
        }
    }
    
    return $errors;
}

function createAdminUser(mysqli $conn, array $config): bool {
    try {
        // Check if admin user already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $config['admin_username'], $config['admin_email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return true; // Admin already exists
        }
        
        // Create admin user
        $hashedPassword = password_hash($config['admin_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role, created_at) 
            VALUES (?, ?, ?, 'admin', NOW())
        ");
        $stmt->bind_param("sss", $config['admin_username'], $config['admin_email'], $hashedPassword);
        
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

function updateSettings(mysqli $conn, array $config): bool {
    try {
        // Update company name
        $stmt = $conn->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_description)
            VALUES ('company_name', ?, 'Company name displayed in the application')
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->bind_param("ss", $config['company_name'], $config['company_name']);
        
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

function createAPITokensTable(mysqli $conn): bool {
    try {
        $query = "
            CREATE TABLE IF NOT EXISTS api_tokens (
                token_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL UNIQUE,
                description VARCHAR(255),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                last_used_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE INDEX idx_api_tokens_token ON api_tokens(token);
        ";
        
        return $conn->multi_query($query);
    } catch (Exception $e) {
        return false;
    }
}

function createConfigFile(array $config): bool {
    $configContent = "<?php
/**
 * Application Configuration
 * Generated on: " . date('Y-m-d H:i:s') . "
 */

return [
    'database' => [
        'host' => '{$config['dbhost']}',
        'username' => '{$config['dbuser']}',
        'password' => '{$config['dbpass']}',
        'name' => '{$config['dbname']}',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => '{$config['company_name']}',
        'url' => '',
        'timezone' => 'Asia/Manila',
        'debug' => false
    ],
    'mail' => [
        'from_name' => '{$config['company_name']}',
        'from_email' => 'noreply@example.com',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls'
    ]
];";

    return file_put_contents('config.php', $configContent) !== false;
}

function createInstallLockFile(): bool {
    return file_put_contents('install.lock', date('Y-m-d H:i:s')) !== false;
}

// Main installation process
displayHeader();

// Check if installation is already completed
if (file_exists('install.lock')) {
    displayError("Installation has already been completed. For security reasons, please delete this file (install.php).");
    displayFooter();
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $config['dbhost'] = $_POST['dbhost'] ?? 'localhost';
    $config['dbuser'] = $_POST['dbuser'] ?? 'root';
    $config['dbpass'] = $_POST['dbpass'] ?? '';
    $config['dbname'] = $_POST['dbname'] ?? 'bbims';
    $config['admin_email'] = $_POST['admin_email'] ?? 'admin@bbims.com';
    $config['admin_username'] = $_POST['admin_username'] ?? 'admin';
    $config['admin_password'] = $_POST['admin_password'] ?? 'admin123';
    $config['company_name'] = $_POST['company_name'] ?? 'Business IMS';
    
    echo '<h3>Installing Business IMS...</h3>';
    
    // Step 1: Check requirements
    echo '<h4>Step 1: Checking requirements</h4>';
    $requirementErrors = checkRequirements();
    
    if (!empty($requirementErrors)) {
        foreach ($requirementErrors as $error) {
            displayError($error);
        }
        displayFooter();
        exit;
    }
    
    displaySuccess("All requirements met.");
    updateProgress(20);
    
    // Step 2: Test database connection
    echo '<h4>Step 2: Testing database connection</h4>';
    if (!testDatabaseConnection($config)) {
        displayError("Could not connect to the database server. Please check your credentials.");
        displayFooter();
        exit;
    }
    
    displaySuccess("Database connection successful.");
    updateProgress(40);
    
    // Step 3: Create database and tables
    echo '<h4>Step 3: Creating database and tables</h4>';
    
    try {
        $conn = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpass']);
        
        // Create database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS {$config['dbname']}");
        $conn->select_db($config['dbname']);
        
        // Run schema.sql
        $schemaErrors = runSQLFile($conn, './schema.sql');
        
        if (!empty($schemaErrors)) {
            foreach ($schemaErrors as $error) {
                displayError($error);
            }
            displayFooter();
            exit;
        }
        
        displaySuccess("Database and tables created successfully.");
        updateProgress(60);
        
        // Step 4: Create admin user
        echo '<h4>Step 4: Creating admin user</h4>';
        if (!createAdminUser($conn, $config)) {
            displayError("Failed to create admin user.");
            displayFooter();
            exit;
        }
        
        displaySuccess("Admin user created successfully.");
        updateProgress(70);
        
        // Step 5: Update settings
        echo '<h4>Step 5: Configuring application settings</h4>';
        if (!updateSettings($conn, $config)) {
            displayError("Failed to update application settings.");
            displayFooter();
            exit;
        }
        
        // Create API tokens table
        if (!createAPITokensTable($conn)) {
            displayError("Failed to create API tokens table.");
            displayFooter();
            exit;
        }
        
        displaySuccess("Application settings configured successfully.");
        updateProgress(80);
        
        // Step 6: Create config file
        echo '<h4>Step 6: Creating configuration file</h4>';
        if (!createConfigFile($config)) {
            displayError("Failed to create configuration file.");
            displayFooter();
            exit;
        }
        
        displaySuccess("Configuration file created successfully.");
        updateProgress(90);
        
        // Step 7: Finalize installation
        echo '<h4>Step 7: Finalizing installation</h4>';
        if (!createInstallLockFile()) {
            displayError("Failed to create installation lock file.");
            displayFooter();
            exit;
        }
        
        $conn->close();
        
        displaySuccess("Installation completed successfully!");
        updateProgress(100);
        
        echo '<div class="alert alert-success mt-4">
            <h4>Installation Complete!</h4>
            <p>Business IMS has been successfully installed. You can now log in with the following credentials:</p>
            <ul>
                <li><strong>Username:</strong> ' . htmlspecialchars($config['admin_username']) . '</li>
                <li><strong>Password:</strong> ' . htmlspecialchars($config['admin_password']) . '</li>
            </ul>
            <p>Please delete this file (install.php) for security reasons.</p>
            <div class="mt-3">
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                <a href="auth/login.php" class="btn btn-success ms-2">Login to Dashboard</a>
            </div>
        </div>';
        
    } catch (Exception $e) {
        displayError("An error occurred during installation: " . $e->getMessage());
    }
    
} else {
    // Display installation form
    ?>
    <h3>Welcome to Business IMS Installation</h3>
    <p>This wizard will help you set up the Business IMS application. Please fill in the form below with your database and admin information.</p>
    
    <form method="post" action="install.php" class="mt-4">
        <h4>Database Information</h4>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="dbhost" class="form-label">Database Host</label>
                <input type="text" class="form-control" id="dbhost" name="dbhost" value="localhost" required>
            </div>
            <div class="col-md-6">
                <label for="dbname" class="form-label">Database Name</label>
                <input type="text" class="form-control" id="dbname" name="dbname" value="bbims" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="dbuser" class="form-label">Database Username</label>
                <input type="text" class="form-control" id="dbuser" name="dbuser" value="root" required>
            </div>
            <div class="col-md-6">
                <label for="dbpass" class="form-label">Database Password</label>
                <input type="password" class="form-control" id="dbpass" name="dbpass" value="">
            </div>
        </div>
        
        <h4 class="mt-4">Admin Account</h4>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="admin_username" class="form-label">Admin Username</label>
                <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
            </div>
            <div class="col-md-6">
                <label for="admin_email" class="form-label">Admin Email</label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@bbims.com" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="admin_password" class="form-label">Admin Password</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" value="admin123" required>
        </div>
        
        <h4 class="mt-4">Company Information</h4>
        <div class="mb-3">
            <label for="company_name" class="form-label">Company Name</label>
            <input type="text" class="form-control" id="company_name" name="company_name" value="Business IMS" required>
        </div>
        
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg">Install Business IMS</button>
        </div>
    </form>
    <?php
}

displayFooter();
?> 