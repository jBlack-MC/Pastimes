<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id    = (int)$_SESSION["user_id"];
$product_id = (int)($_POST["product_id"] ?? 0);
$rating     = (int)($_POST["rating"] ?? 0);
$comment    = trim($_POST["comment"] ?? "");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || $product_id <= 0 || $rating < 1 || $rating > 5) {
    header("Location: shop.php");
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblreview (
    review_id  INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_per_user (product_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Any signed-in shopper may review – a purchase is not required.
   Just confirm the product exists and is still live before saving. */
$prodOk = false;
$pchk = mysqli_prepare($conn,
    "SELECT 1 FROM tblclothes WHERE product_id = ? AND is_deleted = 0 LIMIT 1"
);
if ($pchk) {
    mysqli_stmt_bind_param($pchk, "i", $product_id);
    mysqli_stmt_execute($pchk);
    $prodOk = mysqli_num_rows(mysqli_stmt_get_result($pchk)) > 0;
    mysqli_stmt_close($pchk);
}

if (!$prodOk) {
    header("Location: product.php?id={$product_id}&err=not_eligible");
    exit;
}

/* Insert or update (user can edit their own review) */
$ins = mysqli_prepare($conn,
    "INSERT INTO tblreview (product_id, user_id, rating, comment)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)"
);
if ($ins) {
    mysqli_stmt_bind_param($ins, "iiis", $product_id, $user_id, $rating, $comment);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
}

header("Location: product.php?id={$product_id}&reviewed=1");
exit;
