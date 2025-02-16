<?php
require_once 'config/database.php';

try {
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // Create tables
    $sql = file_get_contents('database.sql');
    $pdo->exec($sql);
    
    echo "Database and tables created successfully!<br>";
    
    // Create upload directories
    $directories = [
        'uploads',
        'uploads/materials',
        'uploads/assignments',
        'uploads/submissions'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: $dir<br>";
        }
    }
    
    echo "Setup completed successfully!";
    
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 