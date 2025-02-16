<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireLogin();

if (!isStudent()) {
    header("Location: ../index.php");
    exit();
}

// Fetch all grades for the student
$stmt = $pdo->prepare("
    SELECT s.grade, s.feedback, a.title as assignment_title, 
           c.course_name, s.submitted_at
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Grades</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="dashboard.php">Student Dashboard</a>
        <div class="navbar-nav ml-auto">
            <a class="nav-item nav-link" href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>My Grades</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Assignment</th>
                        <th>Grade</th>
                        <th>Feedback</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($grade['assignment_title']); ?></td>
                        <td><?php echo $grade['grade'] ? $grade['grade'] . '%' : 'Not graded'; ?></td>
                        <td><?php echo nl2br(htmlspecialchars($grade['feedback'] ?? '')); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($grade['submitted_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 