<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $enrollment_key = trim($_POST['enrollment_key']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Add error handling and validation
    $errors = [];
    
    // Validate dates
    if (strtotime($end_date) < strtotime($start_date)) {
        $errors[] = "End date cannot be before start date";
    }
    
    // Validate course code uniqueness
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Course code already exists. Please choose a different one.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (teacher_id, course_name, course_code, enrollment_key, description, start_date, end_date) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $course_name, $course_code, $enrollment_key, $description, $start_date, $end_date])) {
                $_SESSION['success'] = "Course created successfully!";
                header("Location: dashboard.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Course | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher-styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chalkboard-teacher mr-2"></i>Teacher Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home mr-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle mr-2"></i>Create New Course
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

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group">
                            <label><i class="fas fa-book mr-2"></i>Course Name</label>
                            <input type="text" name="course_name" class="form-control" required 
                                   value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : ''; ?>"
                                   placeholder="Enter course name">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><i class="fas fa-hashtag mr-2"></i>Course Code</label>
                                <input type="text" name="course_code" class="form-control" required 
                                       pattern="[A-Za-z0-9-]+" title="Only letters, numbers, and hyphens allowed"
                                       value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>"
                                       placeholder="e.g., CS101">
                                <small class="form-text text-muted">Unique identifier for the course (e.g., CS101, MATH-202)</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label><i class="fas fa-key mr-2"></i>Enrollment Key</label>
                                <input type="text" name="enrollment_key" class="form-control" required 
                                       value="<?php echo isset($_POST['enrollment_key']) ? htmlspecialchars($_POST['enrollment_key']) : ''; ?>"
                                       placeholder="Enter enrollment key">
                                <small class="form-text text-muted">Key required for students to enroll in this course</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-2"></i>Description</label>
                            <textarea name="description" class="form-control" rows="4" required 
                                      placeholder="Enter course description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                                </div class="form-group">
                            <label><i class="fas fa-align-left mr-2"></i>Define Sem</label>
                            <textarea name="description" class="form-control" rows="4" required 
                                      placeholder="Enter course description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><i class="fas fa-calendar-alt mr-2"></i>Start Date</label>
                                <input type="date" name="start_date" class="form-control" required
                                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label><i class="fas fa-calendar-alt mr-2"></i>End Date</label>
                                <input type="date" name="end_date" class="form-control" required
                                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle mr-2"></i>Create Course
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-times-circle mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 