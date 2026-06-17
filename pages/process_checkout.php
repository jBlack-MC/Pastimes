<?php
/**
 * Process Checkout
 * 
 * Converts shopping cart to order:
 * 1. Creates order in tblorder
 * 2. Creates order line items in tblorderline
 * 3. Decrements stock in tblclothes
 * 4. Clears user's cart
 * 5. Shows order confirmation
 */

session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Check authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$name = $_SESSION["name"] ?? "User";
$user_id = $_SESSION["user_id"];
$order_id = null;
$order_number = null;
$total_price = 0;
$delivery_address = "";
$error_message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get delivery address
    $delivery_address = trim($_POST["delivery_address"] ?? "");
    
    if (empty($delivery_address)) {
        $error_message = "Delivery address is required.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Step 1: Get cart items with product details
            $cartStmt = mysqli_prepare($conn, 
                "SELECT c.product_id, SUM(c.quantity) AS quantity, p.price, p.stock 
                 FROM tblcart c 
                 JOIN tblclothes p ON c.product_id = p.product_id 
                 WHERE c.user_id = ?
                 GROUP BY c.product_id, p.price, p.stock"
            );
            
            if (!$cartStmt) {
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($cartStmt, "i", $user_id);
            mysqli_stmt_execute($cartStmt);
            $cartResult = mysqli_stmt_get_result($cartStmt);
            $cartItems = [];
            
            while ($item = mysqli_fetch_assoc($cartResult)) {
                $cartItems[] = $item;
            }
            mysqli_stmt_close($cartStmt);
            
            // Check cart not empty
            if (count($cartItems) === 0) {
                throw new Exception("Your cart is empty. Please add items before checking out.");
            }
            
            // Check stock availability
            foreach ($cartItems as $item) {
                if ($item["quantity"] > $item["stock"]) {
                    throw new Exception("Insufficient stock for product ID " . $item["product_id"]);
                }
            }
            
            // Step 2: Calculate total
            $total_price = 0;
            foreach ($cartItems as $item) {
                $total_price += ($item["quantity"] * $item["price"]);
            }
            
            // Step 3: Create order
            $insertOrderStmt = mysqli_prepare($conn, 
                "INSERT INTO tblorder (user_id,total_price,delivery_address)
                 VALUES (?,?,?)"
            );
            
            if (!$insertOrderStmt) {
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($insertOrderStmt, "ids", $user_id, $total_price, $delivery_address);
            
            if (!mysqli_stmt_execute($insertOrderStmt)) {
                throw new Exception("Failed to create order: " . mysqli_error($conn));
            }
            
            $order_id = mysqli_insert_id($conn);
            $order_number = "ORD-" . str_pad($order_id, 8, "0", STR_PAD_LEFT);
            mysqli_stmt_close($insertOrderStmt);
            
            // Step 4: Create order line items
            foreach ($cartItems as $item) {
                $insertLineStmt = mysqli_prepare($conn, 
                    "INSERT INTO tblorderline (order_id, product_id, quantity, unit_price) 
                     VALUES (?, ?, ?, ?)"
                );
                
                if (!$insertLineStmt) {
                    throw new Exception("Database error: " . mysqli_error($conn));
                }
                
                $product_id = $item["product_id"];
                $quantity = $item["quantity"];
                $unit_price = $item["price"];
                
                mysqli_stmt_bind_param($insertLineStmt, "iiid", $order_id, $product_id, $quantity, $unit_price);
                
                if (!mysqli_stmt_execute($insertLineStmt)) {
                    throw new Exception("Failed to create order line: " . mysqli_error($conn));
                }
                mysqli_stmt_close($insertLineStmt);
            }
            
            // Step 5: Update stock
            foreach ($cartItems as $item) {
                $updateStockStmt = mysqli_prepare($conn, 
                    "UPDATE tblclothes SET stock = stock - ? WHERE product_id = ?"
                );
                
                if (!$updateStockStmt) {
                    throw new Exception("Database error: " . mysqli_error($conn));
                }
                
                $product_id = $item["product_id"];
                $quantity = $item["quantity"];
                
                mysqli_stmt_bind_param($updateStockStmt, "ii", $quantity, $product_id);
                
                if (!mysqli_stmt_execute($updateStockStmt)) {
                    throw new Exception("Failed to update stock: " . mysqli_error($conn));
                }
                mysqli_stmt_close($updateStockStmt);
            }
            
            // Step 6: Clear cart
            $clearCartStmt = mysqli_prepare($conn, "DELETE FROM tblcart WHERE user_id = ?");
            
            if (!$clearCartStmt) {
                throw new Exception("Database error: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($clearCartStmt, "i", $user_id);
            
            if (!mysqli_stmt_execute($clearCartStmt)) {
                throw new Exception("Failed to clear cart: " . mysqli_error($conn));
            }
            mysqli_stmt_close($clearCartStmt);
            
            // Commit transaction
            mysqli_commit($conn);
            $success = true;
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}

// If not POST and no success, fetch cart for display
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !$success) {
    $cartStmt = mysqli_prepare($conn, 
        "SELECT c.product_id, SUM(c.quantity) AS quantity, p.name, p.price 
         FROM tblcart c 
         JOIN tblclothes p ON c.product_id = p.product_id 
         WHERE c.user_id = ? 
         GROUP BY c.product_id, p.name, p.price
         ORDER BY c.product_id DESC"
    );
    
    if ($cartStmt) {
        mysqli_stmt_bind_param($cartStmt, "i", $user_id);
        mysqli_stmt_execute($cartStmt);
        $cartResult = mysqli_stmt_get_result($cartStmt);
        $cartItems = [];
        
        while ($item = mysqli_fetch_assoc($cartResult)) {
            $cartItems[] = $item;
        }
        mysqli_stmt_close($cartStmt);
        
        // Calculate total
        $total_price = 0;
        foreach ($cartItems as $item) {
            $total_price += ($item["quantity"] * $item["price"]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes · Checkout</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --sand: #f6efe5;
      --paper: #fffaf4;
      --ink: #1f1a17;
      --muted: #6e635a;
      --rust: #bf5a36;
      --forest: #1f5a4d;
      --gold: #d7a85f;
      --line: rgba(31, 26, 23, 0.12);
      --shadow: 0 18px 45px rgba(31, 26, 23, 0.12);
      --radius-lg: 28px;
      --radius-md: 18px;
      --radius-sm: 12px;
    }

    body {
      font-family: system-ui, "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, #fcf7f0 0%, #f5ede2 100%);
      min-height: 100vh;
      line-height: 1.5;
    }

    .app-wrapper {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 1rem 2rem;
    }

    .top-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      padding: 0.8rem 1.2rem;
      background: rgba(255, 250, 244, 0.92);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: 60px;
      backdrop-filter: blur(12px);
      box-shadow: 0 6px 18px rgba(31, 26, 23, 0.05);
      margin-bottom: 1.8rem;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo-icon {
      width: 42px;
      height: 42px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--forest), #2f7968);
      color: white;
      font-size: 1.4rem;
    }

    .logo-text {
      font-weight: 800;
      font-size: 1.6rem;
      letter-spacing: -0.02em;
      color: var(--forest);
    }

    .breadcrumb {
      margin-bottom: 1.8rem;
      font-size: 0.85rem;
      color: var(--muted);
    }

    .breadcrumb a {
      color: var(--forest);
      text-decoration: none;
      font-weight: 600;
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: var(--ink);
    }

    .alert {
      padding: 1rem;
      border-radius: var(--radius-md);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert-error {
      background: #fef2f2;
      border: 1px solid #fca5a5;
      color: #991b1b;
    }

    .alert-success {
      background: #ecfdf5;
      border: 1px solid #86efac;
      color: #166534;
    }

    .confirmation-box {
      background: var(--paper);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid rgba(255, 255, 240, 0.8);
      text-align: center;
    }

    .confirmation-icon {
      font-size: 4rem;
      color: var(--forest);
      margin-bottom: 1rem;
    }

    .order-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--forest);
      margin: 1rem 0;
      font-family: monospace;
    }

    .session-id {
      font-size: 0.9rem;
      color: var(--muted);
      margin: 1rem 0;
      font-family: monospace;
    }

    .checkout-container {
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 28px;
    }

    .cart-section, .summary-section {
      background: var(--paper);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 240, 0.8);
    }

    h2 {
      font-size: 1.4rem;
      margin-bottom: 1.2rem;
      color: var(--ink);
    }

    .cart-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      margin-bottom: 0.8rem;
      gap: 1rem;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      font-weight: 600;
      margin-bottom: 0.3rem;
    }

    .item-price {
      font-size: 0.9rem;
      color: var(--muted);
    }

    .item-quantity {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .item-quantity input {
      width: 50px;
      padding: 0.3rem;
      border: 1px solid var(--line);
      border-radius: 4px;
      text-align: center;
    }

    .item-total {
      font-weight: 700;
      color: var(--rust);
      min-width: 80px;
      text-align: right;
    }

    .btn {
      padding: 0.7rem 1.2rem;
      border: none;
      border-radius: 40px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-primary {
      background: var(--forest);
      color: white;
    }

    .btn-primary:hover {
      background: #1e5247;
    }

    .btn-secondary {
      background: var(--paper);
      color: var(--ink);
      border: 1px solid var(--line);
    }

    .btn-secondary:hover {
      background: #f0ebe2;
    }

    .btn-danger {
      background: #fee2e2;
      color: var(--rust);
      border: 1px solid #fca5a5;
    }

    .btn-danger:hover {
      background: #fecaca;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }

    textarea, input[type="text"] {
      width: 100%;
      padding: 0.7rem;
      border: 1px solid var(--line);
      border-radius: var(--radius-sm);
      font-family: inherit;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.8rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid var(--line);
    }

    .summary-total {
      display: flex;
      justify-content: space-between;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--rust);
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 2px solid var(--line);
    }

    .empty-cart {
      text-align: center;
      padding: 2rem;
      color: var(--muted);
    }

    .empty-cart-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .button-group {
      display: flex;
      gap: 0.5rem;
      margin-top: 1.5rem;
    }

    .button-group button {
      flex: 1;
    }

    @media (max-width: 768px) {
      .checkout-container {
        grid-template-columns: 1fr;
      }

      .cart-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .item-total {
        align-self: flex-end;
      }
    }
  </style>
</head>
<body>
  <div class="app-wrapper">
    <!-- Header -->
    <div class="top-bar">
      <div class="logo-area">
        <div class="logo-icon">👔</div>
        <div class="logo-text">Pastimes</div>
      </div>
      <nav style="display: flex; gap: 0.5rem;">
        <a href="shop.php" class="btn btn-secondary">
          <i class="fas fa-shopping-bag"></i> Shop
        </a>
        <a href="logout.php" class="btn btn-secondary">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="shop.php">Shop</a> > 
      <span>Checkout</span>
    </div>

    <?php if ($success && $order_id): ?>

    <!-- ORDER CONFIRMATION -->
    <div class="confirmation-box">
      <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h1>Order Confirmed!</h1>
      <p style="color: var(--muted); margin-bottom: 1rem;">Thank you for your purchase, <?php echo htmlspecialchars($name); ?>!</p>
      
      <div class="order-number">
        Order Number: <?php echo htmlspecialchars($order_number); ?>
      </div>
      
      <div class="alert alert-success">
        <i class="fas fa-info-circle"></i>
        <div>
          Your order has been placed successfully. You will receive a confirmation email shortly.
        </div>
      </div>

      <div class="session-id" style="margin-top: 1.5rem;">
        Session ID: <strong><?php echo session_id(); ?></strong>
      </div>

      <div style="margin-top: 1.5rem; text-align: left; background: var(--sand); padding: 1rem; border-radius: var(--radius-sm);">
        <strong>Order Summary:</strong>
        <div style="margin-top: 0.8rem;">
          Total Amount: <strong style="color: var(--rust);">$<?php echo number_format($total_price, 2); ?></strong>
        </div>
        <div style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--muted);">
          Delivery Address:<br>
          <?php echo nl2br(htmlspecialchars($delivery_address)); ?>
        </div>
      </div>

      <div class="button-group">
        <a href="shop.php" class="btn btn-primary" style="justify-content: center;">
          <i class="fas fa-shopping-bag"></i> Continue Shopping
        </a>
      </div>
    </div>

    <?php else: ?>

    <!-- CHECKOUT FORM -->
    <h1>Checkout</h1>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      <div><?php echo htmlspecialchars($error_message); ?></div>
    </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

    <div class="empty-cart">
      <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
      <h2>Your Cart is Empty</h2>
      <p>Add some items before checking out</p>
      <a href="shop.php" class="btn btn-primary" style="margin-top: 1rem;">
        <i class="fas fa-shopping-bag"></i> Continue Shopping
      </a>
    </div>

    <?php else: ?>

    <div class="checkout-container">
      <!-- Cart Items -->
      <div class="cart-section">
        <h2>Order Items</h2>
        
        <?php foreach ($cartItems as $item): ?>
        <div class="cart-item">
          <div class="item-details">
            <div class="item-name"><?php echo htmlspecialchars($item["name"]); ?></div>
            <div class="item-price">$<?php echo number_format($item["price"], 2); ?> each</div>
          </div>
          <div class="item-quantity">
            <span><?php echo (int)$item["quantity"]; ?> ×</span>
          </div>
          <div class="item-total">
            $<?php echo number_format($item["quantity"] * $item["price"], 2); ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary & Checkout Form -->
      <div class="summary-section">
        <h2>Order Summary</h2>

        <div class="summary-row">
          <span>Subtotal:</span>
          <span>$<?php echo number_format($total_price, 2); ?></span>
        </div>

        <div class="summary-row">
          <span>Tax (estimated):</span>
          <span>$0.00</span>
        </div>

        <div class="summary-row">
          <span>Shipping:</span>
          <span>Free</span>
        </div>

        <div class="summary-total">
          <span>Total:</span>
          <span>$<?php echo number_format($total_price, 2); ?></span>
        </div>

        <!-- Checkout Form -->
        <form method="POST" style="margin-top: 2rem;">
          <div class="form-group">
            <label for="delivery_address">Delivery Address *</label>
            <textarea 
              id="delivery_address" 
              name="delivery_address" 
              required 
              rows="4"
              placeholder="Enter your full delivery address..."
              style="resize: vertical;"
            ></textarea>
          </div>

          <div class="button-group">
            <a href="checkout.php" class="btn btn-secondary" style="justify-content: center;">
              <i class="fas fa-arrow-left"></i> Back
            </a>
            <button type="submit" class="btn btn-primary" style="flex: 2;">
              <i class="fas fa-check"></i> Place Order
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php endif; ?>

    <?php endif; ?>

  </div>
</body>
</html>
<?php mysqli_close($conn); ?>
