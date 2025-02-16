<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$assignment_id = $_GET['id'] ?? 0;

// Fetch assignment and verify ownership
$stmt = $pdo->prepare("
    SELECT a.*, c.course_name, c.teacher_id 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = "Assignment not found or access denied.";
    header("Location: assignments.php");
    exit();
}

// Fetch teacher's courses for dropdown
$stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $course_id = $_POST['course_id'];
    $due_date = $_POST['due_date'];
    $max_points = $_POST['max_points'];
    $allow_attachments = isset($_POST['allow_attachments']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($course_id)) $errors[] = "Course is required";
    if (empty($due_date)) $errors[] = "Due date is required";
    if ($max_points < 1) $errors[] = "Maximum points must be greater than 0";
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE assignments 
                SET title = ?, description = ?, course_id = ?, 
                    due_date = ?, max_points = ?, allow_attachments = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $title, $description, $course_id,
                $due_date, $max_points, $allow_attachments,
                $assignment_id
            ])) {
                $_SESSION['success'] = "Assignment updated successfully!";
                header("Location: assignments.php");
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
    <title>Edit Assignment | Teacher Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher-styles.css">
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
            <a href="assignments.php" class="nav-link">
                <i class="fas fa-tasks"></i> Assignments
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-edit mr-2"></i>Edit Assignment
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
                                                    <?php echo ($course['id'] == $assignment['course_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?php echo htmlspecialchars($assignment['title']); ?>">
                                </div>

                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required
                                               value="<?php echo $assignment['due_date']; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Maximum Points</label>
                                        <input type="number" name="max_points" class="form-control" required
                                               min="1" value="<?php echo $assignment['max_points']; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="allowAttachments" 
                                               name="allow_attachments" <?php echo $assignment['allow_attachments'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="allowAttachments">Allow File Attachments</label>
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                    <a href="assignments.php" class="btn btn-secondary ml-2">
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
</body>
</html> 