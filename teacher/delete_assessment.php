<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_id = $_POST['assessment_id'] ?? 0;
    
    // Verify teacher owns this assessment
    $stmt = $pdo->prepare("
        SELECT a.* FROM assessments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$assessment_id, $_SESSION['user_id']]);
    $assessment = $stmt->fetch();
    
    if ($assessment) {
        try {
            $pdo->beginTransaction();
            
            // Delete responses first
            $stmt = $pdo->prepare("DELETE FROM assessment_responses WHERE assessment_id = ?");
            $stmt->execute([$assessment_id]);
            
            // Delete questions
            $stmt = $pdo->prepare("DELETE FROM assessment_questions WHERE assessment_id = ?");
            $stmt->execute([$assessment_id]);
            
            // Delete the assessment
            $stmt = $pdo->prepare("DELETE FROM assessments WHERE id = ?");
            $stmt->execute([$assessment_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Assessment deleted successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to delete assessment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Assessment not found or access denied.";
    }
}

header("Location: assessments.php");
exit();
?> 