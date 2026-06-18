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

/* Must have a delivered order containing this product */
$elig = mysqli_prepare($conn,
    "SELECT 1 FROM tblorder o
     JOIN tblorderline ol ON o.order_id = ol.order_id
     WHERE o.user_id = ? AND ol.product_id = ? AND o.status = 'delivered'
     LIMIT 1"
);
$eligible = false;
if ($elig) {
    mysqli_stmt_bind_param($elig, "ii", $user_id, $product_id);
    mysqli_stmt_execute($elig);
    $eligible = mysqli_num_rows(mysqli_stmt_get_result($elig)) > 0;
    mysqli_stmt_close($elig);
}

if (!$eligible) {
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
