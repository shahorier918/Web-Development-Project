<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = $_POST['assignment_id'] ?? 0;
    
    // Verify teacher owns this assignment
    $stmt = $pdo->prepare("
        SELECT a.* FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$assignment_id, $_SESSION['user_id']]);
    $assignment = $stmt->fetch();
    
    if ($assignment) {
        try {
            $pdo->beginTransaction();
            
            // Delete submissions first
            $stmt = $pdo->prepare("DELETE FROM submissions WHERE assignment_id = ?");
            $stmt->execute([$assignment_id]);
            
            // Delete the assignment
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Assignment deleted successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to delete assignment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Assignment not found or access denied.";
    }
}

header("Location: assignments.php");
exit();
?> 