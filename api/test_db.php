<?php
/**
 * Database Diagnostic Tool
 * Open this in your browser: domain.com/api/test_db.php
 */
require_once 'connect.php';

echo "<h1>Database Connection Diagnostic</h1>";

try {
    if (isset($pdo)) {
        echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS: Connected to the database!</p>";
        
        // Check if tables exist
        $query = $pdo->query("SHOW TABLES");
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>Found " . count($tables) . " tables: " . implode(", ", $tables) . "</p>";
            
            if (in_array('site_settings', $tables)) {
                echo "<p style='color:green;'>✅ 'site_settings' table found.</p>";
            } else {
                echo "<p style='color:red;'>❌ 'site_settings' table MISSING. Did you import database.sql?</p>";
            }
        } else {
            echo "<p style='color:orange;'>⚠️ Connection works, but NO TABLES FOUND. Please import database.sql via phpMyAdmin.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ CONNECTION FAILED</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
