<?php
session_start();

require("../connection.php");

// Function to sanitize input
function sanitize_input($data) {
    global $con;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($con, $data);
}

// LOGIN
if(isset($_POST['login'])){
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    // Prepare statement for login
    $login_stmt = $con->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $login_stmt->bind_param("s", $username);
    $login_stmt->execute();
    $result = $login_stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Update last login timestamp
            $update_login = $con->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_login->bind_param("i", $user['user_id']);
            $update_login->execute();

            // Set success message and redirect
            $_SESSION['message'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
            header("Location: ../Homepage/homepage.php");
            exit();
        }
    }
    
    // Generic error message for security
    echo "<script>alert('Invalid username or password');</script>";
}

// Check for session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Business IMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #F5E7D1;
            margin: 0;
        }
        .login-container {
            display: flex;
            width: 700px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background-color: #FFFFFF;
            animation: fadeIn 0.5s ease-in-out;
        }
        .login-left {
            flex: 1;
            padding: 40px;
        }
        .login-right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #F78F40, #D8C3A5);
            color: #FFFFFF;
            text-align: center;
            padding: 20px;
        }
        .btn-custom {
            background: linear-gradient(135deg, #F78F40, #D8C3A5);
            border: none;
            color: #FFFFFF;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: linear-gradient(135deg, #D8C3A5, #F78F40);
        }
        .form-control {
            background-color: #EDEDED;
            border: 1px solid #D8C3A5;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #F78F40;
            box-shadow: 0 0 5px rgba(247, 143, 64, 0.5);
        }
        .form-check-label {
            color: #000000;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h3 class="fw-bold">Sign In</h3>
            <form id="loginForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" >
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text" id="username-icon"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="invalid-feedback">Please enter your username.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text" id="password-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
                <button type="submit" name="login" class="btn btn-custom w-100">Sign In</button>
            </form>
        </div>
        <div class="login-right">
            <div>
                <h3>Welcome to login</h3>
                <p>Don't have an account?</p>
                <a href="signup.php" class="btn btn-outline-light">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
    // Client-side validation
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.querySelector('#loginForm');
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
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