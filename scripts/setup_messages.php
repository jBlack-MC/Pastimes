<?php
/* Creates the tblmessage table for the messaging system (Phase 11). Run once via browser or CLI. */
require_once __DIR__ . "/../config/DBConn.php";

$sql = "CREATE TABLE IF NOT EXISTS tblmessage (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id  INT NOT NULL,
    sender_name VARCHAR(100),
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "tblmessage created (or already exists).";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
