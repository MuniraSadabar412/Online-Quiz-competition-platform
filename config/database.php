<?php
/**
 * Database Connection Configuration
 * Online Quiz Competition Platform
 * 
 * Uses PHP PDO for secure, prepared SQL execution.
 * Supports auto-fallback between standard port 3306 and port 3307 (common when MySQL 8 and XAMPP co-exist).
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'online_quiz_platform');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get or initialize PDO database connection singleton
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $ports = [3307, 3306]; // 3307 first if running XAMPP alongside MySQL80, with automatic fallback
    $lastException = null;

    foreach ($ports as $port) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, $port, DB_NAME);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            // If the database doesn't exist yet, try connecting without dbname so setup scripts work
            if ($e->getCode() == 1049) {
                try {
                    $dsnWithoutDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, $port);
                    $pdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    return $pdo;
                } catch (PDOException $inner) {
                    $lastException = $inner;
                }
            }
        }
    }

    // If both ports failed
    error_log("Database connection failed: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    throw new Exception("Unable to connect to MySQL database. Please ensure Apache and MySQL are running in XAMPP.");
}
