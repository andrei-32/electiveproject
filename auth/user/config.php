<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rpsgameb_rps');
define('DB_USER', 'rpsgameb_admin');
define('DB_PASS', 'L~EOkr@Gf.[]');

// Pusher configuration
define('PUSHER_APP_ID', '1995924');
define('PUSHER_KEY', '8b2b38eef8daa1db619b');
define('PUSHER_SECRET', '383281ce90ad9dd9ca40');
define('PUSHER_CLUSTER', 'ap1');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize Pusher
require_once __DIR__ . '/../../vendor/autoload.php';

$pusher = new Pusher\Pusher(
    PUSHER_KEY,
    PUSHER_SECRET,
    PUSHER_APP_ID,
    [
        'cluster' => PUSHER_CLUSTER,
        'useTLS' => true
    ]
);
?> 