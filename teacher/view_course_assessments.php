<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$course_id = $_GET['course_id'] ?? 0;

// Verify teacher owns this course and get course details
$stmt = $pdo->prepare("
    SELECT * FROM courses WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Fetch all assessments for this course
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(DISTINCT student_id) FROM assessment_responses WHERE assessment_id = a.id) as submission_count,
           (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count
    FROM assessments a
    WHERE a.course_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$course_id]);
$all_assessments = $stmt->fetchAll();

// Group assessments by type
$assessments = [
    'quiz' => [],
    'midterm' => [],
    'final' => []
];

foreach ($all_assessments as $assessment) {
    $assessments[$assessment['type']][] = $assessment;
}

// Add debugging to check what's being fetched
echo "<!-- Debug: " . count($all_assessments) . " assessments found -->";
foreach ($all_assessments as $assessment) {
    echo "<!-- Assessment: " . $assessment['title'] . " (Type: " . $assessment['type'] . ") -->";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course Assessments | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Include Sidebar -->
    <div class="sidebar">
        <!-- Similar sidebar as other pages -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Course Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-code mr-2"></i><?php echo htmlspecialchars($course['course_code']); ?>
                            </p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="addAssessmentBtn" 
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-plus mr-2"></i>Add Assessment
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="create_assessment.php?type=quiz&course_id=<?php echo $course_id; ?>">
                                    <i class="fas fa-question-circle mr-2"></i>Quiz
                                </a>
                                <a class="dropdown-item" href="create_assessment.php?type=midterm&course_id=<?php echo $course_id; ?>">
                                    <i class="fas fa-file-alt mr-2"></i>Midterm
                                </a>
                                <a class="dropdown-item" href="create_assessment.php?type=final&course_id=<?php echo $course_id; ?>">
                                    <i class="fas fa-graduation-cap mr-2"></i>Final
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assessment Types -->
            <div class="row">
                <!-- Quizzes -->
                <div class="col-md-4">
                    <div class="assessment-type-card card">
                        <div class="card-header quiz-header">
                            <h5 class="mb-0 d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-question-circle mr-2"></i>Quizzes</span>
                                <span class="badge badge-light"><?php echo count($assessments['quiz']); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assessments['quiz'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard"></i>
                                    <p class="mb-0">No quizzes created yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assessments['quiz'] as $quiz): ?>
                                    <div class="assessment-item">
                                        <h6><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo $quiz['duration']; ?> mins</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo $quiz['total_marks']; ?> marks</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $quiz['submission_count']; ?> submissions</span>
                                            </div>
                                        </div>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span><?php echo date('M d, Y h:i A', strtotime($quiz['start_time'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="assessment-status">
                                            <?php
                                            $now = new DateTime();
                                            $start = new DateTime($quiz['start_time']);
                                            $end = new DateTime($quiz['end_time']);
                                            
                                            if ($now < $start):
                                                echo '<span class="badge badge-warning">Upcoming</span>';
                                            elseif ($now > $end):
                                                echo '<span class="badge badge-secondary">Closed</span>';
                                            else:
                                                echo '<span class="badge badge-success">Active</span>';
                                            endif;
                                            ?>
                                            <div class="assessment-actions">
                                                <a href="view_assessment.php?id=<?php echo $quiz['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                                <a href="edit_assessment.php?id=<?php echo $quiz['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                    <span>Edit</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Midterms -->
                <div class="col-md-4">
                    <div class="assessment-type-card card">
                        <div class="card-header midterm-header">
                            <h5 class="mb-0 d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-file-alt mr-2"></i>Midterms</span>
                                <span class="badge badge-light"><?php echo count($assessments['midterm']); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assessments['midterm'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard"></i>
                                    <p class="mb-0">No midterms created yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assessments['midterm'] as $midterm): ?>
                                    <div class="assessment-item">
                                        <h6><?php echo htmlspecialchars($midterm['title']); ?></h6>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo $midterm['duration']; ?> mins</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo $midterm['total_marks']; ?> marks</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $midterm['submission_count']; ?> submissions</span>
                                            </div>
                                        </div>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span><?php echo date('M d, Y h:i A', strtotime($midterm['start_time'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="assessment-status">
                                            <?php
                                            $now = new DateTime();
                                            $start = new DateTime($midterm['start_time']);
                                            $end = new DateTime($midterm['end_time']);
                                            
                                            if ($now < $start):
                                                echo '<span class="badge badge-warning">Upcoming</span>';
                                            elseif ($now > $end):
                                                echo '<span class="badge badge-secondary">Closed</span>';
                                            else:
                                                echo '<span class="badge badge-success">Active</span>';
                                            endif;
                                            ?>
                                            <div class="assessment-actions">
                                                <a href="view_assessment.php?id=<?php echo $midterm['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                                <a href="edit_assessment.php?id=<?php echo $midterm['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                    <span>Edit</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Finals -->
                <div class="col-md-4">
                    <div class="assessment-type-card card">
                        <div class="card-header final-header">
                            <h5 class="mb-0 d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-graduation-cap mr-2"></i>Finals</span>
                                <span class="badge badge-light"><?php echo count($assessments['final']); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assessments['final'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard"></i>
                                    <p class="mb-0">No finals created yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($assessments['final'] as $final): ?>
                                    <div class="assessment-item">
                                        <h6><?php echo htmlspecialchars($final['title']); ?></h6>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo $final['duration']; ?> mins</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo $final['total_marks']; ?> marks</span>
                                            </div>
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $final['submission_count']; ?> submissions</span>
                                            </div>
                                        </div>
                                        <div class="assessment-meta">
                                            <div class="assessment-meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span><?php echo date('M d, Y h:i A', strtotime($final['start_time'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="assessment-status">
                                            <?php
                                            $now = new DateTime();
                                            $start = new DateTime($final['start_time']);
                                            $end = new DateTime($final['end_time']);
                                            
                                            if ($now < $start):
                                                echo '<span class="badge badge-warning">Upcoming</span>';
                                            elseif ($now > $end):
                                                echo '<span class="badge badge-secondary">Closed</span>';
                                            else:
                                                echo '<span class="badge badge-success">Active</span>';
                                            endif;
                                            ?>
                                            <div class="assessment-actions">
                                                <a href="view_assessment.php?id=<?php echo $final['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                                <a href="edit_assessment.php?id=<?php echo $final['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                    <span>Edit</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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