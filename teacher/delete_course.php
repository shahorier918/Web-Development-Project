<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? 0;
    
    // Verify teacher owns this course
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch();
    
    if ($course) {
        try {
            $pdo->beginTransaction();
            
            // Delete related records first
            $stmt = $pdo->prepare("DELETE FROM enrollments WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            $stmt = $pdo->prepare("DELETE FROM course_materials WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            // Finally delete the course
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Course deleted successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to delete course: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Course not found or access denied.";
    }
}

header("Location: dashboard.php");
exit();
?> 