<?php
require_once 'config/database.php';

try {
    // Test the connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection successful!<br>";
    
    // Get database info
    echo "Connected to: " . DB_NAME . "<br>";
    echo "MySQL version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
    
    // Test if tables exist
    $tables = ['users', 'courses', 'enrollments', 'assignments', 'submissions', 'course_materials'];
    
    echo "<br>Checking tables:<br>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists<br>";
        } else {
            echo "✗ Table '$table' does not exist<br>";
        }
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> 