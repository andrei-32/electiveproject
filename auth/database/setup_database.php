<?php
require_once '../user/config.php';

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "Database created or already exists.<br>";

    // Select the database
    $pdo->exec("USE " . DB_NAME);

    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    if (empty($sql)) {
        throw new Exception("SQL file is empty");
    }

    // Split SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement separately
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Tables created successfully.<br>";
    echo "Database setup completed successfully!";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?> 