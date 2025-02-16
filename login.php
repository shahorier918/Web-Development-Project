<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if (isLoggedIn()) {
    header("Location: " . (isTeacher() ? 'teacher/dashboard.php' : 'student/dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        header("Location: " . ($role === 'teacher' ? 'teacher/dashboard.php' : 'student/dashboard.php'));
        exit();
    } else {
        $error = "Invalid credentials or role mismatch";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Academy Connect</title>
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

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
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
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-auth">Login</button>

            <div class="auth-links">
                <a href="register.php">Don't have an account? Register</a>
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