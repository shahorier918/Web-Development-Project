<?php
// Define the upload directories
$directories = [
    'uploads',
    'uploads/materials',
    'uploads/assignments',
    'uploads/submissions'
];

// Create directories and set permissions
foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0777, true)) {
            echo "Created directory: $dir<br>";
            // Ensure proper permissions
            chmod($fullPath, 0777);
            echo "Set permissions for: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    } else {
        echo "Directory already exists: $dir<br>";
        // Update permissions if needed
        chmod($fullPath, 0777);
        echo "Updated permissions for: $dir<br>";
    }
}

// Create .htaccess file to protect against direct file access
$htaccess_content = "Options -Indexes\nDeny from all";
foreach ($directories as $dir) {
    $htaccess_file = __DIR__ . '/' . $dir . '/.htaccess';
    if (file_put_contents($htaccess_file, $htaccess_content)) {
        echo "Created .htaccess in: $dir<br>";
    } else {
        echo "Failed to create .htaccess in: $dir<br>";
    }
}

// Create index.php files to prevent directory listing
$index_content = "<?php header('Location: ../index.php'); ?>";
foreach ($directories as $dir) {
    $index_file = __DIR__ . '/' . $dir . '/index.php';
    if (file_put_contents($index_file, $index_content)) {
        echo "Created index.php in: $dir<br>";
    } else {
        echo "Failed to create index.php in: $dir<br>";
    }
} 