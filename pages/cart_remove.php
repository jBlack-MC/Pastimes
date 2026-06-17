<?php
/**
 * Shopping Cart - Remove Item API
 * 
 * Removes a product from the user's shopping cart.
 * 
 * POST Parameters:
 *  - product_id: int (required)
 * 
 * Returns JSON response
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Not authenticated. Please login first."
    ]);
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST."
    ]);
    exit;
}

// Get and validate product_id
$product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : null;
if (!$product_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Product ID is required."
    ]);
    exit;
}

$user_id = $_SESSION["user_id"];

// Remove item from cart
$deleteStmt = mysqli_prepare($conn, "DELETE FROM tblcart WHERE user_id = ? AND product_id = ?");
if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($deleteStmt, "ii", $user_id, $product_id);
$success = mysqli_stmt_execute($deleteStmt);
$affectedRows = mysqli_stmt_affected_rows($deleteStmt);
mysqli_stmt_close($deleteStmt);

if ($success) {
    if ($affectedRows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Item removed from cart"
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Item not found in cart."
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to remove from cart."
    ]);
}

mysqli_close($conn);
?>
