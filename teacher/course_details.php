<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireTeacher();

$course_id = $_GET['id'] ?? 0;

// Verify the teacher owns this course and get course details
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrolled_students,
           (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
    FROM courses c 
    WHERE c.id = ? AND c.teacher_id = ?
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Fetch assignments for this course
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
    FROM assignments a 
    WHERE a.course_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$course_id]);
$assignments = $stmt->fetchAll();

// Fetch enrolled students
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, e.enrollment_date
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    WHERE e.course_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$course_id]);
$enrolled_students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course Details | Teacher Dashboard</title>
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
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="add_assignment.php?course_id=<?php echo $course_id; ?>" class="nav-link">
                <i class="fas fa-plus"></i> Add Assignment
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

        <!-- Course Overview -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                    <span class="badge badge-light"><?php echo htmlspecialchars($course['course_code']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p class="course-description"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                        <div class="course-details">
                            <p><i class="fas fa-calendar-alt mr-2"></i>Duration: 
                                <?php echo date('M d, Y', strtotime($course['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($course['end_date'])); ?>
                            </p>
                            <p><i class="fas fa-key mr-2"></i>Enrollment Key: 
                                <code><?php echo htmlspecialchars($course['enrollment_key']); ?></code>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span class="stat-value"><?php echo $course['enrolled_students']; ?></span>
                                <span class="stat-label">Students</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-tasks"></i>
                                <span class="stat-value"><?php echo $course['assignment_count']; ?></span>
                                <span class="stat-label">Assignments</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Assignments</h5>
                    <a href="add_assignment.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-2"></i>Add Assignment
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No assignments created yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Max Points</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo strtotime($assignment['due_date']) < time() ? 'badge-danger' : 'badge-info'; ?>">
                                                <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $assignment['max_points']; ?> points</td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo $assignment['submission_count']; ?> submissions
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_submissions.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="btn btn-info" title="View Submissions">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="btn btn-warning" title="Edit Assignment">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" title="Delete Assignment"
                                                        onclick="confirmDelete(<?php echo $assignment['id']; ?>)">
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

        <!-- Course Materials Section -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Course Materials</h5>
                    <a href="upload_material.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload mr-2"></i>Upload Material
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Fetch course materials
                $stmt = $pdo->prepare("
                    SELECT * FROM course_materials 
                    WHERE course_id = ? 
                    ORDER BY uploaded_at DESC
                ");
                $stmt->execute([$course_id]);
                $materials = $stmt->fetchAll();
                ?>

                <?php if (empty($materials)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No materials uploaded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($material['title']); ?></td>
                                        <td><?php echo htmlspecialchars($material['description']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo strtoupper($material['file_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo formatFileSize($material['file_size']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../download.php?type=material&id=<?php echo $material['id']; ?>" 
                                                   class="btn btn-info" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" title="Delete Material"
                                                        onclick="confirmDeleteMaterial(<?php echo $material['id']; ?>)">
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

        <!-- Enrolled Students Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Enrolled Students</h5>
            </div>
            <div class="card-body">
                <?php if (empty($enrolled_students)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No students enrolled yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Enrolled Date</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolled_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                        <td>
                                            <a href="student_progress.php?course_id=<?php echo $course_id; ?>&student_id=<?php echo $student['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-chart-line mr-1"></i>View Progress
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add this button in the course actions section -->
        <a href="view_course_assessments.php?course_id=<?php echo $course['id']; ?>" 
           class="btn btn-info">
            <i class="fas fa-file-alt mr-2"></i>View Assessments
        </a>
    </div>

    <!-- Delete Assignment Modal -->
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
                    <p>Are you sure you want to delete this assignment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" action="delete_assignment.php" method="POST" style="display: inline;">
                        <input type="hidden" name="assignment_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete Assignment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal for material deletion -->
    <div class="modal fade" id="deleteMaterialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this material? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteMaterialForm" action="delete_material.php" method="POST" style="display: inline;">
                        <input type="hidden" name="material_id" id="deleteMaterialId">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <button type="submit" class="btn btn-danger">Delete Material</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function confirmDelete(assignmentId) {
            document.getElementById('deleteId').value = assignmentId;
            $('#deleteModal').modal('show');
        }
    </script>
</body>
</html> 