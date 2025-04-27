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

// Function to validate password strength
function is_password_strong($password) {
    // Password should be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number
    return (strlen($password) >= 8 && 
            preg_match('/[A-Z]/', $password) && 
            preg_match('/[a-z]/', $password) && 
            preg_match('/[0-9]/', $password));
}

// SIGNUP
if(isset($_POST['signup'])){
    $new_username = sanitize_input($_POST['new_username']);
    $new_email = sanitize_input($_POST['new_email']);
    $new_password = $_POST['new_password'];
    
    // Validate password strength
    if (!is_password_strong($new_password)) {
        echo "<script>alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');</script>";
        exit();
    }

    // Hash password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Validate email
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!');</script>";
        exit();
    }

    // Prepare statements to prevent SQL injection
    $username_exists = $con->prepare("SELECT * FROM users WHERE username = ?");
    $username_exists->bind_param("s", $new_username);
    $username_exists->execute();
    $username_result = $username_exists->get_result();

    $email_exists = $con->prepare("SELECT * FROM users WHERE email = ?");
    $email_exists->bind_param("s", $new_email);
    $email_exists->execute();
    $email_result = $email_exists->get_result();

    if($username_result->num_rows > 0){
        echo "<script>alert('Username already taken!');</script>";
    } elseif($email_result->num_rows > 0){
        echo "<script>alert('Email already registered!');</script>";
    } else {
        // Handle profile photo upload
        $profile_photo_name = null;
        if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_photo']['type'];
            
            if(in_array($file_type, $allowed_types)) {
                $profile_photo_name = time() . '_' . basename($_FILES['profile_photo']['name']);
                $target_path = "../User Profiles/" . $profile_photo_name;
                
                if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                    // File uploaded successfully
                } else {
                    echo "<script>alert('Error uploading profile photo!');</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid file type! Please upload a JPEG, PNG, or GIF file.');</script>";
                exit();
            }
        }

        // Insert new user
        $insert_user = $con->prepare("INSERT INTO users (username, email, password, profile_photo, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
        $insert_user->bind_param("ssss", $new_username, $new_email, $hashed_password, $profile_photo_name);
        
        if($insert_user->execute()){
            echo "<script>alert('Account registered successfully! Please log in.'); window.location.href = 'login.php';</script>";
        } else {
            echo "<script>alert('Error creating account. Please try again.');</script>";
        }
    }
}  
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Business IMS</title>
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
        .signup-container {
            display: flex;
            width: 700px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background-color: #FFFFFF;
            animation: fadeIn 0.5s ease-in-out;
        }
        .signup-right {
            flex: 1;
            padding: 40px;
        }
        .signup-left {
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
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-left">
            <div>
                <h3>Join Us Today</h3>
                <p>Already have an account?</p>
                <a href="login.php" class="btn btn-outline-light">Sign In</a>
            </div>
        </div>
        <div class="signup-right">
            <h3 class="fw-bold">Sign Up</h3>
            <form id="signupForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="new_username" placeholder="Username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="new_email" placeholder="Email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Password" required>
                    </div>
                    <small class="text-muted">Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" class="form-control" name="profile_photo" accept="image/*" required>
                </div>
                <button type="submit" name="signup" class="btn btn-custom w-100">Sign Up</button>
            </form>
        </div>
    </div>

    <script>
    // Client-side validation
    document.addEventListener('DOMContentLoaded', function() {
        const signupForm = document.querySelector('#signupForm');
        
        if(signupForm) {
            signupForm.addEventListener('submit', function(e) {
                const password = document.querySelector('#new_password').value;
                if(!isPasswordStrong(password)) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');
                }
            });
        }

        function isPasswordStrong(password) {
            return password.length >= 8 && 
                   /[A-Z]/.test(password) && 
                   /[a-z]/.test(password) && 
                   /[0-9]/.test(password);
        }
        
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