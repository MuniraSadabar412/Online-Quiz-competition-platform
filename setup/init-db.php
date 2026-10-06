<?php
/**
 * Database Initialization Script
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/../config/database.php';

echo "=== INITIALIZING DATABASE ===\n";

try {
    $pdo = get_db_connection();
    echo "[✓] Connected to MySQL successfully.\n";

    // Read SQL file
    $sqlPath = __DIR__ . '/../database/online_quiz_platform.sql';
    if (!file_exists($sqlPath)) {
        die("[✗] Error: SQL file not found at: $sqlPath\n");
    }

    $sql = file_get_contents($sqlPath);

    // Split and execute statements
    $pdo->exec($sql);
    echo "[✓] Database schema imported successfully into 'online_quiz_platform'.\n";

    // Re-verify connection to the created database
    $stmt = $pdo->query("SHOW TABLES FROM `online_quiz_platform`");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "[✓] Tables created: " . implode(', ', $tables) . "\n";

} catch (Exception $e) {
    echo "[✗] Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
