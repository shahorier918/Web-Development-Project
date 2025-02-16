<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
requireStudent();

$assignment_id = $_GET['id'] ?? 0;

// Verify student is enrolled and assignment exists
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, c.id as course_id
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.student_id = ?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = "Assignment not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Check if already submitted
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "You have already submitted this assignment.";
    header("Location: course_view.php?id=" . $assignment['course_id']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_text = trim($_POST['submission_text']);
    $file_path = null;
    $errors = [];
    
    // Handle file upload if allowed
    if ($assignment['allow_attachments'] && isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['pdf', 'doc', 'docx', 'txt'];
        $file_validation = validateFileUpload($_FILES['submission_file'], $allowed_types);
        
        if ($file_validation === true) {
            $upload_dir = '../uploads/submissions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . uniqid() . '_' . $_FILES['submission_file']['name'];
            $file_path = 'uploads/submissions/' . $file_name;
            $full_path = '../' . $file_path;
            
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $full_path)) {
                // File upload successful
            } else {
                $errors[] = "Failed to upload file";
            }
        } else {
            $errors[] = $file_validation;
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO submissions (assignment_id, student_id, submission_text, file_path) 
                VALUES (?, ?, ?, ?)
            ");
            if ($stmt->execute([$assignment_id, $_SESSION['user_id'], $submission_text, $file_path])) {
                $_SESSION['success'] = "Assignment submitted successfully!";
                header("Location: course_view.php?id=" . $assignment['course_id']);
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Assignment | Student Dashboard</title>
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
        <!-- Similar to course_view.php -->
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Submit Assignment</h4>
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

                            <div class="assignment-details mb-4">
                                <h5><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                <div class="text-muted">
                                    <small>
                                        Course: <?php echo htmlspecialchars($assignment['course_name']); ?><br>
                                        Due Date: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?><br>
                                        Points: <?php echo $assignment['max_points']; ?>
                                    </small>
                                </div>
                            </div>

                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Your Answer</label>
                                    <textarea name="submission_text" class="form-control" rows="6" required><?php echo isset($_POST['submission_text']) ? htmlspecialchars($_POST['submission_text']) : ''; ?></textarea>
                                </div>

                                <?php if ($assignment['allow_attachments']): ?>
                                    <div class="form-group">
                                        <label>Attachment (Optional)</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="submissionFile" name="submission_file">
                                            <label class="custom-file-label" for="submissionFile">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">
                                            Allowed file types: PDF, DOC, DOCX, TXT (Max size: 5MB)
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane mr-2"></i>Submit Assignment
                                    </button>
                                    <a href="course_view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Custom file input label
        document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>
</body>
</html> 