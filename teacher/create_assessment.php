<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$type = $_GET['type'] ?? '';
if (!in_array($type, ['quiz', 'midterm', 'final'])) {
    header("Location: assessments.php");
    exit();
}

// Fetch teacher's courses
$stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

$selected_course_id = $_GET['course_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);
    $instructions = trim($_POST['instructions']);
    $duration = $_POST['duration'];
    $total_marks = $_POST['total_marks'];
    $start_time = $_POST['start_date'] . ' ' . $_POST['start_time'];
    $end_time = $_POST['end_date'] . ' ' . $_POST['end_time'];
    $questions = $_POST['questions'] ?? [];
    
    $errors = [];
    
    // Validate input
    if (empty($title)) $errors[] = "Title is required";
    if (empty($course_id)) $errors[] = "Please select a course";
    if (empty($duration)) $errors[] = "Duration is required";
    if (empty($total_marks)) $errors[] = "Total marks is required";
    if (empty($questions)) $errors[] = "At least one question is required";
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert assessment
            $stmt = $pdo->prepare("
                INSERT INTO assessments (course_id, title, type, duration, total_marks, 
                                       start_time, end_time, instructions, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $course_id, $title, $type, $duration, $total_marks,
                $start_time, $end_time, $instructions
            ]);
            
            $assessment_id = $pdo->lastInsertId();
            
            // Insert questions
            foreach ($questions as $q) {
                $stmt = $pdo->prepare("
                    INSERT INTO assessment_questions (assessment_id, question_text, 
                                                    question_type, marks, options, correct_answer)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $assessment_id,
                    $q['text'],
                    $q['type'],
                    $q['marks'],
                    $q['type'] === 'multiple_choice' ? json_encode($q['options']) : null,
                    $q['correct_answer']
                ]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = ucfirst($type) . " created successfully!";
            header("Location: assessments.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating assessment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create <?php echo ucfirst($type); ?> | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
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
            <a href="my_courses.php" class="nav-link">
                <i class="fas fa-book"></i> My Courses
            </a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle active" href="#" id="assessmentDropdown" role="button" 
                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-alt"></i> Assessments
                </a>
                <div class="dropdown-menu" aria-labelledby="assessmentDropdown">
                    <a class="dropdown-item" href="create_assessment.php?type=quiz">
                        <i class="fas fa-question-circle mr-2"></i>Quiz
                    </a>
                    <a class="dropdown-item" href="create_assessment.php?type=midterm">
                        <i class="fas fa-file-alt mr-2"></i>Midterm
                    </a>
                    <a class="dropdown-item" href="create_assessment.php?type=final">
                        <i class="fas fa-graduation-cap mr-2"></i>Final
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="assessments.php">
                        <i class="fas fa-list mr-2"></i>View All
                    </a>
                </div>
            </div>
            <a href="assignments.php" class="nav-link">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="grade_submissions.php" class="nav-link">
                <i class="fas fa-check-circle"></i> Grade Submissions
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card assessment-form-container">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-edit mr-2"></i>
                                Create <?php echo ucfirst($type); ?>
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

                            <form method="POST" id="assessmentForm">
                                <!-- Basic Information Section -->
                                <div class="section mb-4">
                                    <h5 class="text-muted mb-3">Basic Information</h5>
                                    
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-book mr-2"></i>Select Course
                                        </label>
                                        <select name="course_id" class="form-control" required>
                                            <option value="">Select a course</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo $course['id']; ?>" 
                                                        <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-heading mr-2"></i>Title
                                        </label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-info-circle mr-2"></i>Instructions
                                        </label>
                                        <textarea name="instructions" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>

                                <!-- Timing Section -->
                                <div class="section mb-4">
                                    <h5 class="text-muted mb-3">Timing and Marks</h5>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-clock mr-2"></i>Duration (minutes)
                                            </label>
                                            <input type="number" name="duration" class="form-control" required min="1">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-star mr-2"></i>Total Marks
                                            </label>
                                            <input type="number" name="total_marks" class="form-control" required min="1">
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-calendar mr-2"></i>Start Date
                                            </label>
                                            <input type="date" name="start_date" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-clock mr-2"></i>Start Time
                                            </label>
                                            <input type="time" name="start_time" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-calendar mr-2"></i>End Date
                                            </label>
                                            <input type="date" name="end_date" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>
                                                <i class="fas fa-clock mr-2"></i>End Time
                                            </label>
                                            <input type="time" name="end_time" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions Section -->
                                <div class="questions-container">
                                    <h5>
                                        <i class="fas fa-question-circle mr-2"></i>Questions
                                    </h5>
                                    <div id="questionsList"></div>
                                    <button type="button" class="btn btn-add-question" onclick="addQuestion()">
                                        <i class="fas fa-plus mr-2"></i>Add New Question
                                    </button>
                                </div>

                                <div class="form-group mt-4 text-right">
                                    <a href="assessments.php" class="btn btn-secondary mr-2">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Create <?php echo ucfirst($type); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Template -->
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        let questionCount = 0;

        function addQuestion() {
            const template = document.getElementById('questionTemplate').content.cloneNode(true);
            const container = document.getElementById('questionsList');
            
            // Replace placeholder index
            template.querySelectorAll('[name*="{index}"]').forEach(el => {
                el.name = el.name.replace('{index}', questionCount);
            });
            
            container.appendChild(template);
            questionCount++;
        }

        function removeQuestion(button) {
            button.closest('.question-item').remove();
        }

        function toggleOptions(select) {
            const optionsContainer = select.closest('.question-item').querySelector('.options-container');
            const type = select.value;
            
            if (type === 'multiple_choice') {
                optionsContainer.innerHTML = `
                    <div class="form-group">
                        <label>Options (one per line)</label>
                        <textarea class="form-control" name="questions[${questionCount-1}][options]" 
                                  rows="4" required></textarea>
                    </div>
                `;
            } else {
                optionsContainer.innerHTML = '';
            }
        }

        // Add first question by default
        document.addEventListener('DOMContentLoaded', function() {
            addQuestion();
        });
    </script>
</body>
</html> 