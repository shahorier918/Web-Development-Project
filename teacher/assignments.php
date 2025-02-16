<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireTeacher();

// Fetch all assignments for this teacher's courses
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, 
           (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assignments | Teacher Dashboard</title>
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
            <a href="assignments.php" class="nav-link active">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="grade_submissions.php" class="nav-link">
                <i class="fas fa-check-circle"></i> Grade Submissions
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>All Assignments</h2>
                        <div class="form-group mb-0">
                            <input type="text" id="assignmentSearch" class="form-control" placeholder="Search assignments...">
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($assignments)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tasks fa-3x mb-3 text-muted"></i>
                        <h4>No Assignments Yet</h4>
                        <p class="text-muted">Create assignments for your courses to get started.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="assignmentsTable">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Course</th>
                                        <th>Due Date</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($assignment['description'], 0, 50)) . '...'; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($assignment['course_name']); ?></td>
                                            <td>
                                                <?php 
                                                $due_date = strtotime($assignment['due_date']);
                                                $badge_class = time() > $due_date ? 'badge-danger' : 'badge-info';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo date('M d, Y', $due_date); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo $assignment['submission_count']; ?> submissions
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (time() > $due_date): ?>
                                                    <span class="badge badge-danger">Closed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Assignment search functionality
        document.getElementById('assignmentSearch').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#assignmentsTable tbody tr');
            
            tableRows.forEach(row => {
                let title = row.querySelector('strong').textContent.toLowerCase();
                let course = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                if (title.includes(searchText) || course.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete confirmation
        function confirmDelete(assignmentId) {
            document.getElementById('deleteId').value = assignmentId;
            $('#deleteModal').modal('show');
        }
    </script>
</body>
</html> 