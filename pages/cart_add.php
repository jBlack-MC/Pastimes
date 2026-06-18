<?php
/**
 * Shopping Cart - Add Item API
 * 
 * Adds a product to the user's shopping cart.
 * If product already exists, increases quantity by the requested amount.
 * 
 * POST Parameters:
 *  - product_id: int (required)
 *  - quantity: int (optional, defaults to 1)
 * 
 * Returns JSON for AJAX requests or redirects normal form posts to shop.php.
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

$requestedQuantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : 1;
if ($requestedQuantity < 1) {
    $requestedQuantity = 1;
}

$user_id = $_SESSION["user_id"];

function getCartCount($conn, $user_id) {
    $countStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) AS cart_count FROM tblcart WHERE user_id = ?");
    if (!$countStmt) {
        return 0;
    }

    mysqli_stmt_bind_param($countStmt, "i", $user_id);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);
    mysqli_stmt_close($countStmt);

    return (int)($countRow["cart_count"] ?? 0);
}

// Check if product exists and has stock
$productStmt = mysqli_prepare($conn, "SELECT product_id, name, price, stock FROM tblclothes WHERE product_id = ? LIMIT 1");
if (!$productStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($productStmt, "i", $product_id);
mysqli_stmt_execute($productStmt);
$productResult = mysqli_stmt_get_result($productStmt);
$product = mysqli_fetch_assoc($productResult);
mysqli_stmt_close($productStmt);

if (!$product) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);
    exit;
}

if ($product["stock"] <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Product is out of stock."
    ]);
    exit;
}

// Check if product already in cart
$checkStmt = mysqli_prepare($conn, "SELECT cart_id, quantity FROM tblcart WHERE user_id = ? AND product_id = ? LIMIT 1");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($checkStmt, "ii", $user_id, $product_id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$existingItem = mysqli_fetch_assoc($checkResult);
mysqli_stmt_close($checkStmt);

if ($existingItem) {
    // Product already in cart - increase quantity
    $newQuantity = $existingItem["quantity"] + $requestedQuantity;
    
    // Check stock limit
    if ($newQuantity > $product["stock"]) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Cannot add more. Only " . $product["stock"] . " in stock."
        ]);
        exit;
    }
    
    $updateStmt = mysqli_prepare($conn, "UPDATE tblcart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    if (!$updateStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . mysqli_error($conn)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($updateStmt, "iii", $newQuantity, $user_id, $product_id);
    $success = mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    
    if ($success) {
        if (empty($_SERVER["HTTP_X_REQUESTED_WITH"])) {
            mysqli_close($conn);
            header("Location: shop.php");
            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Quantity increased to " . $newQuantity,
            "quantity" => $newQuantity,
            "product_name" => $product["name"],
            "cart_count" => getCartCount($conn, $user_id)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update cart."
        ]);
    }
} else {
    if ($requestedQuantity > $product["stock"]) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Cannot add more. Only " . $product["stock"] . " in stock."
        ]);
        exit;
    }

    /* Insert new cart row with the correct quantity in a single statement */
    $insertStmt = mysqli_prepare($conn, "INSERT INTO tblcart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    if (!$insertStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($insertStmt, "iii", $user_id, $product_id, $requestedQuantity);
    $success = mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
    
    if ($success) {
        if (empty($_SERVER["HTTP_X_REQUESTED_WITH"])) {
            mysqli_close($conn);
            header("Location: shop.php");
            exit;
        }

        echo json_encode([
            "success" => true,
            "message" => "Added to cart",
            "product_name" => $product["name"],
            "quantity" => $requestedQuantity,
            "cart_count" => getCartCount($conn, $user_id)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to add to cart."
        ]);
    }
}

mysqli_close($conn);
?>
