<?php
require_once __DIR__ . "/../config/DBConn.php";

// Drop table if exists
mysqli_query($conn, "DROP TABLE IF EXISTS tblUser");

// Create table
$sql = "CREATE TABLE tblUser (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    username VARCHAR(50),
    password VARCHAR(255),
    status VARCHAR(20),
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sql);

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO tblUser (name, email, username, password, status, role) VALUES (?, ?, ?, ?, 'active', 'user')"
);

if (!$insertStmt) {
    die("Error: Unable to prepare user import.");
}

// Load data from file
$file = fopen(__DIR__ . "/../data/userData.txt", "r");
if (!$file) {
    die("Error: Unable to open userData.txt");
}

while (($line = fgets($file)) !== false) {
    $data = explode(",", trim($line));

    // Skip malformed/empty rows in the data file
    if (count($data) < 4) {
        continue;
    }

    $name = trim($data[0]);
    $email = trim($data[1]);
    $username = trim($data[2]);
    $password = password_hash(trim($data[3]), PASSWORD_DEFAULT);

    mysqli_stmt_bind_param($insertStmt, "ssss", $name, $email, $username, $password);
    mysqli_stmt_execute($insertStmt);
}

fclose($file);
mysqli_stmt_close($insertStmt);

echo "Table created and data inserted!";
?>
