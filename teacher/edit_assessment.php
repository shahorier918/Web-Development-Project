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

// Fetch teacher's courses
$stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

// Fetch existing questions
$stmt = $pdo->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ?");
$stmt->execute([$assessment_id]);
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $course_id = $_POST['course_id'];
    $duration = $_POST['duration'];
    $total_marks = $_POST['total_marks'];
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_date = $_POST['end_date'];
    $end_time = $_POST['end_time'];
    $instructions = trim($_POST['instructions']);
    
    $errors = [];
    
    // Basic validation
    if (empty($title)) $errors[] = "Title is required";
    if (empty($course_id)) $errors[] = "Course is required";
    if ($duration < 1) $errors[] = "Duration must be greater than 0";
    if ($total_marks < 1) $errors[] = "Total marks must be greater than 0";
    
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    
    if (strtotime($end_datetime) <= strtotime($start_datetime)) {
        $errors[] = "End time must be after start time";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update assessment
            $stmt = $pdo->prepare("
                UPDATE assessments 
                SET title = ?, course_id = ?, duration = ?, total_marks = ?,
                    start_time = ?, end_time = ?, instructions = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title, $course_id, $duration, $total_marks,
                $start_datetime, $end_datetime, $instructions,
                $assessment_id
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "Assessment updated successfully!";
            header("Location: assessments.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Format dates and times for form
$start_datetime = new DateTime($assessment['start_time']);
$end_datetime = new DateTime($assessment['end_time']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Assessment | Teacher Dashboard</title>
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
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p>Teacher</p>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="assessments.php" class="nav-link">
                <i class="fas fa-file-alt"></i> Assessments
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-edit mr-2"></i>Edit Assessment
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="form-group">
                                    <label>Course</label>
                                    <select name="course_id" class="form-control" required>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                    <?php echo ($course['id'] == $assessment['course_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?php echo htmlspecialchars($assessment['title']); ?>">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Duration (minutes)</label>
                                        <input type="number" name="duration" class="form-control" required min="1"
                                               value="<?php echo $assessment['duration']; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Total Marks</label>
                                        <input type="number" name="total_marks" class="form-control" required min="1"
                                               value="<?php echo $assessment['total_marks']; ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required
                                               value="<?php echo $start_datetime->format('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Start Time</label>
                                        <input type="time" name="start_time" class="form-control" required
                                               value="<?php echo $start_datetime->format('H:i'); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control" required
                                               value="<?php echo $end_datetime->format('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>End Time</label>
                                        <input type="time" name="end_time" class="form-control" required
                                               value="<?php echo $end_datetime->format('H:i'); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Instructions</label>
                                    <textarea name="instructions" class="form-control" rows="3"><?php echo htmlspecialchars($assessment['instructions']); ?></textarea>
                                </div>

                                <div class="questions-section mt-4">
                                    <h5 class="mb-3">Questions</h5>
                                    <div id="questionsList">
                                        <?php foreach ($questions as $index => $question): ?>
                                            <div class="question-item card mb-3">
                                                <div class="card-body">
                                                    <button type="button" class="close" onclick="removeQuestion(this)">
                                                        <span>&times;</span>
                                                    </button>
                                                    
                                                    <div class="form-group">
                                                        <label>Question Text</label>
                                                        <textarea name="questions[<?php echo $index; ?>][text]" class="form-control" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                                        <input type="hidden" name="questions[<?php echo $index; ?>][id]" value="<?php echo $question['id']; ?>">
                                                    </div>

                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label>Question Type</label>
                                                            <select name="questions[<?php echo $index; ?>][type]" class="form-control" 
                                                                    onchange="toggleOptions(this)" required>
                                                                <option value="multiple_choice" <?php echo $question['question_type'] == 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                                <option value="true_false" <?php echo $question['question_type'] == 'true_false' ? 'selected' : ''; ?>>True/False</option>
                                                                <option value="short_answer" <?php echo $question['question_type'] == 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label>Marks</label>
                                                            <input type="number" name="questions[<?php echo $index; ?>][marks]" 
                                                                   class="form-control" required min="1" value="<?php echo $question['marks']; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="options-container">
                                                        <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                                            <div class="form-group">
                                                                <label>Options (one per line)</label>
                                                                <textarea class="form-control" name="questions[<?php echo $index; ?>][options]" 
                                                                          rows="4" required><?php echo $question['options'] ? implode("\n", json_decode($question['options'], true)) : ''; ?></textarea>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="form-group">
                                                        <label>Correct Answer</label>
                                                        <input type="text" name="questions[<?php echo $index; ?>][correct_answer]" 
                                                               class="form-control" required value="<?php echo htmlspecialchars($question['correct_answer']); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-block" onclick="addQuestion()">
                                        <i class="fas fa-plus mr-2"></i>Add New Question
                                    </button>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                    <a href="assessments.php" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times-circle mr-2"></i>Cancel
                                    </a>
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

    <!-- Add this template for new questions -->
    <template id="questionTemplate">
        <div class="question-item card mb-3">
            <div class="card-body">
                <button type="button" class="close" onclick="removeQuestion(this)">
                    <span>&times;</span>
                </button>
                
                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="questions[{index}][text]" class="form-control" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Question Type</label>
                        <select name="questions[{index}][type]" class="form-control" 
                                onchange="toggleOptions(this)" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Marks</label>
                        <input type="number" name="questions[{index}][marks]" 
                               class="form-control" required min="1">
                    </div>
                </div>

                <div class="options-container">
                    <!-- Options will be dynamically added here -->
                </div>

                <div class="form-group">
                    <label>Correct Answer</label>
                    <input type="text" name="questions[{index}][correct_answer]" 
                           class="form-control" required>
                </div>
            </div>
        </div>
    </template>
</body>
</html> 