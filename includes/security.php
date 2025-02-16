<?php
// Add this new file for security functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateFileUpload($file, $allowed_types = ['pdf', 'doc', 'docx', 'txt']) {
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($file_ext, $allowed_types)) {
        return "Invalid file type. Allowed types: " . implode(', ', $allowed_types);
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return "File size too large. Maximum size: 5MB";
    }
    
    return true;
}

// Add CSRF protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Add this function to your existing security.php file
function serveFile($filepath) {
    if (!file_exists($filepath)) {
        header("HTTP/1.0 404 Not Found");
        exit("File not found.");
    }

    // Get file extension
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    // Set content type based on file extension
    $content_types = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain'
    ];
    
    $content_type = $content_types[$extension] ?? 'application/octet-stream';
    
    // Set headers
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read file and output
    readfile($filepath);
    exit;
}
?> 