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

/* Ensure the soft-delete column exists. Products referenced by past orders
   cannot be physically removed (FK constraint on tblorderline), so they are
   hidden via is_deleted instead. IF NOT EXISTS makes this a safe no-op once
   the column is present. */
try {
    mysqli_query($conn, "ALTER TABLE tblclothes ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0");
} catch (mysqli_sql_exception $e) {
    /* Column already exists or user lacks ALTER privilege – safe to ignore */
}
?>
