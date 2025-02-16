<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (isLoggedIn()) {
    header("Location: " . (isTeacher() ? 'teacher/dashboard.php' : 'student/dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    $errors = [];
    
    // Validation
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and contain only letters, numbers, and underscores";
    }
    
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters and contain at least one letter and one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Username or email already exists";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed_password, $role, $full_name, $email])) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Academy Connect</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth-modern.css">
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="auth-card">
        <h2 class="text-center mb-4">
            <i class="fas fa-graduation-cap mr-2"></i>Academy Connect
        </h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="registration-form">
            <div class="role-select">
                <div class="role-option" data-role="student">
                    <i class="fas fa-user-graduate"></i>
                    <div>Student</div>
                </div>
                <div class="role-option" data-role="teacher">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <div>Teacher</div>
                </div>
            </div>
            <input type="hidden" name="role" id="role" value="student">

            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>

            <div class="form-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
            </div>

            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn btn-auth">Create Account</button>

            <div class="auth-links">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </form>
    </div>

    <script>
        // Role selection handling
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('role').value = this.dataset.role;
            });
        });

        // Set default active role
        document.querySelector('.role-option[data-role="student"]').classList.add('active');
    </script>
</body>
</html> 