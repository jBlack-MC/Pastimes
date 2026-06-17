<?php
$host = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USERNAME") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_DATABASE") ?: "ClothingStore";

$conn = @mysqli_connect($host, $username, $password, $database);

if (!$conn && $database !== strtolower($database)) {
    $conn = @mysqli_connect($host, $username, $password, strtolower($database));
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
