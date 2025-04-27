<?php
session_start();

// Check if the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// If the user confirmed logout
if (isset($_POST['confirm_logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// If the user canceled logout
if (isset($_POST['cancel_logout'])) {
    // Redirect back to the homepage
    header("Location: ../dashboard/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Business IMS</title>
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
        .logout-container {
            width: 400px;
            padding: 30px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background-color: #FFFFFF;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #F78F40, #D8C3A5);
            border: none;
            color: #FFFFFF;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #D8C3A5, #F78F40);
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h3 class="mb-4"><i class="bi bi-box-arrow-right text-danger me-2"></i>Logout Confirmation</h3>
        <p class="mb-4">Are you sure you want to logout from your account?</p>
        
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="d-flex justify-content-center gap-3">
            <button type="submit" name="confirm_logout" class="btn btn-primary">Yes, Logout</button>
            <button type="submit" name="cancel_logout" class="btn btn-secondary">Cancel</button>
        </form>
    </div>

    <!-- Auto-show Modal on Page Load -->
    <div class="modal fade" id="logoutModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to logout? This will end your current session.</p>
                </div>
                <div class="modal-footer">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="w-100 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.href='../dashboard/dashboard.php';">Cancel</button>
                        <button type="submit" name="confirm_logout" class="btn btn-primary">Yes, Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show the modal automatically when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
            
            // Add event listener for the Cancel button in the modal
            document.querySelector('#logoutModal .btn-secondary').addEventListener('click', function() {
                window.location.href = '../dashboard/dashboard.php';
            });
        });
    </script>
</body>
</html>
