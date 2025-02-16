<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$course_id = $_GET['course_id'] ?? 0;

// Verify the teacher owns this course and get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "You don't have permission to add assignments to this course.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $max_points = $_POST['max_points'];
    $allow_attachments = isset($_POST['allow_attachments']) ? 1 : 0;
    
    $errors = [];
    
    // Validate due date is not in the past
    if (strtotime($due_date) < strtotime('today')) {
        $errors[] = "Due date cannot be in the past";
    }
    
    // Validate due date is within course duration
    if (strtotime($due_date) > strtotime($course['end_date'])) {
        $errors[] = "Due date cannot be after course end date";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO assignments (course_id, title, description, due_date, max_points, allow_attachments, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt->execute([$course_id, $title, $description, $due_date, $max_points, $allow_attachments])) {
                $_SESSION['success'] = "Assignment created successfully!";
                header("Location: course_details.php?id=$course_id");
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
    <title>Add Assignment | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher-styles.css">
    <link rel="stylesheet" href="../assets/css/course-management.css">
</head>
<body>
    <!-- Left Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p>Teacher</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="course_details.php?id=<?php echo $course_id; ?>" class="nav-link">
                <i class="fas fa-arrow-left"></i> Back to Course
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
                            <h4 class="mb-0">
                                <i class="fas fa-plus-circle mr-2"></i>Add New Assignment
                            </h4>
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

                            <div class="course-info mb-4">
                                <h5>Course: <?php echo htmlspecialchars($course['course_name']); ?></h5>
                                <p class="text-muted">
                                    Course Duration: 
                                    <?php echo date('M d, Y', strtotime($course['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($course['end_date'])); ?>
                                </p>
                            </div>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label><i class="fas fa-heading mr-2"></i>Assignment Title</label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                           placeholder="Enter assignment title">
                                    <div class="invalid-feedback">Please provide a title.</div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-align-left mr-2"></i>Description</label>
                                    <textarea name="description" class="form-control" rows="6" required
                                              placeholder="Enter assignment description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                    <div class="invalid-feedback">Please provide a description.</div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-calendar-alt mr-2"></i>Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required
                                               min="<?php echo date('Y-m-d'); ?>"
                                               max="<?php echo $course['end_date']; ?>"
                                               value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                                        <div class="invalid-feedback">Please select a valid due date.</div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-star mr-2"></i>Maximum Points</label>
                                        <input type="number" name="max_points" class="form-control" required
                                               min="1" max="100" 
                                               value="<?php echo isset($_POST['max_points']) ? htmlspecialchars($_POST['max_points']) : '100'; ?>">
                                        <div class="invalid-feedback">Please enter maximum points (1-100).</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="allowAttachments" 
                                               name="allow_attachments" <?php echo isset($_POST['allow_attachments']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="allowAttachments">
                                            <i class="fas fa-paperclip mr-2"></i>Allow File Attachments
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus-circle mr-2"></i>Create Assignment
                                    </button>
                                    <a href="course_details.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times-circle mr-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    </script>
</body>
</html> 