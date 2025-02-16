<?php
require_once 'config/database.php';
require_once 'includes/session.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academy Connect - </title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('assets/images/education-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        
        .feature-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap mr-2"></i>Academy Connect
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isTeacher() ? 'teacher/dashboard.php' : 'student/dashboard.php'; ?>">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container text-center">
            <h1>Welcome to Academy Connect</h1>
            <p class="lead">Your comprehensive learning management solution</p>
            <?php if (!isLoggedIn()): ?>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg mr-3">Login</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Key Features</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-book-reader feature-icon"></i>
                            <h4 class="card-title">Course Management</h4>
                            <p class="card-text">Easily manage and access course materials, schedules, and resources.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-tasks feature-icon"></i>
                            <h4 class="card-title">Assignment Tracking</h4>
                            <p class="card-text">Submit and track assignments, with instant feedback from teachers.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h4 class="card-title">Grade Management</h4>
                            <p class="card-text">View and manage grades with detailed progress tracking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>About Our System</h2>
                    <p class="lead">The Student Management System is designed to streamline the educational process for both students and teachers.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success mr-2"></i> Easy-to-use interface</li>
                        <li><i class="fas fa-check text-success mr-2"></i> Secure data management</li>
                        <li><i class="fas fa-check text-success mr-2"></i> Real-time updates</li>
                        <li><i class="fas fa-check text-success mr-2"></i> Comprehensive reporting</li>
                    </ul>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p>Empowering education through technology</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Help & Support</a></li>
                        <li><a href="#" class="text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 