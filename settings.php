<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once("connection.php");

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Initialize messages
$success_message = '';
$error_message = '';

// Get current user data
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $con->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    
    // Validate input
    if (empty($new_username) || empty($new_email)) {
        $error_message = "Username and email are required fields.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if username already exists (excluding current user)
        $check_query = "SELECT * FROM users WHERE username = ? AND user_id != ?";
        $stmt = $con->prepare($check_query);
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Username already taken. Please choose another one.";
        } else {
            // Update user profile
            $update_query = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
            $stmt = $con->prepare($update_query);
            $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['username'] = $new_username;
                $username = $new_username;
                $user_data['username'] = $new_username;
                $user_data['email'] = $new_email;
                $success_message = "Profile updated successfully.";
            } else {
                $error_message = "Failed to update profile: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $error_message = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        // Verify current password
        if (password_verify($current_password, $user_data['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $con->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Password updated successfully.";
            } else {
                $error_message = "Failed to update password: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}

// Handle profile photo upload
if (isset($_POST['update_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
            $error_message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $profile_photo_name = time() . '_' . basename($_FILES['profile_photo']['name']);
            $profile_dir = __DIR__ . "/User Profiles";
            
            if (!file_exists($profile_dir)) {
                mkdir($profile_dir, 0777, true);
            }
            
            $target_path = $profile_dir . "/" . $profile_photo_name;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                // Delete old profile photo if exists
                if (!empty($user_data['profile_photo'])) {
                    $old_photo_path = $profile_dir . "/" . $user_data['profile_photo'];
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                
                // Update profile photo in database
                $update_query = "UPDATE users SET profile_photo = ? WHERE user_id = ?";
                $stmt = $con->prepare($update_query);
                $stmt->bind_param("si", $profile_photo_name, $user_id);
                
                if ($stmt->execute()) {
                    $user_data['profile_photo'] = $profile_photo_name;
                    $success_message = "Profile photo updated successfully.";
                } else {
                    $error_message = "Failed to update profile photo in database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Failed to upload profile photo.";
            }
        }
    } else {
        $error_message = "Please select a photo to upload.";
    }
}

// Handle theme preference update
if (isset($_POST['update_theme'])) {
    $theme_preference = $_POST['theme_mode'];
    
    // Store theme preference in session and cookie
    $_SESSION['theme_preference'] = $theme_preference;
    setcookie('theme_preference', $theme_preference, time() + (86400 * 30), "/"); // 30 days
    
    $success_message = "Theme preference updated.";
}

// Get current theme preference
$theme_preference = $_SESSION['theme_preference'] ?? ($_COOKIE['theme_preference'] ?? 'light');
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme_preference; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Business IMS</title>
    
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
        
        /* Settings Cards */
        .settings-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .settings-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
        }
        
        .profile-photo-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .current-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--accent);
            margin-right: 20px;
            overflow: hidden;
        }
        
        .current-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .current-photo i {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--text-light);
        }
        
        .theme-toggle-container {
            margin-bottom: 20px;
        }
        
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-top: 0;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        
        .btn-primary {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        
        .btn-primary:hover {
            background-color: #e67e22;
            border-color: #e67e22;
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
                <a href="dashboard/dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="products/products.php">
                    <i class="fas fa-boxes"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="suppliers/suppliers.php">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>
            <li>
                <a href="pos/pos.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Point of Sale</span>
                </a>
            </li>
            <li>
                <a href="ai-insights/insights.php">
                    <i class="fas fa-lightbulb"></i>
                    <span>AI Insights</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="signout">
            <a href="auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Settings</h1>
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
        
        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Theme Settings -->
            <div class="col-md-6">
                <div class="settings-card">
                    <h3>Theme Settings</h3>
                    <form action="settings.php" method="POST">
                        <div class="theme-toggle-container">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="themeToggle" <?php echo $theme_preference === 'dark' ? 'checked' : ''; ?>>
                                <input type="hidden" name="theme_mode" id="themeMode" value="<?php echo $theme_preference; ?>">
                                <label class="form-check-label" for="themeToggle">Dark Mode</label>
                            </div>
                        </div>
                        <button type="submit" name="update_theme" class="btn btn-primary">Save Theme Preference</button>
                    </form>
                </div>
            </div>
            
            <!-- Profile Photo -->
            <div class="col-md-6">
                <div class="settings-card">
                    <h3>Profile Photo</h3>
                    <div class="profile-photo-container">
                        <div class="current-photo">
                            <?php if (!empty($user_data['profile_photo'])): ?>
                                <img src="User Profiles/<?php echo htmlspecialchars($user_data['profile_photo']); ?>" alt="Profile Photo">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p>Current profile photo</p>
                        </div>
                    </div>
                    <form action="settings.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profilePhoto" class="form-label">Upload new photo</label>
                            <input type="file" class="form-control" id="profilePhoto" name="profile_photo" accept="image/*">
                            <div class="form-text">Allowed formats: JPG, PNG, GIF. Max size: 5MB</div>
                        </div>
                        <button type="submit" name="update_photo" class="btn btn-primary">Update Photo</button>
                    </form>
                </div>
            </div>
            
            <!-- Profile Settings -->
            <div class="col-md-6">
                <div class="settings-card">
                    <h3>Profile Information</h3>
                    <form action="settings.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user_data['role'])); ?>" readonly>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <!-- Password Change -->
            <div class="col-md-6">
                <div class="settings-card">
                    <h3>Change Password</h3>
                    <form action="settings.php" method="POST">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <div class="form-text">Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.</div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme Toggle JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeModeInput = document.getElementById('themeMode');
            const html = document.querySelector('html');
            
            // Toggle theme when checkbox is clicked
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    html.setAttribute('data-theme', 'dark');
                    themeModeInput.value = 'dark';
                } else {
                    html.setAttribute('data-theme', 'light');
                    themeModeInput.value = 'light';
                }
            });
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