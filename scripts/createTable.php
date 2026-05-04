<?php
include("../config/DBConn.php");

// Drop table if exists
mysqli_query($conn, "DROP TABLE IF EXISTS tblUser");

// Create table
$sql = "CREATE TABLE tblUser (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    username VARCHAR(50),
    password VARCHAR(255),
    status VARCHAR(20)
)";

mysqli_query($conn, $sql);

// Load data from file
$file = fopen("../data/userData.txt", "r");
if (!$file) {
    die("Error: Unable to open userData.txt");
}

while (($line = fgets($file)) !== false) {
    $data = explode(",", trim($line));

    // Skip malformed/empty rows in the data file
    if (count($data) < 4) {
        continue;
    }

    $name = $data[0];
    $email = $data[1];
    $username = $data[2];
    $password = $data[3];

    $query = "INSERT INTO tblUser (name, email, username, password, status)
              VALUES ('$name', '$email', '$username', '$password', 'active')";

    mysqli_query($conn, $query);
}

fclose($file);

echo "Table created and data inserted!";
?>