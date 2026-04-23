<?php
// ============================================================
//  config/db.php  —  Database Connection
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change to your MySQL password
define('DB_NAME', 'cloud_storage_db');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:monospace;color:red;padding:20px;">
                 DB Connection Failed: ' . $conn->connect_error . '</div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
