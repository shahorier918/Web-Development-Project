<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireStudent();

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email is already taken";
    }
    
    // If password change is requested
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, address = ?, password = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $full_name,
                    $email,
                    $phone,
                    $address,
                    password_hash($new_password, PASSWORD_DEFAULT),
                    $_SESSION['user_id']
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $full_name,
                    $email,
                    $phone,
                    $address,
                    $_SESSION['user_id']
                ]);
            }
            
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile | Student Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Left Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p>Student</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="browse_courses.php" class="nav-link">
                <i class="fas fa-book"></i> Browse Courses
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Edit Profile</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success">
                                    <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                    ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="profile-form">
                                <div class="form-group">
                                    <label><i class="fas fa-user mr-2"></i>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-envelope mr-2"></i>Email</label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-phone mr-2"></i>Phone Number</label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-map-marker-alt mr-2"></i>Address</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>

                                <hr>

                                <h5 class="mb-3">Change Password</h5>
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" class="form-control">
                                    <small class="form-text text-muted">Leave blank if you don't want to change password</small>
                                </div>

                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 