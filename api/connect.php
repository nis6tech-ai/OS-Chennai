<?php
/**
 * Nutpa CMS - Database Connection
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database credentials
$db_host = 'localhost';
$db_name = 'nutpa_db';
$db_user = 'nutpa_user';
$db_pass = 'nutpaPassw0rd123!';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Utility to save site data to JSON (Optional - for high speed fetching)
 */
function updateStaticCache($pdo) {
    // We can generate a products.json for the frontend to load super fast
}
?>
