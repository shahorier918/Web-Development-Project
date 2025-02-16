<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireTeacher();

// Fetch all courses for this teacher
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_students,
           (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
    FROM courses c 
    WHERE c.teacher_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Courses | Teacher Dashboard</title>
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
            <a href="my_courses.php" class="nav-link active">
                <i class="fas fa-book"></i> My Courses
            </a>
            <a href="add_course.php" class="nav-link">
                <i class="fas fa-plus"></i> Create Course
            </a>
            <a href="assignments.php" class="nav-link">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="grade_submissions.php" class="nav-link">
                <i class="fas fa-check-circle"></i> Grade Submissions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>My Courses</h2>
                        <a href="add_course.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Create New Course
                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($courses)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                        <h4>No Courses Yet</h4>
                        <p class="text-muted">Start by creating your first course</p>
                        <a href="add_course.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Create Course
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                    </h5>
                                    <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                                    <div class="course-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-users mr-2"></i>
                                            <?php echo $course['enrolled_students']; ?> Students
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-tasks mr-2"></i>
                                            <?php echo $course['assignment_count']; ?> Assignments
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <?php echo date('M d, Y', strtotime($course['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($course['end_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100">
                                        <a href="course_details.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye mr-1"></i> View Details
                                        </a>
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 