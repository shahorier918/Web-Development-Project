<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

// Fetch all courses for this teacher
$stmt = $pdo->prepare("
    SELECT id, course_name 
    FROM courses 
    WHERE teacher_id = ? 
    ORDER BY course_name
");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Fetch existing assessments
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name,
           (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
           (SELECT COUNT(DISTINCT student_id) FROM assessment_responses WHERE assessment_id = a.id) as submission_count
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$assessments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assessments | Teacher Dashboard</title>
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
        <!-- Similar to other pages -->
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
            <a href="my_courses.php" class="nav-link">
                <i class="fas fa-book"></i> My Courses
            </a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle active" href="#" id="assessmentDropdown" role="button" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-alt"></i> Assessments
                </a>
                <div class="dropdown-menu" aria-labelledby="assessmentDropdown">
                    <a class="dropdown-item" href="create_assessment.php?type=quiz">Quiz</a>
                    <a class="dropdown-item" href="create_assessment.php?type=midterm">Midterm</a>
                    <a class="dropdown-item" href="create_assessment.php?type=final">Final</a>
                </div>
            </div>
            <!-- Other nav items -->
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Assessments</h2>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="createAssessmentBtn" 
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-plus mr-2"></i>Create Assessment
                            </button>
                            <div class="dropdown-menu" aria-labelledby="createAssessmentBtn">
                                <a class="dropdown-item" href="create_assessment.php?type=quiz">
                                    <i class="fas fa-question-circle mr-2"></i>Quiz
                                </a>
                                <a class="dropdown-item" href="create_assessment.php?type=midterm">
                                    <i class="fas fa-file-alt mr-2"></i>Midterm
                                </a>
                                <a class="dropdown-item" href="create_assessment.php?type=final">
                                    <i class="fas fa-graduation-cap mr-2"></i>Final
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assessment List -->
            <?php if (empty($assessments)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                        <h4>No Assessments Created</h4>
                        <p class="text-muted">Start by creating a quiz, midterm, or final assessment.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Type</th>
                                        <th>Duration</th>
                                        <th>Questions</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assessments as $assessment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($assessment['title']); ?></td>
                                            <td><?php echo htmlspecialchars($assessment['course_name']); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucfirst($assessment['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $assessment['duration']; ?> mins</td>
                                            <td><?php echo $assessment['question_count']; ?> questions</td>
                                            <td><?php echo $assessment['submission_count']; ?> submissions</td>
                                            <td>
                                                <?php
                                                $now = new DateTime();
                                                $start = new DateTime($assessment['start_time']);
                                                $end = new DateTime($assessment['end_time']);
                                                
                                                if ($now < $start) {
                                                    echo '<span class="badge badge-warning">Upcoming</span>';
                                                } elseif ($now > $end) {
                                                    echo '<span class="badge badge-secondary">Closed</span>';
                                                } else {
                                                    echo '<span class="badge badge-success">Active</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_assessment.php?id=<?php echo $assessment['id']; ?>" 
                                                       class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="view_assessment_submissions.php?id=<?php echo $assessment['id']; ?>" 
                                                       class="btn btn-success" title="View Submissions">
                                                        <i class="fas fa-users"></i>
                                                        <?php if ($assessment['submission_count'] > 0): ?>
                                                            <span class="badge badge-light ml-1"><?php echo $assessment['submission_count']; ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                    <a href="edit_assessment.php?id=<?php echo $assessment['id']; ?>" 
                                                       class="btn btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="confirmDeleteAssessment(<?php echo $assessment['id']; ?>)"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                    <p>Are you sure you want to delete this assessment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" action="delete_assessment.php" method="POST">
                        <input type="hidden" name="assessment_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete Assessment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function confirmDelete(assessmentId) {
            document.getElementById('deleteId').value = assessmentId;
            $('#deleteModal').modal('show');
        }
    </script>
</body>
</html> 