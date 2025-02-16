<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireStudent();

// Get all available courses
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name, 
    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
    FROM courses c 
    JOIN users u ON c.teacher_id = u.id 
    WHERE c.end_date >= CURRENT_DATE
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();

// Check if student is already enrolled in any courses
$stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Courses | Student Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student-styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap mr-2"></i>Student Dashboard
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
        <div class="row">
            <div class="col-12">
                <h2 class="section-title">
                    <i class="fas fa-book-reader mr-2"></i>Available Courses
                </h2>
            </div>
        </div>

        <div class="row">
            <?php if (empty($courses)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>No courses are currently available.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                </h5>
                                <p class="card-text description"><?php echo htmlspecialchars($course['description']); ?></p>
                                <div class="course-details">
                                    <p><i class="fas fa-user-tie mr-2"></i><?php echo htmlspecialchars($course['teacher_name']); ?></p>
                                    <p><i class="fas fa-users mr-2"></i><?php echo $course['student_count']; ?> students enrolled</p>
                                    <p><i class="fas fa-calendar-alt mr-2"></i><?php echo date('M d, Y', strtotime($course['start_date'])); ?> - <?php echo date('M d, Y', strtotime($course['end_date'])); ?></p>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <?php if (in_array($course['id'], $enrolled_courses)): ?>
                                    <button class="btn btn-success btn-block" disabled>
                                        <i class="fas fa-check-circle mr-2"></i>Enrolled
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary btn-block" 
                                            onclick="showEnrollModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')">
                                        <i class="fas fa-sign-in-alt mr-2"></i>Enroll Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Course Enrollment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="enrollmentForm" action="enroll_course.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="courseId">
                        <p id="enrollmentMessage"></p>
                        <div class="form-group">
                            <label>Enrollment Key</label>
                            <input type="text" name="enrollment_key" class="form-control" required>
                            <small class="form-text text-muted">Please enter the enrollment key provided by your teacher</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Enroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showEnrollModal(courseId, courseName) {
            document.getElementById('courseId').value = courseId;
            document.getElementById('enrollmentMessage').innerHTML = 
                `You are about to enroll in <strong>${courseName}</strong>. Please enter the enrollment key to continue.`;
            $('#enrollModal').modal('show');
        }
    </script>
</body>
</html> 