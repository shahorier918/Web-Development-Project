<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
requireTeacher();

$course_id = $_GET['course_id'] ?? 0;

// Verify the teacher owns this course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Course not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $errors = [];
    
    // File upload handling
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
        $file_validation = validateFileUpload($_FILES['material_file'], $allowed_types);
        
        if ($file_validation === true) {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/../uploads/materials/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION));
            $file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $file_path)) {
                try {
                    $file_size = filesize($file_path);
                    $stmt = $pdo->prepare("
                        INSERT INTO course_materials (
                            course_id, 
                            title, 
                            description, 
                            file_path, 
                            file_type,
                            file_size,
                            uploaded_at
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    if ($stmt->execute([
                        $course_id, 
                        $title, 
                        $description, 
                        'uploads/materials/' . $file_name,  // Store relative path
                        $file_extension,
                        $file_size
                    ])) {
                        $_SESSION['success'] = "Material uploaded successfully!";
                        header("Location: course_details.php?id=$course_id");
                        exit();
                    }
                } catch (PDOException $e) {
                    $errors[] = "Database error: " . $e->getMessage();
                }
            } else {
                $errors[] = "Failed to upload file";
            }
        } else {
            $errors[] = $file_validation;
        }
    } else {
        $errors[] = "Please select a file to upload";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Course Material | Teacher Dashboard</title>
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
            <a href="course_details.php?id=<?php echo $course_id; ?>" class="nav-link">
                <i class="fas fa-arrow-left"></i> Back to Course
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
                                <i class="fas fa-upload mr-2"></i>Upload Course Material
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

                            <div class="course-info mb-4">
                                <h5>Course: <?php echo htmlspecialchars($course['course_name']); ?></h5>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label><i class="fas fa-heading mr-2"></i>Material Title</label>
                                    <input type="text" name="title" class="form-control" required
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                           placeholder="Enter material title">
                                    <div class="invalid-feedback">Please provide a title.</div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-align-left mr-2"></i>Description</label>
                                    <textarea name="description" class="form-control" rows="4"
                                              placeholder="Enter material description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-file mr-2"></i>Upload File</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="materialFile" name="material_file" required>
                                        <label class="custom-file-label" for="materialFile">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Allowed file types: PDF, DOC, DOCX, PPT, PPTX, TXT (Max size: 5MB)
                                    </small>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload mr-2"></i>Upload Material
                                    </button>
                                    <a href="course_details.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
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
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html> 