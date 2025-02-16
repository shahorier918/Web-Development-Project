<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/helpers.php';
requireStudent();

$course_id = $_GET['id'] ?? 0;

// Verify enrollment
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name 
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    JOIN users u ON c.teacher_id = u.id
    WHERE c.id = ? AND e.student_id = ?
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or not enrolled.";
    header("Location: dashboard.php");
    exit();
}

// Fetch assignments
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submitted
    FROM assignments a 
    WHERE a.course_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$assignments = $stmt->fetchAll();

// Fetch materials
$stmt = $pdo->prepare("
    SELECT * FROM course_materials 
    WHERE course_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt->execute([$course_id]);
$materials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($course['course_name']); ?> | Student Dashboard</title>
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
                <h4 class="mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                        <p><strong>Teacher:</strong> <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                        <p><strong>Duration:</strong> 
                            <?php echo date('M d, Y', strtotime($course['start_date'])); ?> - 
                            <?php echo date('M d, Y', strtotime($course['end_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Assignments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No assignments available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                        <small class="text-muted">
                                            Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                            | Points: <?php echo $assignment['max_points']; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if ($assignment['submitted']): ?>
                                            <span class="badge badge-success">Submitted</span>
                                        <?php else: ?>
                                            <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload mr-2"></i>Submit
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

        <!-- Assessments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Assessments</h5>
            </div>
            <div class="card-body">
                <?php
                // Fetch assessments for this course
                $stmt = $pdo->prepare("
                    SELECT a.*, 
                           (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
                           (SELECT COUNT(*) FROM assessment_responses WHERE assessment_id = a.id AND student_id = ?) as has_submitted
                    FROM assessments a
                    WHERE a.course_id = ?
                    ORDER BY a.type, a.start_time ASC
                ");
                $stmt->execute([$_SESSION['user_id'], $course_id]);
                $assessments = $stmt->fetchAll();

                // Group assessments by type
                $grouped_assessments = [
                    'quiz' => [],
                    'midterm' => [],
                    'final' => []
                ];
                foreach ($assessments as $assessment) {
                    $grouped_assessments[$assessment['type']][] = $assessment;
                }
                ?>

                <?php if (empty($assessments)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No assessments available yet.</p>
                    </div>
                <?php else: ?>
                    <!-- Quizzes -->
                    <?php if (!empty($grouped_assessments['quiz'])): ?>
                        <div class="assessment-category mb-4">
                            <h6 class="text-primary mb-3">Quizzes</h6>
                            <div class="list-group">
                                <?php foreach ($grouped_assessments['quiz'] as $quiz): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                                <p class="mb-1 small">
                                                    <i class="fas fa-clock mr-1"></i><?php echo $quiz['duration']; ?> minutes
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-star mr-1"></i><?php echo $quiz['total_marks']; ?> marks
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-question-circle mr-1"></i><?php echo $quiz['question_count']; ?> questions
                                                </p>
                                                <small class="text-muted">
                                                    Available: <?php echo date('M d, Y h:i A', strtotime($quiz['start_time'])); ?> - 
                                                    <?php echo date('M d, Y h:i A', strtotime($quiz['end_time'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php
                                                $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
                                                $start = new DateTime($quiz['start_time'], new DateTimeZone('Asia/Dhaka'));
                                                $end = new DateTime($quiz['end_time'], new DateTimeZone('Asia/Dhaka'));
                                                
                                                if ($quiz['has_submitted']): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($now < $start): ?>
                                                    <span class="badge badge-warning">Upcoming</span>
                                                <?php elseif ($now > $end): ?>
                                                    <span class="badge badge-danger">Closed</span>
                                                <?php else: ?>
                                                    <a href="take_assessment.php?id=<?php echo $quiz['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-pen mr-1"></i>Take Quiz
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Midterms -->
                    <?php if (!empty($grouped_assessments['midterm'])): ?>
                        <div class="assessment-category mb-4">
                            <h6 class="text-info mb-3">Midterm Exams</h6>
                            <div class="list-group">
                                <?php foreach ($grouped_assessments['midterm'] as $midterm): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($midterm['title']); ?></h6>
                                                <p class="mb-1 small">
                                                    <i class="fas fa-clock mr-1"></i><?php echo $midterm['duration']; ?> minutes
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-star mr-1"></i><?php echo $midterm['total_marks']; ?> marks
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-question-circle mr-1"></i><?php echo $midterm['question_count']; ?> questions
                                                </p>
                                                <small class="text-muted">
                                                    Available: <?php echo date('M d, Y h:i A', strtotime($midterm['start_time'])); ?> - 
                                                    <?php echo date('M d, Y h:i A', strtotime($midterm['end_time'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php
                                                $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
                                                $start = new DateTime($midterm['start_time'], new DateTimeZone('Asia/Dhaka'));
                                                $end = new DateTime($midterm['end_time'], new DateTimeZone('Asia/Dhaka'));
                                                
                                                if ($midterm['has_submitted']): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($now < $start): ?>
                                                    <span class="badge badge-warning">Upcoming</span>
                                                <?php elseif ($now > $end): ?>
                                                    <span class="badge badge-danger">Closed</span>
                                                <?php else: ?>
                                                    <a href="take_assessment.php?id=<?php echo $midterm['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-pen mr-1"></i>Take Midterm
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Finals -->
                    <?php if (!empty($grouped_assessments['final'])): ?>
                        <div class="assessment-category">
                            <h6 class="text-success mb-3">Final Exams</h6>
                            <div class="list-group">
                                <?php foreach ($grouped_assessments['final'] as $final): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($final['title']); ?></h6>
                                                <p class="mb-1 small">
                                                    <i class="fas fa-clock mr-1"></i><?php echo $final['duration']; ?> minutes
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-star mr-1"></i><?php echo $final['total_marks']; ?> marks
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-question-circle mr-1"></i><?php echo $final['question_count']; ?> questions
                                                </p>
                                                <small class="text-muted">
                                                    Available: <?php echo date('M d, Y h:i A', strtotime($final['start_time'])); ?> - 
                                                    <?php echo date('M d, Y h:i A', strtotime($final['end_time'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php
                                                $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
                                                $start = new DateTime($final['start_time'], new DateTimeZone('Asia/Dhaka'));
                                                $end = new DateTime($final['end_time'], new DateTimeZone('Asia/Dhaka'));
                                                
                                                if ($final['has_submitted']): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($now < $start): ?>
                                                    <span class="badge badge-warning">Upcoming</span>
                                                <?php elseif ($now > $end): ?>
                                                    <span class="badge badge-danger">Closed</span>
                                                <?php else: ?>
                                                    <a href="take_assessment.php?id=<?php echo $final['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-pen mr-1"></i>Take Final
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Materials Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Course Materials</h5>
            </div>
            <div class="card-body">
                <?php if (empty($materials)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">No materials available yet.</p>
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
                                            Type: <?php echo strtoupper($material['file_type']); ?> 
                                            | Size: <?php echo formatFileSize($material['file_size']); ?>
                                            | Uploaded: <?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 