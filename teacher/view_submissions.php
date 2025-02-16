<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireTeacher();

$assignment_id = $_GET['id'] ?? 0;

// Fetch assignment details and verify teacher owns it
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, c.teacher_id 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = "Assignment not found or access denied.";
    header("Location: assignments.php");
    exit();
}

// Fetch submissions with student details
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submissions | Teacher Dashboard</title>
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
        <!-- Similar sidebar as other teacher pages -->
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
            <a href="assignments.php" class="nav-link">
                <i class="fas fa-tasks"></i> Assignments
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Assignment Details -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Assignment Details</h4>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($assignment['title']); ?></h5>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_name']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Max Points:</strong> <?php echo $assignment['max_points']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submissions List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt mr-2"></i>
                        Submissions (<?php echo count($submissions); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                            <h5>No Submissions Yet</h5>
                            <p class="text-muted">There are no submissions for this assignment yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Submitted At</th>
                                        <th>Status</th>
                                        <th>Grade</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($submission['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($submission['email']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($submission['grade'] !== null): ?>
                                                    <span class="badge badge-success">Graded</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($submission['grade'] !== null): ?>
                                                    <span class="badge badge-info">
                                                        <?php echo $submission['grade']; ?>/<?php echo $assignment['max_points']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" 
                                                            class="btn btn-info" 
                                                            onclick="viewSubmission(<?php echo $submission['id']; ?>)"
                                                            title="View Submission">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-primary"
                                                            onclick="gradeSubmission(<?php echo $submission['id']; ?>)"
                                                            title="Grade Submission">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                    <?php if ($submission['file_path']): ?>
                                                        <a href="download_submission.php?id=<?php echo $submission['id']; ?>" 
                                                           class="btn btn-secondary"
                                                           title="Download Attachment">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
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
    </div>

    <!-- View Submission Modal -->
    <div class="modal fade" id="viewSubmissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submission Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="submissionContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Submission Modal -->
    <div class="modal fade" id="gradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Grade Submission</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="gradeForm" action="grade_submission.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="submission_id" id="submissionId">
                        <div class="form-group">
                            <label>Grade (out of <?php echo $assignment['max_points']; ?>)</label>
                            <input type="number" name="grade" class="form-control" 
                                   min="0" max="<?php echo $assignment['max_points']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Feedback</label>
                            <textarea name="feedback" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Grade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function viewSubmission(submissionId) {
            // Load submission details via AJAX
            $.get('get_submission.php?id=' + submissionId, function(data) {
                $('#submissionContent').html(data);
                $('#viewSubmissionModal').modal('show');
            });
        }

        function gradeSubmission(submissionId) {
            $('#submissionId').val(submissionId);
            $('#gradeModal').modal('show');
        }
    </script>
</body>
</html> 