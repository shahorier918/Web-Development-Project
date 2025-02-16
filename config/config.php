<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'class_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Class Management System');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Directory Constants
define('MATERIALS_DIR', UPLOAD_DIR . '/materials');
define('ASSIGNMENTS_DIR', UPLOAD_DIR . '/assignments');
define('SUBMISSIONS_DIR', UPLOAD_DIR . '/submissions');
?> 