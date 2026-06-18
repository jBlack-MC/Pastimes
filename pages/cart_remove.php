<?php
/* cart_remove.php – AJAX endpoint that removes a single product line from
   the current user's shopping cart (tblcart).
   Called by the trash-icon button on checkout.php via fetch().

   POST parameters:
     product_id  int  (required) – the product to remove

   Response: JSON { success, message } */

session_start();
/* All responses from this endpoint are JSON */
header('Content-Type: application/json');

/* Only authenticated users may modify a cart; return 401 for guests */
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authenticated. Please login first."]);
    exit;
}

/* Include database connection ($conn) */
require_once __DIR__ . "/../config/DBConn.php";

/* This endpoint only accepts POST requests; reject everything else */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use POST."]);
    exit;
}

/* Cast product_id to int – any non-numeric value becomes 0 (falsy) */
$product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : null;
if (!$product_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Product ID is required."]);
    exit;
}

/* Read the logged-in user's ID from session (set at login) */
$user_id = (int)$_SESSION["user_id"];

/* Delete the cart row that belongs to THIS user and THIS product.
   The WHERE clause on both columns prevents users from deleting
   another user's cart items. */
$deleteStmt = mysqli_prepare($conn,
    "DELETE FROM tblcart WHERE user_id = ? AND product_id = ?"
);
if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
    exit;
}

/* Bind both parameters as integers ("ii") and execute */
mysqli_stmt_bind_param($deleteStmt, "ii", $user_id, $product_id);
$success      = mysqli_stmt_execute($deleteStmt);
$affectedRows = mysqli_stmt_affected_rows($deleteStmt); // 1 = deleted, 0 = not found
mysqli_stmt_close($deleteStmt);

if ($success) {
    if ($affectedRows > 0) {
        /* Row deleted – let the front-end remove the card from the DOM */
        echo json_encode(["success" => true, "message" => "Item removed from cart"]);
    } else {
        /* Execute succeeded but nothing was deleted – product wasn't in cart */
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Item not found in cart."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to remove from cart."]);
}

mysqli_close($conn);
?>
