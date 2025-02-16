<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$student_id = $_GET['student_id'] ?? 0;
$course_id = $_GET['course_id'] ?? 0;

// Verify teacher owns this course and student is enrolled
$stmt = $pdo->prepare("
    SELECT u.*, c.course_name, c.teacher_id, e.enrolled_at
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE u.id = ? AND c.id = ? AND c.teacher_id = ?
");
$stmt->execute([$student_id, $course_id, $_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = "Student not found or access denied.";
    header("Location: course_details.php?id=" . $course_id);
    exit();
}

// Fetch assignment submissions and grades
$stmt = $pdo->prepare("
    SELECT a.*, 
           s.submitted_at, 
           s.grade, 
           s.feedback,
           CASE 
               WHEN s.submitted_at IS NOT NULL THEN 'Submitted'
               WHEN NOW() > a.due_date THEN 'Overdue'
               ELSE 'Pending'
           END as status
    FROM assignments a
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
    WHERE a.course_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$student_id, $course_id]);
$assignments = $stmt->fetchAll();

// Fetch assessment results
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as total_questions,
        (
            SELECT COUNT(DISTINCT ar.question_id) 
            FROM assessment_responses ar 
            WHERE ar.assessment_id = a.id AND ar.student_id = ?
        ) as answered_questions,
        (
            SELECT SUM(
                CASE 
                    WHEN aq.question_type = 'multiple_choice' OR aq.question_type = 'true_false' THEN
                        CASE WHEN ar.response = aq.correct_answer THEN aq.marks ELSE 0 END
                    ELSE ar.marks_obtained 
                END
            )
            FROM assessment_responses ar
            JOIN assessment_questions aq ON ar.question_id = aq.id
            WHERE ar.assessment_id = a.id AND ar.student_id = ?
        ) as score,
        (
            SELECT COUNT(*) > 0
            FROM assessment_responses ar
            WHERE ar.assessment_id = a.id AND ar.student_id = ?
        ) as has_attempted,
        CASE 
            WHEN (
                SELECT COUNT(*) > 0
                FROM assessment_responses ar
                WHERE ar.assessment_id = a.id AND ar.student_id = ?
            ) THEN 'Completed'
            WHEN NOW() < a.start_time THEN 'Upcoming'
            WHEN NOW() > a.end_time THEN 'Closed'
            ELSE 'Not Attempted'
        END as status
    FROM assessments a
    WHERE a.course_id = ?
    ORDER BY a.type, a.start_time ASC
");
$stmt->execute([$student_id, $student_id, $student_id, $student_id, $course_id]);
$assessments = $stmt->fetchAll();

// Calculate overall progress more accurately
$total_assignments = count($assignments);
$completed_assignments = 0;
$total_assignment_score = 0;
$max_assignment_score = 0;
$total_assessment_score = 0;
$max_assessment_score = 0;

// Calculate assignment progress
foreach ($assignments as $assignment) {
    if ($assignment['submitted_at']) {
        $completed_assignments++;
        $total_assignment_score += ($assignment['grade'] ?? 0);
    }
    $max_assignment_score += $assignment['max_points'];
}

// Calculate assessment progress
foreach ($assessments as $assessment) {
    if ($assessment['answered_questions'] > 0) {
        $total_assessment_score += ($assessment['score'] ?? 0);
    }
    $max_assessment_score += $assessment['total_marks'];
}

// Calculate overall percentages
$assignment_completion = $total_assignments > 0 ? ($completed_assignments / $total_assignments) * 100 : 0;
$overall_grade = ($max_assignment_score + $max_assessment_score) > 0 ? 
    (($total_assignment_score + $total_assessment_score) / ($max_assignment_score + $max_assessment_score)) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Progress | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Student Progress</h4>
                            <a href="course_details.php?id=<?php echo $course_id; ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i>Back to Course
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Student Info -->
                            <div class="student-info mb-4">
                                <h5><?php echo htmlspecialchars($student['full_name']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($student['email']); ?></p>
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_name']); ?></p>
                                <p><strong>Enrolled Since:</strong> <?php echo date('M d, Y', strtotime($student['enrolled_at'])); ?></p>
                            </div>

                            <!-- Progress Overview -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>Overall Progress</h6>
                                            <div class="progress mb-2">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $assignment_completion; ?>%"
                                                     aria-valuenow="<?php echo $assignment_completion; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo round($assignment_completion); ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $completed_assignments; ?> of <?php echo $total_assignments; ?> assignments completed
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>Overall Grade</h6>
                                            <div class="progress mb-2">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $overall_grade; ?>%"
                                                     aria-valuenow="<?php echo $overall_grade; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo round($overall_grade); ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo round($total_assignment_score + $total_assessment_score); ?> of 
                                                <?php echo $max_assignment_score + $max_assessment_score; ?> total points earned
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignments -->
                            <h5 class="mb-3">Assignments</h5>
                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Assignment</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
                                                <td>
                                                    <?php if ($assignment['submitted_at']): ?>
                                                        <span class="badge badge-success">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($assignment['grade'] !== null): ?>
                                                        <span class="badge badge-info">
                                                            <?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Assessments -->
                            <h5 class="mb-3">Assessments</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Type</th>
                                            <th>Questions</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assessments as $assessment): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($assessment['title']); ?></strong>
                                                        <small class="d-block text-muted">
                                                            <?php echo date('M d, Y h:i A', strtotime($assessment['start_time'])); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?php echo ucfirst($assessment['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($assessment['has_attempted']): ?>
                                                        <span class="text-muted">
                                                            <?php echo $assessment['answered_questions']; ?>/<?php echo $assessment['total_questions']; ?> answered
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <?php echo $assessment['total_questions']; ?> questions
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $now = new DateTime();
                                                    $start = new DateTime($assessment['start_time']);
                                                    $end = new DateTime($assessment['end_time']);
                                                    
                                                    if ($assessment['has_attempted']) {
                                                        echo '<span class="badge badge-success">Completed</span>';
                                                        if ($assessment['answered_questions'] < $assessment['total_questions']) {
                                                            echo '<small class="d-block text-warning">Partially Completed</small>';
                                                        }
                                                    } elseif ($now < $start) {
                                                        echo '<span class="badge badge-warning">Upcoming</span>';
                                                    } elseif ($now > $end) {
                                                        echo '<span class="badge badge-danger">Missed</span>';
                                                    } else {
                                                        echo '<span class="badge badge-info">Available</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($assessment['has_attempted']): ?>
                                                        <div>
                                                            <span class="badge badge-info">
                                                                <?php echo $assessment['score'] ?? 0; ?>/<?php echo $assessment['total_marks']; ?>
                                                            </span>
                                                            <?php 
                                                            $percentage = $assessment['total_marks'] > 0 ? 
                                                                (($assessment['score'] ?? 0) / $assessment['total_marks']) * 100 : 0;
                                                            ?>
                                                            <small class="d-block text-muted">
                                                                <?php echo round($percentage); ?>%
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not attempted</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php if ($assessment['has_attempted']): ?>
                                                <tr class="table-light">
                                                    <td colspan="5">
                                                        <small>
                                                            <strong>Attempt Details:</strong><br>
                                                            <?php if ($assessment['answered_questions'] < $assessment['total_questions']): ?>
                                                                <span class="text-warning">
                                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                    <?php echo $assessment['total_questions'] - $assessment['answered_questions']; ?> 
                                                                    questions not answered
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-success">
                                                                    <i class="fas fa-check-circle mr-1"></i>
                                                                    All questions answered
                                                                </span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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