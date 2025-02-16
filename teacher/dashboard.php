<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

// Fetch courses with enrollment counts and assignment counts
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

// Get total number of students across all courses
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.student_id) as total_students,
           COUNT(DISTINCT a.id) as total_assignments
    FROM courses c 
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assignments a ON c.id = a.course_id
    WHERE c.teacher_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher-styles.css">
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
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="my_courses.php" class="nav-link">
                <i class="fas fa-book"></i> My Courses
            </a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="assessmentDropdown" role="button" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-alt"></i> Assessments
                </a>
                <div class="dropdown-menu" aria-labelledby="assessmentDropdown">
                    <a class="dropdown-item" href="create_assessment.php?type=quiz">
                        <i class="fas fa-question-circle mr-2"></i>Quiz
                    </a>
                    <a class="dropdown-item" href="create_assessment.php?type=midterm">
                        <i class="fas fa-file-alt mr-2"></i>Midterm
                    </a>
                    <a class="dropdown-item" href="create_assessment.php?type=final">
                        <i class="fas fa-graduation-cap mr-2"></i>Final
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="assessments.php">
                        <i class="fas fa-list mr-2"></i>View All
                    </a>
                </div>
            </div>
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Courses</h6>
                                <h2 class="mb-0"><?php echo count($courses); ?></h2>
                            </div>
                            <div class="stats-icon courses">
                                <i class="fas fa-book-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Students</h6>
                                <h2 class="mb-0"><?php echo $stats['total_students']; ?></h2>
                            </div>
                            <div class="stats-icon students">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total Assignments</h6>
                                <h2 class="mb-0"><?php echo $stats['total_assignments']; ?></h2>
                            </div>
                            <div class="stats-icon assignments">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <a href="add_course.php" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-plus mr-2"></i>Create New Course
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses List -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Courses</h4>
                    <div class="form-group mb-0">
                        <input type="text" id="courseSearch" class="form-control" placeholder="Search courses...">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-book-open fa-3x mb-3"></i>
                            <h4>No Courses Yet</h4>
                            <p class="text-muted">Start by creating your first course</p>
                            <a href="add_course.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Create Course
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Enrollment Key</th>
                                    <th>Students</th>
                                    <th>Assignments</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="course-icon mr-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                                    <small class="text-muted">Created <?php echo date('M d, Y', strtotime($course['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($course['course_code']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($course['enrollment_key']); ?></code></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <i class="fas fa-users mr-1"></i>
                                                <?php echo $course['enrolled_students']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-tasks mr-1"></i>
                                                <?php echo $course['assignment_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d', strtotime($course['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($course['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="course_details.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="add_assignment.php?course_id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-success" title="Add Assignment">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-warning" title="Edit Course">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="confirmDelete(<?php echo $course['id']; ?>)"
                                                        title="Delete Course">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this course? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" action="delete_course.php" method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete Course</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Course search functionality
        document.getElementById('courseSearch').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#coursesTable tbody tr');
            
            tableRows.forEach(row => {
                let courseName = row.querySelector('h6').textContent.toLowerCase();
                let courseCode = row.querySelector('.badge-primary').textContent.toLowerCase();
                
                if (courseName.includes(searchText) || courseCode.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete confirmation
        function confirmDelete(courseId) {
            document.getElementById('deleteId').value = courseId;
            $('#deleteModal').modal('show');
        }

        // Initialize dropdowns
        $(document).ready(function() {
            // Enable Bootstrap dropdowns
            $('.dropdown-toggle').dropdown();
            
            // Keep dropdown menu open when clicking inside
            $('.dropdown-menu').click(function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html> 