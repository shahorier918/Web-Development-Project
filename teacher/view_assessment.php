<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$assessment_id = $_GET['id'] ?? 0;

// Fetch assessment details with course info
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, 
           (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
           (SELECT COUNT(DISTINCT student_id) FROM assessment_responses WHERE assessment_id = a.id) as submission_count
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

// Fetch questions
$stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Assessment | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Include your sidebar here -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Assessment Details</h5>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($assessment['course_name']); ?></p>
                            <p><strong>Type:</strong> <?php echo ucfirst($assessment['type']); ?></p>
                            <p><strong>Duration:</strong> <?php echo $assessment['duration']; ?> minutes</p>
                            <p><strong>Total Marks:</strong> <?php echo $assessment['total_marks']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Timing</h5>
                            <p><strong>Start:</strong> <?php echo date('M d, Y h:i A', strtotime($assessment['start_time'])); ?></p>
                            <p><strong>End:</strong> <?php echo date('M d, Y h:i A', strtotime($assessment['end_time'])); ?></p>
                            <p><strong>Status:</strong> 
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
                            </p>
                        </div>
                    </div>

                    <div class="questions-section mt-4">
                        <h5>Questions</h5>
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6>Question <?php echo $index + 1; ?></h6>
                                    <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted">
                                                Type: <?php echo ucwords(str_replace('_', ' ', $question['question_type'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">
                                                Marks: <?php echo $question['marks']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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