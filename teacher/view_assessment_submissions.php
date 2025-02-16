<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$assessment_id = $_GET['id'] ?? 0;

// Fetch assessment details and verify ownership
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name 
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->execute([$assessment_id, $_SESSION['user_id']]);
$assessment = $stmt->fetch();

if (!$assessment) {
    $_SESSION['error'] = "Assessment not found or access denied.";
    header("Location: assessments.php");
    exit();
}

// Fetch submissions with student details
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id as student_id,
        u.full_name,
        u.email,
        MIN(r.submitted_at) as submission_time,
        (
            SELECT SUM(CASE WHEN aq.correct_answer = r2.response THEN aq.marks ELSE 0 END)
            FROM assessment_responses r2
            JOIN assessment_questions aq ON r2.question_id = aq.id
            WHERE r2.student_id = u.id AND r2.assessment_id = ?
        ) as total_score
    FROM users u
    JOIN assessment_responses r ON u.id = r.student_id
    WHERE r.assessment_id = ?
    GROUP BY u.id
    ORDER BY submission_time DESC
");
$stmt->execute([$assessment_id, $assessment_id]);
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assessment Submissions | Teacher Dashboard</title>
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
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <?php echo htmlspecialchars($assessment['title']); ?> - Submissions
                    </h4>
                </div>
                <div class="card-body">
                    <div class="assessment-info mb-4">
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($assessment['course_name']); ?></p>
                        <p><strong>Type:</strong> <?php echo ucfirst($assessment['type']); ?></p>
                        <p><strong>Total Marks:</strong> <?php echo $assessment['total_marks']; ?></p>
                    </div>

                    <?php if (empty($submissions)): ?>
                        <div class="alert alert-info">
                            No submissions yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Submission Time</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($submission['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($submission['email']); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($submission['submission_time'])); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo $submission['total_score']; ?>/<?php echo $assessment['total_marks']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_student_submission.php?assessment_id=<?php echo $assessment_id; ?>&student_id=<?php echo $submission['student_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye mr-1"></i>View Details
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
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 