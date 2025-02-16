<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireStudent();

// Get assessment type from URL if specified
$type = $_GET['type'] ?? null;

// Fetch enrolled courses
$stmt = $pdo->prepare("
    SELECT c.id FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$course_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($course_ids)) {
    $assessments = [];
} else {
    // Build the query based on assessment type
    $query = "
        SELECT a.*, c.course_name,
               (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
               (SELECT COUNT(*) FROM assessment_responses WHERE assessment_id = a.id AND student_id = ?) as has_attempted
        FROM assessments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.course_id IN (" . str_repeat('?,', count($course_ids) - 1) . "?)
    ";
    
    if ($type) {
        $query .= " AND a.type = ?";
    }
    $query .= " ORDER BY a.start_time DESC";
    
    $params = array_merge([$_SESSION['user_id']], $course_ids);
    if ($type) {
        $params[] = $type;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assessments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assessments | Student Dashboard</title>
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
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p>Student</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="browse_courses.php" class="nav-link">
                <i class="fas fa-book"></i> Browse Courses
            </a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle active" href="#" id="assessmentDropdown" role="button" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-alt"></i> Assessments
                </a>
                <div class="dropdown-menu" aria-labelledby="assessmentDropdown">
                    <a class="dropdown-item <?php echo $type === 'quiz' ? 'active' : ''; ?>" 
                       href="?type=quiz">
                        <i class="fas fa-question-circle mr-2"></i>Quizzes
                    </a>
                    <a class="dropdown-item <?php echo $type === 'midterm' ? 'active' : ''; ?>" 
                       href="?type=midterm">
                        <i class="fas fa-file-alt mr-2"></i>Midterms
                    </a>
                    <a class="dropdown-item <?php echo $type === 'final' ? 'active' : ''; ?>" 
                       href="?type=final">
                        <i class="fas fa-graduation-cap mr-2"></i>Finals
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item <?php echo !$type ? 'active' : ''; ?>" href="assessments.php">
                        <i class="fas fa-list mr-2"></i>View All
                    </a>
                </div>
            </div>
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
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <?php
                                if ($type) {
                                    echo ucfirst($type) . " Assessments";
                                } else {
                                    echo "All Assessments";
                                }
                                ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assessments)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <h5>No assessments available</h5>
                                    <p class="text-muted">There are no assessments to display at this time.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($assessments as $assessment): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($assessment['title']); ?></h6>
                                                    <p class="mb-1 small">
                                                        <i class="fas fa-book mr-1"></i><?php echo htmlspecialchars($assessment['course_name']); ?>
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-clock mr-1"></i><?php echo $assessment['duration']; ?> minutes
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-star mr-1"></i><?php echo $assessment['total_marks']; ?> marks
                                                        <span class="mx-2">|</span>
                                                        <i class="fas fa-question-circle mr-1"></i><?php echo $assessment['question_count']; ?> questions
                                                    </p>
                                                    <small class="text-muted">
                                                        Available: <?php echo date('M d, Y h:i A', strtotime($assessment['start_time'])); ?> - 
                                                        <?php echo date('M d, Y h:i A', strtotime($assessment['end_time'])); ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?php
                                                    $now = new DateTime();
                                                    $start = new DateTime($assessment['start_time']);
                                                    $end = new DateTime($assessment['end_time']);
                                                    
                                                    if ($assessment['has_attempted']): ?>
                                                        <span class="badge badge-success">Completed</span>
                                                    <?php elseif ($now < $start): ?>
                                                        <span class="badge badge-warning">Upcoming</span>
                                                    <?php elseif ($now > $end): ?>
                                                        <span class="badge badge-danger">Closed</span>
                                                    <?php else: ?>
                                                        <a href="take_assessment.php?id=<?php echo $assessment['id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-pen mr-1"></i>Take Assessment
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>