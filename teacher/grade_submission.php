<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'] ?? 0;
    $grade = $_POST['grade'] ?? null;
    $feedback = trim($_POST['feedback'] ?? '');

    // Verify teacher owns this submission
    $stmt = $pdo->prepare("
        SELECT s.*, c.teacher_id, a.max_points 
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        JOIN courses c ON a.course_id = c.id
        WHERE s.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
    $submission = $stmt->fetch();

    if ($submission) {
        // Validate grade
        if ($grade < 0 || $grade > $submission['max_points']) {
            $_SESSION['error'] = "Invalid grade value";
        } else {
            // Update submission with grade and feedback
            $stmt = $pdo->prepare("
                UPDATE submissions 
                SET grade = ?, feedback = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$grade, $feedback, $submission_id])) {
                $_SESSION['success'] = "Submission graded successfully";
            } else {
                $_SESSION['error'] = "Failed to grade submission";
            }
        }
    } else {
        $_SESSION['error'] = "Submission not found or access denied";
    }

    // Redirect back to view submissions
    header("Location: view_submissions.php?id=" . $submission['assignment_id']);
    exit();
}

// If not POST request, redirect to dashboard
header("Location: dashboard.php");
exit();
?> 