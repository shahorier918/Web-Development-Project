<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = $_POST['material_id'] ?? 0;
    $course_id = $_POST['course_id'] ?? 0;
    
    // Verify ownership and get file path
    $stmt = $pdo->prepare("
        SELECT m.* FROM course_materials m
        JOIN courses c ON m.course_id = c.id
        WHERE m.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$material_id, $_SESSION['user_id']]);
    $material = $stmt->fetch();
    
    if ($material) {
        try {
            // Delete file
            if (file_exists($material['file_path'])) {
                unlink($material['file_path']);
            }
            
            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = ?");
            if ($stmt->execute([$material_id])) {
                $_SESSION['success'] = "Material deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete material.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Material not found or access denied.";
    }
    
    header("Location: course_details.php?id=" . $course_id);
    exit();
}

header("Location: dashboard.php");
exit();
?> 