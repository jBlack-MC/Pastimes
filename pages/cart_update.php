<?php
/**
 * Shopping Cart - Update Quantity API
 * 
 * Updates the quantity of a product in the user's shopping cart.
 * 
 * POST Parameters:
 *  - product_id: int (required)
 *  - quantity: int (required, min 1)
 * 
 * Returns JSON response with updated total
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

// Get and validate parameters
$product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : null;
$quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : null;

if (!$product_id || !$quantity) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Product ID and quantity are required."
    ]);
    exit;
}

// Validate quantity
if ($quantity < 1) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Quantity must be at least 1."
    ]);
    exit;
}

$user_id = $_SESSION["user_id"];

// Check stock availability
$stockStmt = mysqli_prepare($conn, "SELECT stock, price FROM tblclothes WHERE product_id = ? LIMIT 1");
if (!$stockStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stockStmt, "i", $product_id);
mysqli_stmt_execute($stockStmt);
$stockResult = mysqli_stmt_get_result($stockStmt);
$product = mysqli_fetch_assoc($stockResult);
mysqli_stmt_close($stockStmt);

if (!$product) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);
    exit;
}

if ($quantity > $product["stock"]) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Quantity exceeds available stock (" . $product["stock"] . " available)."
    ]);
    exit;
}

// Update quantity
$updateStmt = mysqli_prepare($conn, "UPDATE tblcart SET quantity = ? WHERE user_id = ? AND product_id = ?");
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($updateStmt, "iii", $quantity, $user_id, $product_id);
$success = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if ($success) {
    // Calculate line total
    $lineTotal = $quantity * $product["price"];
    
    // Get updated cart total
    $totalStmt = mysqli_prepare($conn, 
        "SELECT SUM(c.quantity * p.price) AS cart_total 
         FROM tblcart c 
         JOIN tblclothes p ON c.product_id = p.product_id 
         WHERE c.user_id = ?"
    );
    $cartTotal = ["cart_total" => 0];
    if ($totalStmt) {
        mysqli_stmt_bind_param($totalStmt, "i", $user_id);
        mysqli_stmt_execute($totalStmt);
        $totalResult = mysqli_stmt_get_result($totalStmt);
        $cartTotal = mysqli_fetch_assoc($totalResult) ?: $cartTotal;
        mysqli_stmt_close($totalStmt);
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Quantity updated",
        "quantity" => $quantity,
        "line_total" => (float)$lineTotal,
        "cart_total" => (float)($cartTotal["cart_total"] ?? 0)
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to update quantity."
    ]);
}

mysqli_close($conn);
?>
