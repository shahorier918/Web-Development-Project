<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

$course_id = $_GET['id'] ?? 0;

// Verify teacher owns this course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $enrollment_key = trim($_POST['enrollment_key']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $errors = [];
    
    // Validate dates
    if (strtotime($end_date) < strtotime($start_date)) {
        $errors[] = "End date cannot be before start date";
    }
    
    // Validate course code uniqueness (excluding current course)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ? AND id != ?");
    $stmt->execute([$course_code, $course_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Course code already exists. Please choose a different one.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE courses 
                SET course_name = ?, course_code = ?, enrollment_key = ?, 
                    description = ?, start_date = ?, end_date = ?
                WHERE id = ? AND teacher_id = ?
            ");
            
            if ($stmt->execute([
                $course_name, $course_code, $enrollment_key,
                $description, $start_date, $end_date,
                $course_id, $_SESSION['user_id']
            ])) {
                $_SESSION['success'] = "Course updated successfully!";
                header("Location: dashboard.php");
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
    <title>Edit Course | Teacher Dashboard</title>
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
                                <i class="fas fa-edit mr-2"></i>Edit Course
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
                                    <label><i class="fas fa-book mr-2"></i>Course Name</label>
                                    <input type="text" name="course_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($course['course_name']); ?>">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-hashtag mr-2"></i>Course Code</label>
                                        <input type="text" name="course_code" class="form-control" required
                                               pattern="[A-Za-z0-9-]+" 
                                               value="<?php echo htmlspecialchars($course['course_code']); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-key mr-2"></i>Enrollment Key</label>
                                        <input type="text" name="enrollment_key" class="form-control" required
                                               value="<?php echo htmlspecialchars($course['enrollment_key']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-align-left mr-2"></i>Description</label>
                                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-calendar-alt mr-2"></i>Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required
                                               value="<?php echo $course['start_date']; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label><i class="fas fa-calendar-alt mr-2"></i>End Date</label>
                                        <input type="date" name="end_date" class="form-control" required
                                               value="<?php echo $course['end_date']; ?>">
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary ml-2">
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