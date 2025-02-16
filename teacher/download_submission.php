<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
requireTeacher();

$submission_id = $_GET['id'] ?? 0;

// Verify teacher's access to this submission
$stmt = $pdo->prepare("
    SELECT s.*, a.title as assignment_title, c.teacher_id, s.file_path
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND c.teacher_id = ?
");
$stmt->execute([$submission_id, $_SESSION['user_id']]);
$submission = $stmt->fetch();

if (!$submission) {
    $_SESSION['error'] = "Submission not found or access denied.";
    header("Location: assignments.php");
    exit();
}

// Update the file path handling
$file_path = "../" . $submission['file_path'];
if (!file_exists($file_path)) {
    $_SESSION['error'] = "File not found.";
    header("Location: view_submissions.php?id=" . $submission['assignment_id']);
    exit();
}

// Get file information
$file_name = basename($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set content type based on file extension
$content_types = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt'  => 'text/plain',
    // Add more mime types as needed
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set headers for download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

// Clear output buffer
ob_clean();
flush();

// Output file
readfile($file_path);
exit();
?> 