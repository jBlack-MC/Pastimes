<?php
/* cart_update.php – AJAX endpoint that changes the quantity of a product
   already in the user's cart.  Called by the quantity input on checkout.php.

   POST parameters:
     product_id  int  (required) – which cart row to update
     quantity    int  (required, min 1) – new desired quantity

   Response: JSON { success, message, quantity, line_total, cart_total }
     line_total  = new price for this one product line
     cart_total  = updated grand total for the whole cart */

session_start();
/* Tell the browser (and fetch()) to expect JSON */
header('Content-Type: application/json');

/* Guests have no cart – return 401 so the front-end can redirect to login */
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authenticated. Please login first."]);
    exit;
}

/* Include database connection ($conn) */
require_once __DIR__ . "/../config/DBConn.php";

/* Only POST is allowed; block GETs to prevent accidental quantity resets */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use POST."]);
    exit;
}

/* Cast inputs to int – invalid/missing values become 0 (falsy) */
$product_id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : null;
$quantity   = isset($_POST["quantity"])   ? (int)$_POST["quantity"]   : null;

if (!$product_id || !$quantity) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Product ID and quantity are required."]);
    exit;
}

/* Quantity must be at least 1 – use cart_remove.php to delete a line */
if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Quantity must be at least 1."]);
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* Fetch the product's current stock and unit price so we can:
   1. Reject the update if the requested qty exceeds available stock.
   2. Recalculate the line total to return in the JSON response. */
$stockStmt = mysqli_prepare($conn,
    "SELECT stock, price FROM tblclothes WHERE product_id = ? LIMIT 1"
);
if (!$stockStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stockStmt, "i", $product_id);
mysqli_stmt_execute($stockStmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stockStmt));
mysqli_stmt_close($stockStmt);

if (!$product) {
    /* Product was deleted from the catalogue while it was in the cart */
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Product not found."]);
    exit;
}

/* Prevent the customer from ordering more units than are in stock */
if ($quantity > $product["stock"]) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Quantity exceeds available stock (" . $product["stock"] . " available)."
    ]);
    exit;
}

/* Update the cart row – WHERE on both user_id and product_id so a user
   can only edit their own cart lines */
$updateStmt = mysqli_prepare($conn,
    "UPDATE tblcart SET quantity = ? WHERE user_id = ? AND product_id = ?"
);
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
    exit;
}
/* "iii" = three integer parameters: new quantity, user_id, product_id */
mysqli_stmt_bind_param($updateStmt, "iii", $quantity, $user_id, $product_id);
$success = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if ($success) {
    /* Recalculate line total (this product only) for the front-end to update */
    $lineTotal = $quantity * (float)$product["price"];

    /* Recalculate the full cart total by summing all lines for this user */
    $totalStmt = mysqli_prepare($conn,
        "SELECT SUM(c.quantity * p.price) AS cart_total
         FROM   tblcart c
         JOIN   tblclothes p ON c.product_id = p.product_id
         WHERE  c.user_id = ?"
    );
    $cartTotal = 0;
    if ($totalStmt) {
        mysqli_stmt_bind_param($totalStmt, "i", $user_id);
        mysqli_stmt_execute($totalStmt);
        $tRow      = mysqli_fetch_assoc(mysqli_stmt_get_result($totalStmt));
        $cartTotal = (float)($tRow["cart_total"] ?? 0);
        mysqli_stmt_close($totalStmt);
    }

    /* Return updated figures so the checkout page can refresh totals in-place */
    echo json_encode([
        "success"    => true,
        "message"    => "Quantity updated",
        "quantity"   => $quantity,
        "line_total" => $lineTotal,
        "cart_total" => $cartTotal,
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update quantity."]);
}

mysqli_close($conn);
