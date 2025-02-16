<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireStudent();

// Fetch enrolled courses with teacher names and stats
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name,
           (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as total_assignments,
           (SELECT COUNT(*) FROM submissions s 
            JOIN assignments a ON s.assignment_id = a.id 
            WHERE a.course_id = c.id AND s.student_id = ?) as completed_assignments
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id = ?
    ORDER BY c.start_date DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll();

// Get overall stats
$total_courses = count($enrolled_courses);
$total_assignments = 0;
$completed_assignments = 0;
foreach ($enrolled_courses as $course) {
    $total_assignments += $course['total_assignments'];
    $completed_assignments += $course['completed_assignments'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> | Student Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-image">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <span class="user-role">Student</span>
            </div>

            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="browse_courses.php" class="nav-link">
                    <i class="fas fa-book"></i> Browse Courses
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="assessmentDropdown" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt"></i> Assessments
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="assessmentDropdown">
                        <li>
                            <a class="dropdown-item" href="assessments.php?type=quiz">
                                <i class="fas fa-question-circle mr-2"></i>Quizzes
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="assessments.php?type=midterm">
                                <i class="fas fa-file-alt mr-2"></i>Midterms
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="assessments.php?type=final">
                                <i class="fas fa-graduation-cap mr-2"></i>Finals
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="assessments.php">
                                <i class="fas fa-list mr-2"></i>View All
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="view_grades.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> My Grades
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Dashboard</h1>
                <div class="top-bar-actions">
                    <div class="notification-bell">
                        <i class="far fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stats-card enrolled">
                    <div class="stats-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $total_courses; ?></h3>
                        <p>Enrolled Courses</p>
                    </div>
                </div>

                <div class="stats-card assignments">
                    <div class="stats-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $total_assignments; ?></h3>
                        <p>Total Assignments</p>
                    </div>
                </div>

                <div class="stats-card completed">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $completed_assignments; ?></h3>
                        <p>Completed Assignments</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Enrolled Courses -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>My Courses</h3>
                    </div>
                    <div class="courses-grid">
                        <?php if (empty($enrolled_courses)): ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open"></i>
                                <p>You haven't enrolled in any courses yet.</p>
                                <a href="browse_courses.php" class="btn-view">Browse Courses</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-item">
                                    <div class="course-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="course-info">
                                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                        <p>
                                            <i class="fas fa-user-tie"></i> 
                                            <?php echo htmlspecialchars($course['teacher_name']); ?>
                                        </p>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <?php 
                                            $progress = $course['total_assignments'] ? 
                                                ($course['completed_assignments'] / $course['total_assignments']) * 100 : 0;
                                            ?>
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </div>
                                    <a href="course_view.php?id=<?php echo $course['id']; ?>" class="btn-view">
                                        View Course
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($enrolled_courses as $course): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="activity-details">
                                    <h4>Course Enrolled</h4>
                                    <p><?php echo htmlspecialchars($course['course_name']); ?></p>
                                    <span class="activity-time">
                                        <?php echo date('M d, Y', strtotime($course['start_date'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
    // Add this JavaScript to ensure dropdowns work
    document.addEventListener('DOMContentLoaded', function() {
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
    </script>
</body>
</html> 