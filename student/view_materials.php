<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireStudent();

$course_id = $_GET['course_id'] ?? 0;

// Verify student is enrolled in the course
$stmt = $pdo->prepare("
    SELECT c.* FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE c.id = ? AND e.student_id = ?
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or not enrolled.";
    header("Location: dashboard.php");
    exit();
}

// Fetch course materials
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name as teacher_name 
    FROM course_materials m
    JOIN courses c ON m.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE m.course_id = ?
    ORDER BY m.uploaded_at DESC
");
$stmt->execute([$course_id]);
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course Materials | Student Dashboard</title>
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
        <!-- ... (similar to other student pages) ... -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-book mr-2"></i>Course Materials
                        <small class="text-muted">- <?php echo htmlspecialchars($course['course_name']); ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (empty($materials)): ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <h4>No Materials Available</h4>
                                <p class="text-muted">No course materials have been uploaded yet.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($materials as $material): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($material['title']); ?></h5>
                                            <p class="mb-1"><?php echo htmlspecialchars($material['description']); ?></p>
                                            <small class="text-muted">
                                                Uploaded by <?php echo htmlspecialchars($material['teacher_name']); ?> 
                                                on <?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?>
                                            </small>
                                        </div>
                                        <a href="../download.php?type=material&id=<?php echo $material['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-download mr-2"></i>Download
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 