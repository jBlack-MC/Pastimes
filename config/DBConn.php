<?php
$conn = mysqli_connect("localhost", "root", "", "ClothingStore");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>