<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? 0;
    $enrollment_key = trim($_POST['enrollment_key']);
    
    // Verify the enrollment key
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND enrollment_key = ?");
    $stmt->execute([$course_id, $enrollment_key]);
    $course = $stmt->fetch();
    
    if ($course) {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        if ($stmt->fetchColumn() == 0) {
            // Enroll the student
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $course_id])) {
                $_SESSION['success'] = "Successfully enrolled in the course!";
            } else {
                $_SESSION['error'] = "Failed to enroll in the course.";
            }
        } else {
            $_SESSION['error'] = "You are already enrolled in this course.";
        }
    } else {
        $_SESSION['error'] = "Invalid enrollment key.";
    }
}

header("Location: browse_courses.php");
exit();
?> 