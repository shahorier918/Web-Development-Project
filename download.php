<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/security.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$file_id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

// Verify access rights and get file path
switch ($type) {
    case 'material':
        $stmt = $pdo->prepare("
            SELECT m.*, c.teacher_id 
            FROM course_materials m
            JOIN courses c ON m.course_id = c.id
            LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
            WHERE m.id = ? AND (c.teacher_id = ? OR e.student_id IS NOT NULL)
        ");
        $stmt->execute([$_SESSION['user_id'], $file_id, $_SESSION['user_id']]);
        $file = $stmt->fetch();
        
        if (!$file) {
            die("Access denied or file not found.");
        }

        // Get file information
        $filepath = $file['file_path'];
        $filename = basename($filepath);
        $filetype = $file['file_type'];
        
        // Check if file exists
        if (!file_exists($filepath)) {
            die("File not found.");
        }

        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Output file
        readfile($filepath);
        exit();
        break;
        
    default:
        die("Invalid request type.");
} 