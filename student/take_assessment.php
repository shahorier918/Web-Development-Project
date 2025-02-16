<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireStudent();

$assessment_id = $_GET['id'] ?? 0;

// Fetch assessment details and verify student enrollment
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, c.id as course_id,
           (SELECT COUNT(*) FROM assessment_responses WHERE assessment_id = a.id AND student_id = ?) as has_submitted
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.student_id = ?
");
$stmt->execute([$_SESSION['user_id'], $assessment_id, $_SESSION['user_id']]);
$assessment = $stmt->fetch();

if (!$assessment) {
    $_SESSION['error'] = "Assessment not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Check if already submitted
if ($assessment['has_submitted'] > 0) {
    $_SESSION['error'] = "You have already submitted this assessment.";
    header("Location: course_view.php?id=" . $assessment['course_id']);
    exit();
}

// Check time
$now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
$start = new DateTime($assessment['start_time'], new DateTimeZone('Asia/Dhaka'));
$end = new DateTime($assessment['end_time'], new DateTimeZone('Asia/Dhaka'));

if ($now < $start) {
    $_SESSION['error'] = "This assessment is not yet available.";
    header("Location: course_view.php?id=" . $assessment['course_id']);
    exit();
}

if ($now > $end) {
    $_SESSION['error'] = "This assessment has ended.";
    header("Location: course_view.php?id=" . $assessment['course_id']);
    exit();
}

// Fetch questions
$stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['answers'] as $question_id => $answer) {
            // Get question details
            $stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch();
            
            // Calculate marks for automatic grading
            $marks_obtained = 0;
            if ($question['question_type'] !== 'short_answer') {
                $marks_obtained = ($answer === $question['correct_answer']) ? $question['marks'] : 0;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO assessment_responses (
                    assessment_id, student_id, question_id, 
                    response, marks_obtained, submitted_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $assessment_id,
                $_SESSION['user_id'],
                $question_id,
                $answer,
                $marks_obtained
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Assessment submitted successfully!";
        header("Location: course_view.php?id=" . $assessment['course_id']);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Error submitting assessment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Assessment | Student Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="timer-container">
        <div id="timer"></div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="assessment-info mb-4">
                                <p><strong>Course:</strong> <?php echo htmlspecialchars($assessment['course_name']); ?></p>
                                <p><strong>Duration:</strong> <?php echo $assessment['duration']; ?> minutes</p>
                                <p><strong>Total Marks:</strong> <?php echo $assessment['total_marks']; ?></p>
                                <?php if ($assessment['instructions']): ?>
                                    <div class="alert alert-info">
                                        <strong>Instructions:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($assessment['instructions'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" id="assessmentForm">
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="mb-3">Question <?php echo $index + 1; ?></h5>
                                            <p class="mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                            
                                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                <?php $options = json_decode($question['options'], true); ?>
                                                <?php foreach ($options as $option): ?>
                                                    <div class="custom-control custom-radio mb-2">
                                                        <input type="radio" 
                                                               id="q<?php echo $question['id']; ?>_<?php echo md5($option); ?>" 
                                                               name="answers[<?php echo $question['id']; ?>]" 
                                                               value="<?php echo htmlspecialchars($option); ?>" 
                                                               class="custom-control-input" required>
                                                        <label class="custom-control-label" 
                                                               for="q<?php echo $question['id']; ?>_<?php echo md5($option); ?>">
                                                            <?php echo htmlspecialchars($option); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                                <div class="custom-control custom-radio mb-2">
                                                    <input type="radio" 
                                                           id="q<?php echo $question['id']; ?>_true" 
                                                           name="answers[<?php echo $question['id']; ?>]" 
                                                           value="true" 
                                                           class="custom-control-input" required>
                                                    <label class="custom-control-label" 
                                                           for="q<?php echo $question['id']; ?>_true">True</label>
                                                </div>
                                                <div class="custom-control custom-radio mb-2">
                                                    <input type="radio" 
                                                           id="q<?php echo $question['id']; ?>_false" 
                                                           name="answers[<?php echo $question['id']; ?>]" 
                                                           value="false" 
                                                           class="custom-control-input" required>
                                                    <label class="custom-control-label" 
                                                           for="q<?php echo $question['id']; ?>_false">False</label>
                                                </div>
                                            <?php else: ?>
                                                <textarea name="answers[<?php echo $question['id']; ?>]" 
                                                          class="form-control" 
                                                          rows="3" 
                                                          required></textarea>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted mt-2 d-block">
                                                Marks: <?php echo $question['marks']; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" 
                                            onclick="return confirm('Are you sure you want to submit this assessment?')">
                                        <i class="fas fa-paper-plane mr-2"></i>Submit Assessment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    // Timer functionality
    const duration = <?php echo $assessment['duration']; ?> * 60; // Convert to seconds
    let timeLeft = duration;
    
    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById('timer').textContent = 
            `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            document.getElementById('assessmentForm').submit();
        }
        timeLeft--;
    }
    
    setInterval(updateTimer, 1000);
    updateTimer();
    </script>
</body>
</html> 