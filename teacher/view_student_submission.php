<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$assessment_id = $_GET['assessment_id'] ?? 0;
$student_id = $_GET['student_id'] ?? 0;

// Fetch assessment details and verify ownership
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, u.full_name as student_name 
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON u.id = ?
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->execute([$student_id, $assessment_id, $_SESSION['user_id']]);
$assessment = $stmt->fetch();

if (!$assessment) {
    $_SESSION['error'] = "Assessment or student not found.";
    header("Location: assessments.php");
    exit();
}

// Fetch student's responses with questions
$stmt = $pdo->prepare("
    SELECT q.*, r.response, r.submitted_at,
           (CASE WHEN q.correct_answer = r.response THEN q.marks ELSE 0 END) as marks_obtained
    FROM assessment_questions q
    LEFT JOIN assessment_responses r ON q.id = r.question_id 
        AND r.student_id = ? AND r.assessment_id = ?
    WHERE q.assessment_id = ?
    ORDER BY q.id ASC
");
$stmt->execute([$student_id, $assessment_id, $assessment_id]);
$responses = $stmt->fetchAll();

// Calculate total score
$total_score = 0;
foreach ($responses as $response) {
    $total_score += $response['marks_obtained'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Submission | Teacher Dashboard</title>
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Student Submission Details</h4>
                    <a href="view_assessment_submissions.php?id=<?php echo $assessment_id; ?>" 
                       class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Submissions
                    </a>
                </div>
                <div class="card-body">
                    <!-- Assessment and Student Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Assessment Information</h5>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($assessment['title']); ?></p>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($assessment['course_name']); ?></p>
                            <p><strong>Type:</strong> <?php echo ucfirst($assessment['type']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Student Information</h5>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($assessment['student_name']); ?></p>
                            <p><strong>Total Score:</strong> 
                                <span class="badge badge-info">
                                    <?php echo $total_score; ?>/<?php echo $assessment['total_marks']; ?>
                                </span>
                            </p>
                            <p><strong>Percentage:</strong> 
                                <?php 
                                $percentage = ($total_score / $assessment['total_marks']) * 100;
                                $badge_class = $percentage >= 70 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                ?>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo number_format($percentage, 1); ?>%
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Responses -->
                    <h5 class="mb-3">Responses</h5>
                    <?php foreach ($responses as $index => $response): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6>Question <?php echo $index + 1; ?></h6>
                                    <?php if ($response['question_type'] === 'short_answer'): ?>
                                        <form method="POST" action="grade_short_answer.php" class="grade-form">
                                            <input type="hidden" name="response_id" value="<?php echo $response['id']; ?>">
                                            <input type="hidden" name="assessment_id" value="<?php echo $assessment_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                            <div class="input-group input-group-sm" style="width: 200px;">
                                                <input type="number" name="marks" class="form-control" 
                                                       value="<?php echo $response['marks_obtained'] ?? 0; ?>"
                                                       min="0" max="<?php echo $response['marks']; ?>" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">/ <?php echo $response['marks']; ?></span>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-<?php echo $response['marks_obtained'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $response['marks_obtained']; ?>/<?php echo $response['marks']; ?> marks
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="mb-3"><?php echo htmlspecialchars($response['question_text']); ?></p>
                                
                                <?php if ($response['question_type'] === 'multiple_choice'): ?>
                                    <div class="options-list">
                                        <?php 
                                        $options = json_decode($response['options'], true);
                                        foreach ($options as $option):
                                            $is_correct = $option === $response['correct_answer'];
                                            $is_selected = $option === $response['response'];
                                        ?>
                                            <div class="option-item <?php echo $is_selected ? ($is_correct ? 'correct' : 'incorrect') : ''; ?>">
                                                <i class="fas <?php echo $is_selected ? ($is_correct ? 'fa-check text-success' : 'fa-times text-danger') : 'fa-circle'; ?> mr-2"></i>
                                                <?php echo htmlspecialchars($option); ?>
                                                <?php if ($is_correct): ?>
                                                    <span class="badge badge-success ml-2">Correct Answer</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($response['question_type'] === 'true_false'): ?>
                                    <div class="true-false-response">
                                        <div class="option-item <?php echo $response['response'] === $response['correct_answer'] ? 'correct' : 'incorrect'; ?>">
                                            <i class="fas <?php echo $response['response'] === $response['correct_answer'] ? 'fa-check text-success' : 'fa-times text-danger'; ?> mr-2"></i>
                                            Student answered: <?php echo ucfirst($response['response']); ?>
                                        </div>
                                        <div class="correct-answer mt-2">
                                            <span class="badge badge-success">Correct Answer: <?php echo ucfirst($response['correct_answer']); ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="short-answer-response">
                                        <div class="student-response mb-2">
                                            <strong>Student's Answer:</strong>
                                            <div class="p-3 bg-light rounded">
                                                <?php echo nl2br(htmlspecialchars($response['response'] ?? 'No response')); ?>
                                            </div>
                                        </div>
                                        <div class="feedback-section mt-3">
                                            <form method="POST" action="grade_short_answer.php">
                                                <input type="hidden" name="response_id" value="<?php echo $response['id']; ?>">
                                                <input type="hidden" name="assessment_id" value="<?php echo $assessment_id; ?>">
                                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                <div class="form-group">
                                                    <label>Feedback</label>
                                                    <textarea name="feedback" class="form-control" rows="2"><?php echo htmlspecialchars($response['feedback'] ?? ''); ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Save Feedback
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 