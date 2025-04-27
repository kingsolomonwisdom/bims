<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

require_once("../connection.php");

/**
 * Pure function to authenticate a user
 * 
 * @param mysqli $connection Database connection
 * @param string $username Username or email
 * @param string $password Password
 * @return array|bool User data on success, false on failure
 */
function authenticateUser(mysqli $connection, string $username, string $password): array|bool {
    try {
        // Check if input is email or username
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        $query = "SELECT * FROM users WHERE " . ($isEmail ? "email = ?" : "username = ?");
        $result = executeQuery($connection, $query, [$username]);
        
        if (empty($result)) {
            return false;
        }
        
        $user = $result[0];
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Update last login timestamp
        $updateQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
        executeQuery($connection, $updateQuery, [$user['user_id']]);
        
        return $user;
    } catch (DatabaseException $e) {
        error_log($e->getMessage());
        return false;
    }
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $user = authenticateUser($con, $username, $password);
        
        if ($user) {
            // If user is not active, show error
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Please contact an administrator.";
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // If remember me is checked, set a cookie (30 days)
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                    
                    // Store the token in the database (in a real implementation)
                    // This would require a remember_tokens table
                }
                
                // Redirect to dashboard
                header("Location: ../dashboard/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Business IMS</title>
    
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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FFF8F1;
            background-image: url('../assets/images/index.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }
        
        .login-container {
            background-color: #FFF8F1;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 30px;
            width: 400px;
            max-width: 90%;
            position: relative;
            z-index: 1;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo i {
            font-size: 40px;
            color: var(--primary-bg);
        }
        
        .login-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary-bg);
        }
        
        .form-control {
            background-color: #f9f3e9;
            border: 1px solid #e1d7c7;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--highlight);
            box-shadow: 0 0 0 0.2rem rgba(247, 143, 64, 0.25);
        }
        
        .btn-login {
            background-color: var(--highlight);
            border: none;
            border-radius: 5px;
            color: white;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #e67e38;
            transform: translateY(-2px);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider-line {
            flex-grow: 1;
            height: 1px;
            background-color: #e1d7c7;
        }
        
        .divider-text {
            padding: 0 15px;
            color: #896a51;
            font-size: 14px;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: var(--text-dark);
        }
        
        .signup-link a {
            color: var(--highlight);
            text-decoration: none;
            font-weight: 500;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-briefcase"></i>
        </div>
        
        <h2 class="login-title">Login to Business IMS</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="mb-3">
                <input type="text" class="form-control" name="username" placeholder="Username or Email" required>
            </div>
            
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-login">Login</button>
        </form>
        
        <div class="divider">
            <div class="divider-line"></div>
            <div class="divider-text">OR</div>
            <div class="divider-line"></div>
        </div>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
        
        <div class="signup-link mt-2">
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
if(isset($con)) {
    $con->close();
}
?> 